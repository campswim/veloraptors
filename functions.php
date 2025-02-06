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
  wp_enqueue_script('custom-js', get_stylesheet_directory_uri() . '/custom.js', array('jquery'), '1.0', true);

  // Pass the current URI to the script
  wp_localize_script('custom-js', 'siteData', array(
      'pageUri' => trim($_SERVER['REQUEST_URI'], '/')
  ));
}
add_action('wp_enqueue_scripts', 'enqueue_child_theme_scripts');

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

// Change the status of a new PMPro order to "pending" if the payment method is "check".
function update_order_status_to_pending($user_id, $order) {
  global $wpdb;

  // Check if the payment method is 'check'
  if ($order->payment_type === 'Check') {
    // Update the status to 'pending' in the database
    $wpdb->update(
        "{$wpdb->prefix}pmpro_membership_orders", // Table name
        array( 'status' => 'pending' ), // Data to update
        array( 'id' => $order->id ), // Where clause
        array( '%s' ), // Format for 'status'
        array( '%d' )  // Format for 'id'
    );

    // Remove the membership level so it's not active yet.
    pmpro_cancelMembershipLevel($order->membership_id, $user_id);
  }
}
add_action('pmpro_after_checkout', 'update_order_status_to_pending', 10, 2);

// Activate or deactivate the membership based on the payment's status of "pending" or "success"; set the expiration date for one year.
function log_pmpro_update_order($order) {
  if ($order->gateway == 'check') {
    if ($order->status === 'success') {
      pmpro_changeMembershipLevel($order->membership_id, $order->user_id); // Activate the membership.

      // Get the expiration timestamp (one year from now)
      $expiration_timestamp = strtotime('+1 year'); // Get the Unix timestamp

      // Ensure the expiration timestamp is an integer (just for extra safety)
      $expiration_timestamp = (int) $expiration_timestamp;

      // Format the expiration timestamp into MySQL-compatible datetime format
      $expiration_date = date('Y-m-d H:i:s', (int) $expiration_timestamp); // Force the timestamp to be treated as an integer
      
      update_user_meta($order->user_id, 'pmpro_membership_expires', $expiration_date); // Set the expiration date for the membership.

      // Now update the user's membership enddate directly in the database
      global $wpdb;

      // Update the expiration date in the `pmpro_memberships_users` table
      $updated_rows = $wpdb->update(
        $wpdb->prefix . 'pmpro_memberships_users',
        array('enddate' => $expiration_date),  // Set the expiration (enddate)
        array('user_id' => $order->user_id),   // Target the user by user_id
        array('%s'),                           // Format for the enddate (datetime)
        array('%d')                            // Format for the user_id
      );
    } else if ($order->status === 'pending') {
      // Remove the membership level so it's not active yet.
      pmpro_cancelMembershipLevel($order->membership_id, $order->user_id);
    }
  }
}
add_action('pmpro_update_order', 'log_pmpro_update_order', 10, 1);

// Add the link "Groups" to the right main menu when a user is logged in and has a current membership.
function add_dynamic_menu_link($items, $args) {
  if ( ( $args->menu === 'right-main-menu' || $args->menu === 'mobile-menu' ) && is_user_logged_in() && pmpro_hasMembershipLevel()) {
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
add_action('init', function () {
  // Check if we're handling a form submission with the problematic key
  if (
      isset($_POST['action']) &&
      $_POST['action'] === 'form_processing' &&
      isset($_POST['login_member_redirect'])
  ) {
      // Fix the redirect URL
      $_POST['login_member_redirect'] = home_url();
  }
});
add_filter('logout_redirect', function ($redirect_to, $requested_redirect_to, $user) {
  // Ensure the redirect URL is valid.
  if (empty($redirect_to) || !is_string($redirect_to)) {
    return home_url(); // Default to home URL if invalid
  }
  return $redirect_to;
}, 10, 3);

// Revise the registration confirmation message.
function custom_pmpro_confirmation_message($message, $invoice) {
  if (strpos($message, 'payment') !== false) {
    $replace_with = '<p>Thank you for your application to join the VeloRaptors Cycling Club. Your membership will be activated once it has been approved and your payment processed.</p>';
  } elseif (strpos($message, 'active') !== false) {
    $replace_with = '<p>Your application has been approved and your payment processed. Your membership is now active, and we welcome to the club!</p>';
  }
  
  $message = preg_replace('/<p>.*?<\/p>/', $replace_with, $message, 1);
  return $message;
}
add_filter('pmpro_confirmation_message', 'custom_pmpro_confirmation_message', 10, 2);

// Run after deleting the RSVP page to flush the rewrite rules.
function flush_rewrite_rules_after_deleting_rsvp() {
  flush_rewrite_rules();
}
add_action( 'wp_trash_post', 'flush_rewrite_rules_after_deleting_rsvp' );

// Add a custom post type for RSVP pages.
function custom_rsvp_post_type() {
  $labels = array(
    'name'               => 'RSVP Pages',
    'singular_name'      => 'RSVP Page',
    'menu_name'          => 'RSVP Pages',
    'name_admin_bar'     => 'RSVP Page',
    'add_new'            => 'Add New',
    'add_new_item'       => 'Add New RSVP Page',
    'new_item'           => 'New RSVP Page',
    'edit_item'          => 'Edit RSVP Page',
    'view_item'          => 'View RSVP Page',
    // 'all_items'          => 'All RSVP Pages',
    'search_items'       => 'Search RSVP Pages',
    'not_found'          => 'No RSVP pages found.',
    'not_found_in_trash' => 'No RSVP pages found in Trash.'
  );

  $args = array(
    'labels'             => $labels,
    'public'             => true,
    'has_archive'        => false,
    'show_in_menu'       => true,
    'menu_position'      => 20,
    'menu_icon'          => 'dashicons-calendar-alt', // WordPress Dashicon for event-related icons
    'supports'           => array('title', 'editor', 'custom-fields', 'thumbnail'),
    // 'rewrite'            => array(
    //   'slug' => 'rsvp', // Base slug for the custom post type
    //   'with_front' => false, // Don't use the "front" part of the permalink
    //   'hierarchical' => true, // Allow nested slugs like /rsvp/{event-title}/{event-date},
    //   'ep_mask' => EP_PERMALINK,
    // ),
    // 'rewrite' => true,
    'capability_type'    => 'post',
    'hierarchical'       => true, // Important for nested pages
    // 'publicly_queryable' => true,
  );

  register_post_type('rsvp', $args);
  flush_rewrite_rules();
}
add_action('init', 'custom_rsvp_post_type');

// Flush rewrite rules ONCE when switching themes
function custom_rsvp_flush_rewrite() {
  custom_rsvp_post_type(); // Ensure the CPT is registered first
  flush_rewrite_rules();
}
add_action('after_switch_theme', 'custom_rsvp_flush_rewrite');

// // A method for echoing content to the footer, used to debug.
// add_action('wp_footer', function() {
//   echo '<pre>' . home_url() . '</pre>';
// });

// Log all available PMPro hooks.
// add_action('all', function ($hook_name) {
//   if (strpos($hook_name, 'pmpro') !== false || strpos($hook_name, 'save') !== false) {
//     error_log("Triggered Hook: " . $hook_name);
//   }
// });
