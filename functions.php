<?php
// Enqueue the parent theme's stylesheet
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

// Triggers the creation of the event's RSVP page and causes the redirect.
function handle_rsvp_redirect() {
  if ( isset( $_GET['event'] ) && isset( $_GET['date'] ) ) {
    $event_title = sanitize_text_field( $_GET['event'] );
    $event_date = sanitize_text_field( $_GET['date'] );

    $page_slug = 'rsvp/' . $event_title . '/' . $event_date;
    $page = get_page_by_path( $page_slug );

    if ( $page && $page->post_type === 'page' ) {
      // Redirect only if the page is a valid page.
      wp_redirect( get_permalink( $page->ID ) );
      exit;
    }

    // Create a new RSVP page if it doesn't exist
    $page_id = create_event_rsvp_page( $event_title, $event_date );

    $post = get_post( $page_id );
    error_log( 'Post Type for RSVP Page: ' . $post->post_type );

    // Redirect to the RSVP page
    wp_redirect( get_permalink( $page_id ) );
    exit;
  }
}
// add_action( 'template_redirect', 'handle_rsvp_redirect' );

// Creates the RSVP parent page, the event child page, and the date grandchild page.
function create_event_rsvp_page( $event_title, $event_date ) {
  // Step 1: Create the 'RSVP' parent page if it doesn't exist.
  $rsvp_page = get_page_by_path( 'rsvp' );
  if ( ! $rsvp_page ) {
    $rsvp_page = wp_insert_post( array(
      'post_title'  => 'RSVP',
      'post_name'   => 'rsvp',
      'post_status' => 'publish',
      'post_type'   => 'page',
    ) );
  }
  $rsvp_parent_id = is_object( $rsvp_page ) ? $rsvp_page->ID : $rsvp_page;

  // Step 2: Create the 'event-title' child page under 'RSVP'
  $event_title_slug = sanitize_title( $event_title );
  $event_title_page = get_page_by_path( 'rsvp/' . $event_title_slug );
  $post_title = str_contains( $event_title, '-' ) ? ucwords( str_replace( '-', ' ', $event_title ) ) : $event_title;

  if ( ! $event_title_page ) {
    $event_title_page = wp_insert_post( array(
      'post_title'   => $post_title,
      'post_name'    => $event_title_slug,
      'post_status'  => 'publish',
      'post_type'    => 'page',
      'post_parent'  => $rsvp_parent_id,
    ) );
  }
  $event_title_id = is_object( $event_title_page ) ? $event_title_page->ID : $event_title_page;

  // Step 3: Create the 'event-date' sub-child page under 'event-title'.
  $event_date_slug = sanitize_title( $event_date );
  $event_date_page = get_page_by_path( 'rsvp/' . $event_title_slug . '/' . $event_date_slug );
  if ( ! $event_date_page ) {
    $event_date_page = wp_insert_post( array(
      'post_title'   => $post_title . ' | ' . $event_date,
      'post_name'    => $event_date_slug,
      'post_status'  => 'publish',
      'post_type'    => 'page',
      'post_parent'  => $event_title_id,
      'post_content' => 'RSVP for ' . $post_title . ' | ' . $event_date,
    ) );
  }
  $event_date_id = is_object( $event_date_page ) ? $event_date_page->ID : $event_date_page;

  // Step 4: Add the RSVP block to the 'event-date' page.
  $rsvp_id = create_event_rsvp( $event_date_id, $event_title, $event_date );
  if ( $rsvp_id ) {
    $current_content = get_post_field( 'post_content', $event_date_id );
    $rsvp_block_content = '<!-- wp:tribe/rsvp {"ticketId":' . $rsvp_id . '} /-->';    
    $new_content = $current_content . "\n" . $rsvp_block_content;
    
    wp_update_post( array(
      'ID'           => $event_date_id,
      'post_content' => $new_content,
    ) );
  }

  return $event_date_id;
}

// Creates and inserts the Event Tickets RSVP block. It is also supposed to configure and instantiate the block, but this feature works, ostensibly, only with Event Tickets Plus, which costs nearly $200/year. The error being returned is a 401, suggesting failed authorization (for the Event Tickets API). The block does appear on the page in the WP editor, however, though it is not configured.
function create_event_rsvp( $page_id, $event_title, $event_date ) {
  // Set up the RSVP post.
  $rsvp_data = array(
    'post_title'   => $event_title . ' on ' . $event_date . ' RSVP',
    'post_content' => 'Please RSVP for this event.',
    'post_status'  => 'publish',
    'post_type'    => 'tribe_rsvp', // Event Tickets RSVP post type
  );

  // Insert the RSVP post.
  $rsvp_id = wp_insert_post( $rsvp_data );

  // $post = get_post( $rsvp_id );
  // error_log( 'RSVP Post: ' . print_r( $post, true ) );

  if ( $rsvp_id ) {
    // Add meta fields required by the Event Tickets plugin
    update_post_meta( $rsvp_id, '_tribe_rsvp_form_page', $page_id );
    update_post_meta( $rsvp_id, '_tribe_rsvp_total_capacity', 30 );
    update_post_meta( $rsvp_id, '_tribe_rsvp_tickets_left', 30 );
    update_post_meta( $rsvp_id, '_tribe_rsvp_ticket_type', 'rsvp' );
    update_post_meta( $rsvp_id, '_tribe_tickets_enabled', true );
    update_post_meta( $rsvp_id, '_tribe_rsvp_event_date', $event_date );
  }

  return $rsvp_id;
}

// Ads the rewrite rule that handles the RSVP-page redirect.
function add_custom_rsvp_rewrite_rule() {
  add_rewrite_rule(
    '^rsvp/([^/]+)/([^/]+)/?$',
    'index.php?event=$matches[1]&date=$matches[2]',
    // 'index.php?pagename=rsvp/$matches[1]/$matches[2]&post_type=page',
  );
}
// add_action( 'init', 'add_custom_rsvp_rewrite_rule' );

// Adds the query vars "event" and "date" to the $wp_query->query_vars array.
function add_custom_rsvp_query_vars( $vars ) {
  $vars[] = 'event';
  $vars[] = 'date';
  return $vars;
}
// add_filter( 'query_vars', 'add_custom_rsvp_query_vars' );

// Ensures that the page being accessed is the specific event's RSVP page for a specific date.
add_action( 'pre_get_posts', function ( $query ) {
  if ( ! is_admin() && $query->is_main_query() && isset( $query->query_vars['event'], $query->query_vars['date'] ) ) {
    $page_slug = 'rsvp/' . $query->query_vars['event'] . '/' . $query->query_vars['date'];

    // Set the post type as page.
    if ( empty($query->query_vars['pagename']) || $query->query_vars['pagename'] !== $page_slug ) {
      $query->set( 'post_type', 'page' ); // Ensure it's a page.
      $query->set( 'pagename', $page_slug );
    }
    
    // error_log( 'is_page: ' . ( is_page() ? 'true' : 'false' ) );

    $page = get_page_by_path( $page_slug );
    if ( $page ) {
      error_log( 'Page exists: ' . print_r( $page, true ) );
      $query->set( 'pagename', $page_slug );
      global $post;
      $post = $page;
      setup_postdata( $post );
    } else {
      error_log( 'Page not found for slug: ' . $page_slug );
    }

    // error_log( 'Query Vars: ' . print_r( $query->query_vars, true ) );
  }
} );

// Force the event's RSVP page to be created using the single.php template.
function rsvp_template_redirect( $template ) {
  // Check if 'event' and 'date' query vars are set (i.e., it's an RSVP page).
  if ( get_query_var( 'event' ) && get_query_var( 'date' ) ) {
    return locate_template( 'single.php' );
  }
  return $template;
}
// add_filter( 'template_include', 'rsvp_template_redirect' );

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
  }
}
add_action('pmpro_after_checkout', 'update_order_status_to_pending', 10, 2);

// Add the link "Groups" to the right main menu when a user is logged in.
function add_dynamic_menu_link($items, $args) {
  if ($args->menu === 'right-main-menu' && is_user_logged_in()) {
    $user = wp_get_current_user();

    error_log('User: ' . print_r($user, true));

    $link = site_url('/members/' . $user->user_nicename . '/groups/');    
    $html = '<li id="menu-item-custom-group" class="menu-item menu-item-type-custom menu-item-object-custom"><a class="gp-menu-link" href="' . $link . '">Groups</a></li>';

    // Find the position of the "Friends" link and add the new link after it
    $friends_position = strpos($items, 'Friends</a></li>');
    if ($friends_position !== false) {
      $items = substr_replace($items, $html, $friends_position + 16, 0);
    }
  }
  return $items;
}
add_filter('wp_nav_menu_items', 'add_dynamic_menu_link', 10, 2);

// Restrict the "Members" and "Board Members" menu items and subitems to users with the corresponding membership level.
function restrict_menu_to_board_members($items) {
  // Track parent item keys to remove child items
  $parent_keys_to_remove = [];

  foreach ($items as $key => $item) {
    // Check for "Board Members"
    if (strpos($item->title, 'Board Members') !== false) {
      // Check if the user has the 'Board Member' level
      if (!pmpro_hasMembershipLevel('Board Member')) {
        $parent_keys_to_remove[] = $item->ID;
        unset($items[$key]);
      }
    }

    // Remove child items if the parent "Members" has been removed
    foreach ($items as $key => $item) {
      // Check if the item is a child of "Members" (i.e., check if its parent is in the removed list)
      if (in_array($item->menu_item_parent, $parent_keys_to_remove)) {
        unset($items[$key]);
      }
    }

    $parent_keys_to_remove = [];

    // Check for "Members"
    if (strpos($item->title, 'Members') !== false) {      
      // Check if the user has the 'Member' level
      if (!pmpro_hasMembershipLevel('Member')) {
        // Add parent item key to remove its children later
        $parent_keys_to_remove[] = $item->ID;
        unset($items[$key]);
      }
    }
  }

  // Remove child items if the parent "Members" has been removed
  foreach ($items as $key => $item) {
    // Check if the item is a child of "Members" (i.e., check if its parent is in the removed list)
    if (in_array($item->menu_item_parent, $parent_keys_to_remove)) {
      unset($items[$key]);
    }
  }

  return $items;
}
add_filter('wp_nav_menu_objects', 'restrict_menu_to_board_members', 10, 2);

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
  // Ensure the redirect URL is valid
  if (empty($redirect_to) || !is_string($redirect_to)) {
    return home_url(); // Default to home URL if invalid
  }
  return $redirect_to;
}, 10, 3);

// // A method for echoing content to the footer, used to debug.
// add_action('wp_footer', function() {
//   echo '<pre>' . home_url() . '</pre>';
// });
