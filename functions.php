<?php
// Import the code responsible for the RSVP feature.
require_once get_stylesheet_directory() . '/rsvp-functions.php';

// Enqueue the parent theme's and child theme's stylesheets.
function magzine_child_enqueue_styles() {
    wp_enqueue_style('magzine-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('magzine-child-style', get_stylesheet_uri(), array('magzine-style'));
}
add_action('wp_enqueue_scripts', 'magzine_child_enqueue_styles');

// Enqueue the child theme's scripts.
function enqueue_child_theme_scripts() {
  if ( ! is_admin() ) {
    wp_enqueue_script( 'custom-js', get_stylesheet_directory_uri() . '/custom.js', array('jquery'), '1.0', true );

    // Pass the current URI to the script
    wp_localize_script( 'custom-js', 'siteData', array(
        'pageUri' => trim($_SERVER['REQUEST_URI'], '/')
    ) );
  }
}
add_action('wp_enqueue_scripts', 'enqueue_child_theme_scripts');

// Enqueue the child theme's scripts for the admin dashboard.
function enqueue_child_theme_admin_scripts() {
  if ( is_admin() ) {
    wp_enqueue_script('custom-admin-js', get_stylesheet_directory_uri() . '/custom-admin.js', array('jquery'), '1.0', true);

    // Optionally, localize admin script (if needed)
    wp_localize_script('custom-admin-js', 'adminData', array(
        'adminUri' => trim($_SERVER['REQUEST_URI'], '/')
    ));
  }
}
add_action('admin_enqueue_scripts', 'enqueue_child_theme_admin_scripts');

// Enqueue the child theme's stylesheet for the admin panel.
function magzine_child_admin_styles() {
  wp_enqueue_style('magzine-child-admin-style', get_stylesheet_directory_uri() . '/style-admin.css');
}
add_action('admin_enqueue_scripts', 'magzine_child_admin_styles');

// Shortcode to output the site URL dynamically.
function dynamic_home_url_shortcode() {  
  return esc_url( home_url() );
}
add_shortcode('home_url', 'dynamic_home_url_shortcode');

// Make Elementor widgets accept shortcodes in their settings.
add_action('elementor/widget/before_render_content', function($widget) {
  $settings = $widget->get_settings();

  // Check if 'custom_link' exists and contains the shortcode
  if (isset($settings['custom_link']['url']) && strpos($settings['custom_link']['url'], '[home_url]') !== false) {
    // Process the shortcode and update the 'custom_link' URL
    $settings['custom_link']['url'] = do_shortcode($settings['custom_link']['url']);
    $widget->set_settings('custom_link', $settings['custom_link']);
  }
});

// Hide the WP admin bar when a logged-in user isn't an administrator.
if (!current_user_can('administrator')) {
  add_filter('show_admin_bar', '__return_false');
}

// Change the status of a new PMPro order to "pending" if the payment type is "Check" or "Zelle".
function update_order_status_to_pending( $user_id, $order ) {
  global $wpdb;
  // Check if the payment method is 'Check or Zelle.' (The payment type when created via the site will always be "Check".)
  if ( $order->payment_type === 'Check' || $order->payment_type === 'Zelle' ) {

    // Query the wp_pmpro_subscriptions table for the user's active subscription, if it exists. (This is for renewals.)
    $subscription = $wpdb->get_row( $wpdb->prepare(
      "SELECT membership_level_id, status, startdate, next_payment_date
      FROM {$wpdb->prefix}pmpro_subscriptions
      WHERE user_id = %d
      AND status = 'active'",
      $user_id
    ) );

    error_log( 'the subscription is: ' . print_r( $subscription, true ) );

    // If the order's timestamp doesn't equal the subscription's startdate, then we have a renewal: set the order's status to "Review" to maintain the user's access and don't change the subscription's status; also, update the confirmation message if the order's status is "Review" to reflect that fact.

    // Mark the order as "pending" until the payment is received.
    $order->updateStatus('pending');

    // Set the user's subscription's status to 'review' (keeps subscription active, but restricts access until payment is received).
    $wpdb->update(
      "{$wpdb->prefix}pmpro_memberships_users",
      array('status' => 'review'),
      array('user_id' => $user_id, 'membership_id' => $order->membership_id),
      array('%s'),
      array('%d', '%d')
    );
  }
}
add_action('pmpro_after_checkout', 'update_order_status_to_pending', 10, 2);

// Activate or deactivate the membership based on the payment's status of "pending" or "success"; set the expiration date for one year.
function log_pmpro_update_order( $order ) {
  global $wpdb;

  if ( $order->gateway === 'check' || $order->gateway === 'zelle' ) {
    if ( $order->status === 'success' || $order->status === 'review' ) {
      // Activate the membership.
      pmpro_changeMembershipLevel($order->membership_id, $order->user_id); 

      // Get the expiration timestamp (one year from now)
      $expiration_timestamp = strtotime('+1 year'); // Get the Unix timestamp

      // Ensure the expiration timestamp is an integer (just for extra safety)
      $expiration_timestamp = (int) $expiration_timestamp;

      // Format the expiration timestamp into MySQL-compatible datetime format
      $expiration_date = date('Y-m-d H:i:s', (int) $expiration_timestamp); // Force the timestamp to be treated as an integer.
            
      // Update the expiration date in the `pmpro_memberships_users` table
      $updated_rows = $wpdb->update(
        $wpdb->prefix . 'pmpro_memberships_users',
        array('enddate' => $expiration_date),
        array('user_id' => $order->user_id),
        array('%s'),
        array('%d')
      );
    } elseif ( $order->status === 'pending' ) {
      // Set the user's subscription's status to 'review' (keeps subscription active, but restricts access until payment is received).
      $wpdb->update(
        "{$wpdb->prefix}pmpro_memberships_users",
        array('status' => 'review'),
        array('user_id' => $order->user_id, 'membership_id' => $order->membership_id),
        array('%s'),
        array('%d', '%d')
      );
    }

    // Set flag for automatic page refresh.
    update_user_meta($order->user_id, 'pmpro_order_status_changed', [
      'order_id' => $order->id,
      'status' => $order->status,
      'timestamp' => time()
    ]);
  }
}
add_action('pmpro_update_order', 'log_pmpro_update_order', 10, 1);

// Add AJAX endpoint for checking order status changes.
function pmpro_check_order_status_change() {
    // Security check
    if ( !is_user_logged_in() ) {
        wp_send_json_error('User not logged in');
        return;
    }
    
    $user_id = get_current_user_id();
    $status_change = get_user_meta($user_id, 'pmpro_order_status_changed', true);
    
    if ( !empty( $status_change ) ) {
        // Clear the meta after sending it.
        delete_user_meta( $user_id, 'pmpro_order_status_changed' );
        wp_send_json_success([
            'refresh' => true,
            'order_id' => $status_change['order_id'],
            'status' => $status_change['status']
        ]);
    } else {
        wp_send_json_success(['refresh' => false]);
    }
    
    wp_die();
}
add_action('wp_ajax_pmpro_check_order_status_change', 'pmpro_check_order_status_change');

// Set up the necessary JavaScript variables for logged-in users for the AJAX call to check for a refresh flag.
function pmpro_add_refresh_script_vars() {
  if (is_user_logged_in()) {
    ?>
      <script type="text/javascript">
        const pmpro_refresh_obj = {
          ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
          nonce: '<?php echo wp_create_nonce('pmpro_refresh_nonce'); ?>'
        };
      </script>
    <?php
  }
}
add_action('wp_footer', 'pmpro_add_refresh_script_vars');

// Add the link "Groups" to the right main menu when a user is logged in and has a current membership.
function add_dynamic_menu_link($items, $args) {
  if ( ( $args->menu === 'right-main-menu' || $args->menu === 'mobile-menu' ) && is_user_logged_in() && pmpro_hasMembershipLevel() ) {
    $user = wp_get_current_user();

    $link = site_url('/members/' . $user->user_nicename . '/groups/');    
    $html = '<li id="menu-item-custom-group" class="menu-item menu-item-type-custom menu-item-object-custom"><a class="gp-menu-link" href="' . $link . '">Your Groups</a></li>';

    // Find the position of the "Friends" link and add the new link after it
    $friends_position = strpos($items, 'Friends</a></li>');
    if ($friends_position !== false) {
      $items = substr_replace($items, $html, $friends_position + 16, 0);
    }
  }
  return $items;
}
add_filter('wp_nav_menu_items', 'add_dynamic_menu_link', 10, 2);

// Remove the "Calendar link from the left main menu when a member is logged in. (Logged-in nonmembers should still see the link.)
function remove_calendar_link($items, $args) {
  if ($args->menu === 'left-main-menu' && is_user_logged_in() && pmpro_hasMembershipLevel()) {
    // Find the last occurrence of <li.
    $lastLiPos = strrpos($items, '<li');
    
    if ($lastLiPos !== false) {
      // Find the position of the closing </li> tag after the last <li>.
      $lastLiClosePos = strpos($items, '</li>', $lastLiPos);
      
      if ($lastLiClosePos !== false) {
        // Remove the last <li>...</li>.
        $items = substr($items, 0, $lastLiPos) . substr($items, $lastLiClosePos + 5);
      }
    }
  }

  return $items;
}
add_filter('wp_nav_menu_items', 'remove_calendar_link', 10, 2);

// Redirect user to homepage after logout, not the WP login page.
function custom_logout_redirect() {
  wp_redirect( home_url() ); 
  exit();
}
add_action('wp_logout', 'custom_logout_redirect');

// Correct GhostPool's "login_member_redirect" key to redirect to the homepage. (It incorrectly adds the home url twice in login-form.php. It could be updated there, but this is a more general solution that should work even after an update.)
add_action( 'init', function () {
  // Check if we're handling a form submission with the problematic key.
  if (
      isset( $_POST['action'] ) &&
      $_POST['action'] === 'form_processing' &&
      isset( $_POST['login_member_redirect'] )
  ) {
      // Fix the redirect URL
      $_POST['login_member_redirect'] = home_url();
  }
});
add_filter( 'logout_redirect', function ($redirect_to, $requested_redirect_to, $user) {
  // Ensure the redirect URL is valid.
  if ( empty($redirect_to) || !is_string($redirect_to) ) {
    return home_url(); // Default to home URL if invalid.
  }
  return $redirect_to;
}, 10, 3);

// Revise the registration confirmation message.
function custom_pmpro_confirmation_message($message, $invoice) {
  if ( is_user_logged_in() ) {    
    if ( !pmpro_hasMembershipLevel() ) {
      if ( strpos( $message, 'payment' ) !== false ) {
        $replace_with = '
          <p>
            Thank you for your application to join the VeloRaptors Cycling Club. Your membership will be activated once it has been approved and your payment processed.
          </p>
          <p>
            At that time, your login credentials will grant you access to all of the members-only features and content of our site, including:
          </p>
          <p>
            <ul>
              <li>Upload a profile picture.</li>
              <li>Join groups and view other members\' profiles.</li>
              <li>Make and message friends.</li>
              <li>Make and manage your RSVPs.</li>
            </ul>
          </p>';
      } 
    } else {
      if ( strpos($message, 'active') !== false ) {
        $replace_with = '<p>Your application has been approved and your payment processed. Your membership is now active, and we welcome to the club!</p>';
      }
    }
  }
  
  $message = $message ? preg_replace('/<p>.*?<\/p>/', $replace_with, $message, 1) : $message;
  return $message;
}
add_filter('pmpro_confirmation_message', 'custom_pmpro_confirmation_message', 10, 2);

// Flush rewrite rules ONCE when switching themes
function custom_rsvp_flush_rewrite() {
  custom_rsvp_post_type(); // Ensure the CPT is registered first
  flush_rewrite_rules();
}
add_action('after_switch_theme', 'custom_rsvp_flush_rewrite');

// Filter archived posts out of the query, unless on the archive page.
function customize_ghostpool_query( $args ) {
  if ( !is_page( 'archive-public') && !is_page( 'archive-private' ) ) {

    // Ensure 'tax_query' exists.
    if ( !isset( $args['tax_query'] ) ) {
      $args['tax_query'] = [];
    }

    // Store the original tax query.
    $existing_tax_query = $args['tax_query'];

    // Add the exclusion for the "archive" category
    $archive_exclusion = [
      'taxonomy'         => 'category',
      'field'            => 'slug',
      'terms'            => ['archive'],
      'operator'         => 'NOT IN',
      'include_children' => false,
    ];

    // Wrap everything in an AND relation
    $args['tax_query'] = [
      'relation' => 'AND',
      [
        ...$existing_tax_query,
      ],
      $archive_exclusion,
    ];
  }

  return $args;
}
add_filter( 'ghostpool_items_query', 'customize_ghostpool_query' );

/* Add SEO to public pages; noindex for members-only pages.*/
// Define public pages by their slug
function my_public_pages() {
  return [
    'archive-public',
    'in-memorium',
    'faqs',
    'privacy-policy',
    'rides-routes',
    'about-us',
    'contact-us',
    'home'
  ];
}

// Add SEO meta tags conditionally.
function my_custom_seo_meta() {
  // Exit if not a singular page or post.
  if ( !is_singular() ) {
    return;
  }

  // Get public pages array.
  $public_pages = my_public_pages();

  // Get the current page slug.
  $current_slug = get_post_field( 'post_name', get_post() );

  // Check if the current page is public.
  $is_public = in_array( $current_slug, $public_pages );

  // Add meta tags for public pages.
  if ( $is_public ) {
    echo '<meta name="description" content="' . esc_attr( get_the_excerpt() ) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr( get_the_title() ) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url( get_permalink() ) . '">' . "\n";
  } else {
    // Add noindex for members-only or unknown pages
    echo '<meta name="robots" content="noindex, nofollow">' . "\n";
  }
}
add_action( 'wp_head', 'my_custom_seo_meta' );

// Add the Zelle payment-option instructions to the checkout page.
function add_payment_option_tabs_before_payment() {
  ?>
    <script type="text/javascript">
      jQuery(document).ready(function($) {
        // Ensure the fieldset is loaded before applying changes.
        if ($('#pmpro_payment_information_fields').length) {
        // Create the tabs structure.
        const tabsHtml = `
            <div class="payment-tabs">
                <button class="tab-button active" data-target="check">Pay by Check</button>
                <button class="tab-button" data-target="zelle">Pay via Zelle</button>
            </div>
        `;
        
        // Insert the tabs above the pmpro_payment_information_fields fieldset.
        $('#pmpro_payment_information_fields').prepend(tabsHtml);

        const zellePaymentHtml = `
          <div class="pmpro_card payment-option zelle">
            <div class="pmpro_card_content">
              <legend class="pmpro_form_legend">
                <h2 class="pmpro_form_heading pmpro_font-large">Pay via Zelle</h2>
              </legend>
              <div class="pmpro_form_fields">
                <div class="pmpro_form_field pmpro_zelle_instructions pmpro_checkout">
                  <p class="payment-instructions">To pay via Zelle, you must have an account at a bank that supports Zelle.</p>
                  <p class="payment-instructions">If you do, you may open your bank's mobile app or website, find the Zelle payment option (typically under “Send Money” or “P2P Payments”), and enter the recipient's email address and the amount to be paid.</p>
                  <ul>
                    <li>Recipient's email address: <strong>veloraptors@gmail.com</strong>.</li>
                    <li><strong>NB:</strong> Please include your name in the payment's notes field.</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        `;

        // Add classes to the check-payment info card.
        $('.pmpro_card').addClass('payment-option check active');

        // Insert payment options HTML after the first payment option's div.
        $('#pmpro_payment_information_fields .pmpro_card').after(zellePaymentHtml);

        // Handle tab switching.
        $('.tab-button').click(event => {
          event.preventDefault();

          // Get the clicked tab.
          const target = $(this).data('target');
          
          // Remove active class from all tab buttons.
          $('.tab-button').removeClass('active');

          // Add active class to the clicked tab.
          $(this).addClass('active');

          // Hide all payment options.
          $('.payment-option').removeClass('active');

          // Show the corresponding payment option.
          $('.' + target).addClass('active');                });
        }
      });
    </script>
    <?php
}
add_action('wp_head', 'add_payment_option_tabs_before_payment');

// Add the Zelle payment-option instructions to the application-confirmation page (path: /membership-confirmation/).
function add_payment_option_instructions_after_submission() {
  if ( is_page( 'membership-confirmation' ) ) { ?>
    <script type="text/javascript">
      jQuery(document).ready($ => {
        if ($('#pmpro_order_single-instructions').length) {
          const html = `
            <div class="pmpro_divider"></div>
            <div id="pmpro_order_single-instructions_zelle">
              <h3 class="pmpro_font-large">
						    Payment Instructions: Zelle					
              </h3>
					    <div class="pmpro_payment_instructions">
						    <p>
                  To pay via Zelle, you must have an account at a bank that supports Zelle.<br>

                  If you do, you may open your bank's mobile app or website, find the Zelle payment option (typically under “Send Money” or “P2P Payments”), and enter the recipient's email address and the amount to be paid.
                </p>
                <p class="payment-instructions">
                  <ul>
                    <li>Recipient's email address: <strong>veloraptors@gmail.com</strong>.</li>
                    <li><strong>NB</strong>: Please include your name in the payment's notes field.</li>
                  </ul>
                </p>
					    </div>
				    </div>
          `;

          $('#pmpro_order_single-instructions').after(html);

        }
      });
    </script>
  <?php } 
}
add_action('wp_head', 'add_payment_option_instructions_after_submission');

// Because the visibilty toggle didn't work for the register-renew cards on the contact-us page, it has to be toggled here.
function toggle_registration_card_on_contact_us() {
  if ( is_user_logged_in() ) {
    echo '
      <style>
        #renew-here-card { display: flex; }
        #register-here-card { display: none; };
      </style>
    ';
  } else {
    echo '
      <style>
        #renew-here-card { display: none; }
        #register-here-card { display: flex; };
      </style>
    ';
  }
}
add_action( 'wp_head', 'toggle_registration_card_on_contact_us' );

// Fix the style of the no-access page from pmpro.
function style_membership_required_page() {
  global $post;

  // Check if the user has access to the current post/page
  $has_access = pmpro_has_membership_access($post->ID);

  if ( !$has_access ) {
    echo '
      <style>
        .pmpro_card_actions {
          display: flex;
          flex-direction: column;
          align-items: center;
        }
        .pmpro_card_actions > a {
          max-width: 25rem;
        }
      </style>
    ';
  }
}
add_action( 'wp_head', 'style_membership_required_page' );

// Fix the header spacing on the /groups/{group}/ page.
function fix_header_spacing() {
  if ( preg_match( '#^/groups/([^/]+)(?:/(.*))?$#', $_SERVER['REQUEST_URI'] ) ) {
    echo '
      <style>
        /* Ian wuz ere*/
        @media all and (width >= 1024px) {
          .elementor-container > .elementor-column > .elementor-widget-wrap > .elementor-element > .elementor-widget-container > .gp-element-post-title > .gp-post-title {
            padding-top: 5rem;
          }
        }
      </style>
    ';
  }
}
add_action( 'wp_head', 'fix_header_spacing' );

// Remove the extra-fields section from the pay-by-check confirmation email to admin.
function amend_pmpro_email_body( $body ) {
  // Check if the "Extra Fields" section exists.
  if ( strpos( $body, 'Extra Fields:' ) !== false ) {
    // Remove everything from "Extra Fields:" onward.
    $body = $body ? preg_replace( '/<p>Extra Fields:.*?(<\/p>)/s', '', $body ) : $body;
  }

  // Always return the modified body
  return $body;
}
add_filter( 'pmpro_email_body', 'amend_pmpro_email_body' );

// Add a custom gateway to PMPro's "Gateway" dropdown.
function my_custom_pmpro_gateway($gateways) {
  $gateways['zelle'] = __('Zelle', 'ghostpool-zine');
  return $gateways;
}
add_filter('pmpro_gateways', 'my_custom_pmpro_gateway');

// // Change the number of days before the expiration date for the email notification.
// function my_pmpro_email_expiration_date_change( $days ) {
//   return 3; //change this value to the number of days before the expiration date.
// }
// add_filter( 'pmpro_email_days_before_expiration', 'my_pmpro_email_expiration_date_change;' );

// // View the queries.
// function exclude_archive_public_tag( $query ) {
//   error_log('Query: ' . print_r($query, true));
// }
// add_action('pre_get_posts', 'exclude_archive_public_tag');

// // A method for echoing content to the footer, used to debug.
// add_action('wp_footer', function() {
// });

// // Log all available [PMPro] hooks.
// add_action('all', function ($hook_name) {
//   if (strpos($hook_name, 'pmpro') !== false) {
//     error_log("Triggered Hook: " . $hook_name);
//   }
// });

// // View the SQL.
// add_filter( 'posts_request', function( $sql ) {
//     error_log( 'SQL Query: ' . $sql );
//     return $sql;
// });