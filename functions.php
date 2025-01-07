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
add_action( 'template_redirect', 'handle_rsvp_redirect' );

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
add_action( 'init', 'add_custom_rsvp_rewrite_rule' );

// Adds the query vars "event" and "date" to the $wp_query->query_vars array.
function add_custom_rsvp_query_vars( $vars ) {
  $vars[] = 'event';
  $vars[] = 'date';
  return $vars;
}
add_filter( 'query_vars', 'add_custom_rsvp_query_vars' );

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
add_filter( 'template_include', 'rsvp_template_redirect' );


// Shortcode to output the site URL dynamically.
function dynamic_home_url_shortcode() {  
  return esc_url( home_url() );
}
add_shortcode('home_url', 'dynamic_home_url_shortcode');

// add_action('wp_footer', function() {
//   echo '<pre>' . home_url() . '</pre>';
// });

if (!current_user_can('administrator')) {
  add_filter('show_admin_bar', '__return_false');
}
