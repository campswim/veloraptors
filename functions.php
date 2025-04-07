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
  if ( !is_admin() ) {
    wp_enqueue_script( 'custom-js', get_stylesheet_directory_uri() . '/custom.js', array('jquery'), '1.0', true );
    // Pass the current URI to the script
    wp_localize_script( 'custom-js', 'siteData', array(
        'pageUri' => trim( $_SERVER['REQUEST_URI'], '/' )
    ) );
  }
}
add_action('wp_enqueue_scripts', 'enqueue_child_theme_scripts', 20);

// Enqueue the child theme's scripts for the admin dashboard.
function enqueue_child_theme_admin_scripts() {
  if ( is_admin() ) {
    wp_enqueue_script('custom-admin-js', get_stylesheet_directory_uri() . '/custom-admin.js', array('jquery'), '1.0', true);

    // Optionally, localize admin script (if needed)
    wp_localize_script( 'custom-admin-js', 'adminData', array(
        'adminUri' => trim( $_SERVER['REQUEST_URI'], '/' )
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

// Change the status of a new PMPro order to "pending".
function update_order_status_to_pending( $user_id, $order ) {
  global $wpdb;
  
  // Mark the order as "pending" until the payment is received.
  $order->updateStatus('pending'); 
}
add_action('pmpro_after_checkout', 'update_order_status_to_pending', 10, 2);

// Activate or deactivate the membership based on the payment's status of "pending" or "success"; set the expiration date for one year.
function log_pmpro_update_order( $order ) {
  global $wpdb;

  if ( $order->status === 'success' || $order->status === 'review' ) {
    // Activate the membership.
    pmpro_changeMembershipLevel($order->membership_id, $order->user_id);

    // Trigger the BuddyPress welcome email.
    if ( !empty( $order->user_id ) ) {
      $user = get_userdata( $order->user_id );
      $user_name = isset( $user->display_name ) ? $user->display_name : (isset( $user->data->display_name ) ? $user->data->display_name : '');
      $user_email = isset( $user->user_email ) ? $user->user_email : (isset( $user->data->user_email ) ? $user->data->user_email : '');

      if ( $user_name && $user_email ) {
        if ( function_exists( 'bp_send_email' ) ) {
          // Get the BuddyPress welcome email.
          $email = bp_get_email( 'core-user-activation' );
          
          // Check if email object is valid.
          if ( !is_wp_error( $email ) ) {
            // Get the contact-us page ID to create a link in the template.
            $contact_us_page = get_page_by_path('contact-us');

            // If the page exists, get its URL
            $contact_us_url = $contact_us_page ? get_permalink($contact_us_page->ID) : ''; 
            
            // Prepare tokens for the email template.
            $tokens = array(
              'user.display_name' => $user_name,
              'site.name'         => get_bloginfo( 'name' ),
              'profile.url'       => bp_members_get_user_url( $order->user_id ),
              'lostpassword.url'  => wp_lostpassword_url(),
              'contactus.url'     => $contact_us_url
            );

            $sent = bp_send_email(
              'core-user-activation',  // Email template type.
              $user_email,        // Recipient email address.
              array(
                'tokens' => $tokens  // Tokens to replace in the email template.
              )
            );
          } 
        }
      }
    }

    // Get the expiration timestamp (one year from now).
    $expiration_timestamp = strtotime('+1 year');

    // Ensure the expiration timestamp is an integer.
    $expiration_timestamp = (int) $expiration_timestamp;

    // Format the expiration timestamp into MySQL-compatible datetime format.
    $expiration_date = date('Y-m-d H:i:s', (int) $expiration_timestamp);
          
    // Update the expiration date in the `pmpro_memberships_users` table.
    $updated_rows = $wpdb->update(
      $wpdb->prefix . 'pmpro_memberships_users',
      array('enddate' => $expiration_date),
      array('user_id' => $order->user_id),
      array('%s'),
      array('%d')
    );
  } elseif ( $order->status === 'pending' ) {
    // Set the user's status to 'review' (restricts access until payment is received).
    $wpdb->update(
      "{$wpdb->prefix}pmpro_memberships_users",
      array('status' => 'review'),
      array('user_id' => $order->user_id, 'membership_id' => $order->membership_id),
      array('%s'),
      array('%d', '%d')
    );
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

// Remove the guest's "Calendar" link from the left main menu and the mobile menu when a paying member is logged in. (Logged-in non-members should still see the link.)
function remove_calendar_link($items, $args) {
  if ( $args->menu === 'left-main-menu' && is_user_logged_in() && pmpro_hasMembershipLevel() ) {
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
  } elseif ( $args->menu === 'mobile-menu' && is_user_logged_in() && pmpro_hasMembershipLevel() ) {
    $dom = new DOMDocument();
    libxml_use_internal_errors(true); // Suppress warnings from HTML parsing
    $dom->loadHTML('<?xml encoding="utf-8" ?>' . $items);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $targetHref = '/events-public/';
    $links = $xpath->query("//a[contains(@href, '$targetHref')]");

    if ($links->length > 0) {
      $firstCalendar = $links->item(0);
      if ($firstCalendar) {
        $firstCalendar->parentNode->parentNode->removeChild($firstCalendar->parentNode);
      }
    }

    $items = preg_replace('/^<!DOCTYPE.+?>/', '', $dom->saveHTML());
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
  if ( is_user_logged_in() ) { // The user should have a registered account.
    global $wpdb;

    // Get the current date.
    $current_date = date( 'Y-m-d', time() );
    $new_order_startdate = '';
    $old_order_enddate = '';

    // Get the user ID to use to query pmpro membership info.
    $user_id = get_current_user_id();
    
    // Query the relevant pmpro user info.
    $member = $wpdb->get_results( $wpdb->prepare(
      "SELECT id, membership_id, status, startdate, enddate
      FROM {$wpdb->prefix}pmpro_memberships_users
      WHERE user_id = %d
      ORDER BY id DESC 
      LIMIT 2",
      $user_id
    ) );

    // Set the order start and end dates.
    if ( $member && is_array( $member ) ) {
      foreach( $member as $index => $object ) {
        if ( $index === 0 && isset( $object->startdate ) ) $new_order_startdate = $object->startdate;
        elseif ( $index === 1 && isset( $object->enddate ) ) $old_order_enddate = $object->enddate;
      }
    }

    if ( !$old_order_enddate ) { // New application: no previous order on file.
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
    } else { // Applicant has at least one previous order: this is either a renewal, a change in membership, or a change in membership status (e.g., the confirmation of a new membership being activated).
      // Create DateTime objects.
      $start = new DateTime($new_order_startdate);
      $end = new DateTime($old_order_enddate);

      // Calculate the difference.
      $interval = $start->diff($end);

      // If the new order's start date is fewer than thirty days from the old order's end date: renewal.
      if ( $interval->days <= 30 && $start <= $end ) {
        $payment_instructions = pmpro_getOption('instructions') ?? ''; 

        $replace_with = '
          <p>
            Thank you for submitting the application to renew your membership with us.
          </p>
          <p>
            Your account has been marked as pending while your payment is in transit. In the meantime, you may continue enjoying access to the benefits of membership.
          </p>
          <p>
            <strong>You may need to refresh your browser to restore access to the members-only sections of the site</strong>.
          </p>
          <p>
            Please remit the annual fee within seven calendar days of your membership\'s expiration to ensure that your membership remains active.
          </p>
        ';

        // Check if the current user has any membership: needs to stay active for renewals while payment is pending.
          if ( !pmpro_hasMembershipLevel() ) {
            $latest_id = $wpdb->get_var( $wpdb->prepare(
              "
              SELECT MAX(id)
              FROM {$wpdb->prefix}pmpro_memberships_users
              WHERE user_id = %d
              ",
              $user_id
            ) );

            // Update the membership status of the latest row if its current status is "review"
            $updated = $wpdb->update(
                $wpdb->prefix . 'pmpro_memberships_users', // Table name
                array(
                    'status' => 'active', // New status value
                ),
                array(
                    'id' => $latest_id, // Target the latest row based on ID
                    'status' => 'review', // Only update if the current status is 'review'
                ),
                array(
                    '%s' // Data format for 'status'
                ),
                array(
                    '%d', '%s' // Data formats for 'id' and 'status'
                )
            );
          }

        // Update the invoice to reflect a pending payment.
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
      } else { // A new membership has been activated.
        if ( strpos($message, 'active') !== false ) {
          $replace_with = '<p>Your application has been approved and your payment processed. Your membership is now active, and we welcome you to the club!</p>';

          // Replace the first <p> tag with the activation message
          $message = preg_replace('/<p>.*?<\/p>/', $replace_with, $message, 1);
          return $message;
        }
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

  // Check if the user has access to the current post/page.
  $has_access = isset( $post->ID ) ? pmpro_has_membership_access($post->ID) : null;

  if ( $has_access !== null && !$has_access ) {
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

// // Add the Zelle payment-option instructions to the checkout page. (NRC 20250407: not in use, becauuse payment instructions to be shown only after an application has been submitted.)
function add_payment_option_tabs_before_payment() {
  $payment_instructions = pmpro_getOption('instructions') ?? ''; 
  $check_payment_instructions = $payment_instructions ? explode( '<div class="pmpro_divider"></div>', $payment_instructions )[0] : '';
  $zelle_payment_instructions = $payment_instructions ? explode( '<div class="pmpro_divider"></div>', $payment_instructions )[1] : '';

  // Escape PHP variable for JavaScript
  $zelle_payment_instructions_escaped = json_encode(wp_kses_post($zelle_payment_instructions));
  ?>
    <script type="text/javascript">
      if (typeof zellePaymentInstructions === 'undefined') {
        var zellePaymentInstructions = <?php echo $zelle_payment_instructions_escaped; ?>;
      }

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
                    ${zellePaymentInstructions}
                  </div>
                </div>
                <div class="pmpro_divider"></div>
              </div>
            </div>
          `;

          // Remove zelle payment info from the check-info card.
          $('.payment-instructions_zelle').hide();

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
// add_action('wp_head', 'add_payment_option_tabs_before_payment');

// Hide payment instructions on the checkout page.
function hide_payment_instructions() {
  if ( is_page( 'membership-checkout' ) ) {

    // Replace payment instructions with note that instructions will be provided after submission of the application.
    ?>
      <script>
          const paymentInstructions = document.querySelector('.pmpro_check_instructions');
          if (paymentInstructions) {
            paymentInstructions.innerHTML = '<p>Payment instructions will be provided after you\'ve submitted your application.</p>';
          } 
      </script>      
    <?php
  }
}
add_action('wp_footer', 'hide_payment_instructions');

// Add the Zelle payment-option instructions header to the application-confirmation page (path: "/membership-checkout/membership-confirmation/?pmpro_level={level}"). (NRC 20250407: not in use.)
function add_payment_option_instructions_after_submission() {
  // Both the regular checkout and renewal processes redirect to this page.
  if ( is_page( 'membership-confirmation' ) ) { ?>
    <script type="text/javascript">
      jQuery(document).ready(function($) {
        if ($('.payment-instructions_zelle').length) {
          const paymentTitle = `
            <h3 class="pmpro_font-large">
						  Payment Instructions: Zelle
            </h3>
          `;

          $('.payment-instructions_zelle').before(paymentTitle);
        }
      });
    </script>
  <?php } 
}
// add_action('wp_head', 'add_payment_option_instructions_after_submission');

// Change the number of days before the expiration date for the email notification.
function my_pmpro_email_expiration_date_change( $days ) {
  return 15; //change this value to the number of days before the expiration date.
}
add_filter( 'pmpro_email_days_before_expiration', 'my_pmpro_email_expiration_date_change' );

// To ensure that the post's content is legible, add extra margin to the top of its container.
function add_custom_margin_to_post_content() {
  if ( is_single() && !is_page() ) { // Checks if it's a post, not a page.
    ?>
    <style>
      @media (width < 768px) {
        .gp-element-post-content {
          margin-top: 3rem;
        }
      }
    </style>
    <?php
  }
}
add_action('wp_head', 'add_custom_margin_to_post_content');

// // Test email functionality.
// function test_wp_mail_function() {
//   error_log('ian wuz ere.');

//   $to = 'support@smozhem.com';
//   $subject = 'Test Email from WordPress';
//   $message = 'This is a test email to verify that wp_mail() is working correctly.';
  
//   // Correctly structured headers array
//   $headers = array('Content-Type' => 'text/html; charset=UTF-8');

//   // Send the email using wp_mail
//   $mail_sent = wp_mail($to, $subject, $message, $headers);

//   // Log the result from wp_mail
//   if ($mail_sent) {
//     error_log('Test email sent successfully using wp_mail().');
//   } else {
//     error_log('Failed to send test email using wp_mail().');
//   }

//   // Use PHP mail() function directly
//   $headers_string = 'Content-Type: text/html; charset=UTF-8'; // For PHP mail()
//   if (mail($to, $subject, $message, $headers_string)) {
//     error_log('Email sent successfully using PHP mail().');
//   } else {
//     // Capture system error messages from PHP's mail function
//     $error_message = error_get_last();
//     if ($error_message) {
//       error_log('PHP mail() error: ' . print_r($error_message, true));
//     }
//   }
// }
// add_action('wp_footer', 'test_wp_mail_function');

// // Log emails being sent.
// add_filter('wp_mail', function($args) {
//   error_log( 'args from wp_mail hook: ' . print_r($args, true));
//   return $args;
// });

// add_action('wp_footer', function() {
//   remove_all_filters('wp_mail');
//   remove_all_filters('wp_mail_from');
//   remove_all_filters('wp_mail_from_name');

//   $to = 'support@smozhem.com';
//   $subject = 'Test Email from WordPress';
//   $message = 'This is a test email to verify that wp_mail() is working correctly.';
//   $headers = ['Content-Type: text/html; charset=UTF-8'];

//   $mail_sent = wp_mail($to, $subject, $message, $headers);

//   if ($mail_sent) {
//     error_log('Test email sent successfully using wp_mail().');
//   } else {
//     error_log('Failed to send test email using wp_mail().');
//   }
// });

// // Add a custom gateway to PMPro's "Gateway" dropdown. (NRC: not in use: when in use, will allow for the creation of mulitple active subscriptions for one member, which is undesirable.)
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
//   echo '<!-- Ian wuz ere -->';
//   error_log('Ian wuz ere');
// });

// // Log all available [PMPro] hooks.
// add_action('all', function ($hook_name) {
//   if (strpos($hook_name, 'pmpro') !== false) {
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

// add_action('wp_footer', function() {
//   echo '<script>alert("Test Inline Script!");</script>';
// });