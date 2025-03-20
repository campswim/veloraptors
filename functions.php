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
      "SELECT enddate
      FROM {$wpdb->prefix}pmpro_subscriptions
      WHERE user_id = %d
        AND status = 'cancelled'
      ORDER BY enddate DESC 
      LIMIT 1",
      $user_id
    ) );
    $subscription_enddate = $subscription && isset( $subscription->enddate ) ? explode( ' ', $subscription->enddate )[0] : 0;
    $order_date = isset( $order->timestamp ) ? date( 'Y-m-d', $order->timestamp ) : '';
    
    // If there's a cancelled subscription whose enddate is greater or equal to the order's start date, then we have a renewal: set the order's status to "Review" to maintain the user's access and don't change the subscription's status.
   if ( $subscription_enddate && $order_date && $subscription_enddate >= $order_date ) { // It's a renewal.
      $order->updateStatus('review'); // This way, the renewing member doesn't lose access to the members-only content of the site, while payment is in transit.
   } else { // It's a new subscription.
    $order->updateStatus('pending'); // Mark the order as "pending" until the payment is received.

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
  }
}
add_action('pmpro_update_order', 'log_pmpro_update_order', 10, 1);

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

// Remove the "Calendar" link from the left main menu when a member is logged in. (Logged-in nonmembers should still see the link.)
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

// Redirect the user to the homepage after logout, not the WP login page.
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

// Revise the registration confirmation message (after the renewal process, too).
function custom_pmpro_confirmation_message($message, $invoice) {
  // Debugging: Check how many times this runs.
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
          </p>
        ';

        // Replace the first <p> tag with the payment message
        $message = preg_replace('/<p>.*?<\/p>/', $replace_with, $message, 1);
        return $message;
      } 
    } else {
      global $wpdb;

      // Check if this checkout is for a renewal: get the last subscription's enddate and compare it to today.
      $user_id = get_current_user_id();
      $subscription = $wpdb->get_row( $wpdb->prepare(
        "SELECT enddate
        FROM {$wpdb->prefix}pmpro_subscriptions
        WHERE user_id = %d
          AND status = 'cancelled'
        ORDER BY enddate DESC 
        LIMIT 1",
        $user_id
      ) );
      $subscription_enddate = $subscription && isset( $subscription->enddate ) ? explode( ' ', $subscription->enddate )[0] : 0;
      $current_date = date( 'Y-m-d', time() );
      
      if ( $subscription_enddate >= $current_date ) { // This is a renewal.
        $replace_with = '<p>Thank you for submitting the application to renew your membership with us.</p><p>Your account has been marked as pending while your payment is in transit. In the meantime, you may continue enjoying access to the benefits of membership.</p><p>Please remit the annual fee via check or Zelle within seven calendar days to ensure that your membership remains active.</p><div class="pmpro_message pmpro_alert">We are waiting for your payment to be delivered.</div>';

        add_action('wp_footer', function() {
          ?>
            <script>
              document.addEventListener('DOMContentLoaded', () => {
                const observer = new MutationObserver(() => {
                  // Change "Paid" to "Pending" in the span element.
                  const targetSpan = document.querySelector('span.pmpro_list_item_value.pmpro_tag.pmpro_tag-success');
                  
                  if (targetSpan && targetSpan.textContent.trim() === 'Paid') {
                    targetSpan.className = 'pmpro_list_item_value pmpro_tag pmpro_tag-alert';
                    targetSpan.textContent = 'Pending';
                  }

                  // Change "paid" to "billed" in the #pmpro_order_single-items h3 element
                  const targetH3 = document.querySelector('#pmpro_order_single-items h3.pmpro_font-large');
                  if (targetH3 && targetH3.textContent.includes('paid')) {
                    targetH3.textContent = targetH3.textContent.replace('paid', 'billed');
                  }

                  // Disconnect the observer if both changes are made
                  if (targetSpan && targetH3) {
                    observer.disconnect();
                  }
                });

                // Observe DOM changes
                observer.observe(document.body, { childList: true, subtree: true });
              });
            </script>
          <?php
        });

        return $replace_with;
      } else if ( strpos($message, 'active') !== false ) {
        $replace_with = '<p>Your application has been approved and your payment processed. Your membership is now active, and we welcome you to the club!</p>';

        // Replace the first <p> tag with the activation message
        $message = preg_replace('/<p>.*?<\/p>/', $replace_with, $message, 1);
        return $message;
      }
    }
  }

  return $message; // If no conditions match, return the original message
}
add_filter('pmpro_confirmation_message', 'custom_pmpro_confirmation_message', 10, 2);

// Flush rewrite rules ONCE when switching themes.
function custom_rsvp_flush_rewrite() {
  custom_rsvp_post_type(); // Ensure the CPT is registered first.
  flush_rewrite_rules();
}
add_action('after_switch_theme', 'custom_rsvp_flush_rewrite');

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
          $('.tab-button').click(function(event) {
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
            $('.' + target).addClass('active');                
          });
        }
      });
    </script>
  <?php
}
add_action('wp_head', 'add_payment_option_tabs_before_payment');

// Add the Zelle payment-option instructions to the application-confirmation page (path: "/membership-checkout/membership-confirmation/?pmpro_level={level}").
function add_payment_option_instructions_after_submission() {
  // Both the regular checkout and renewal processes redirect to this page.
  if ( is_page( 'membership-confirmation' ) ) { ?>
    <script type="text/javascript">
      const zelleHtml = `
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
      const checkHtml = `
        <div class="pmpro_divider"></div>
        <div id="pmpro_order_single-instructions">
          <h3 class="pmpro_font-large">
            Payment Instructions: Check					
          </h3>
          <div class="pmpro_payment_instructions">
            <p>
              Please make a check in the amount of the membership fee payable to <u>VeloRaptors</u> and mail it to the following address:
            </p>
            <p class="payment-instructions">
              Kathy Tate<br>
              5333 Terra Granada Dr., #4B<br>
              Walnut Creek, CA 94595
            </p>
          </div>
        </div>
      `;

      jQuery(document).ready($ => {
        // Target the receipt after regular checkout.
        if ($('#pmpro_order_single-instructions').length) {

          // Add the Zelle instructions under the pay-by-check instructions.
          $('#pmpro_order_single-instructions').after(zelleHtml);
        } else { // On a renewal's confirmation page: add both sets of instructions.          
          if ($('.pmpro_card_content').length) {
            const html =  checkHtml + zelleHtml + '<div class="pmpro_divider"></div>';
  
            $('.pmpro_card_content').first().children().eq(1).after(html);
          }
        }
      });
    </script>
  <?php } 
}
add_action('wp_head', 'add_payment_option_instructions_after_submission');

// Change the number of days before the expiration date for the email notification.
function my_pmpro_email_expiration_date_change( $days ) {
  return 15; //change this value to the number of days before the expiration date.
}
add_filter( 'pmpro_email_days_before_expiration', 'my_pmpro_email_expiration_date_change' );

// // Add a custom gateway to PMPro's "Gateway" dropdown. (NRC: not in use: when in use, will allow for the creation of mulitple active subscriptions for one member, which undesirable.)
// require_once get_stylesheet_directory() . '/class.pmprogateway_zelle.php';

// // View the Elementor Items widget's tax query.
// function view_elementor_items_widget_query( $query ) {
//   error_log( 'the query is: ' . print_r( $query, true ) );
//   if ( $query && isset( $query->settings))
// }
// add_filter( 'elementor/element/ghostpool_items/_ghostpool_section_content_query/before_section_start', 'view_elementor_items_widget_query' );

// // View the queries.
// function exclude_archive_public_tag( $query ) {
//   error_log('Query: ' . print_r($query, true));
// }
// add_action('pre_get_posts', 'exclude_archive_public_tag');

// // A method for echoing content to the footer, used to debug.
// add_action('wp_footer', function() {
//   error_log( 'the current time: ' . time() );
//   error_log( 'the curernt time formatted: ' . date('Y-m-d', time() ));
// });

// // Log all available [PMPro] hooks.
// add_action('all', function ($hook_name) {
//   if (strpos($hook_name, 'elementor') !== false) {
//     error_log("Triggered Hook: " . $hook_name);
//   }
// });

// // View the SQL.
// add_filter( 'posts_request', function( $sql ) {
//   error_log( 'SQL Query: ' . $sql );
//   return $sql;
// });

// // View the Items loop's tax query.
// function exclude_archive_category_from_items_query( $args ) {
//   $is_ajax = defined( 'DOING_AJAX' ) && DOING_AJAX;

//   if ( !$is_ajax ) {

//     error_log( 'the tax query when NOT ajax: ' . print_r( $args['tax_query'], true ) );

//   } else {

//     error_log( 'the tax query when it is ajax: ' . print_r( $args['tax_query'], true ) );

//   }
//   return $args;
// }
// add_filter( 'ghostpool_items_query', 'exclude_archive_category_from_items_query' );
