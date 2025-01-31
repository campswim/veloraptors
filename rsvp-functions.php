<?php

// Create the RSVP table in the DB.
function create_rsvp_table() {
  global $wpdb;
  $table_name = $wpdb->prefix . 'rsvps';

  $charset_collate = $wpdb->get_charset_collate();

  $sql = "CREATE TABLE IF NOT EXISTS $table_name (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_title VARCHAR(255) NOT NULL,
    event_date DATE NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) $charset_collate;";

  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  dbDelta($sql);
}
add_action('after_setup_theme', 'create_rsvp_table');

// Redirect a user to the RSVP page of the event after clicking the event's RSVP link.
function handle_rsvp_redirect() {
  if ( isset( $_GET['event'] ) && isset( $_GET['date'] ) ) {
    $event_title = sanitize_text_field( $_GET['event'] );
    $event_date = sanitize_text_field( $_GET['date'] );

    $page_slug = 'rsvp/' . $event_title . '/' . $event_date;
    $page = get_page_by_path( $page_slug );

    if ( $page && $page->post_type === 'page' ) {
      // Redirect to the existing page.
      wp_redirect( get_permalink( $page->ID ) );
      exit;
    }

    // Create the RSVP page if it doesn't exist.
    $page_id = create_event_rsvp_page( $event_title, $event_date );

    // Redirect to the new RSVP page.
    wp_redirect( get_permalink( $page_id ) );
    exit;
  }
}
add_action( 'template_redirect', 'handle_rsvp_redirect' );

// Use a shortcode to ensure that the RSVP form is new for each user.
function rsvp_form_shortcode( $atts ) {
  $atts = shortcode_atts( array(
    'event_title' => '',
    'event_date'  => '',
  ), $atts );

  return generate_rsvp_form( $atts['event_title'], $atts['event_date'] );
}
add_shortcode( 'rsvp_form', 'rsvp_form_shortcode' );

// Add the rsvp to the database and reload the page to show a confirmation message and a list of attendees.
function handle_rsvp_submission() {
  if ( isset( $_POST['submit_rsvp'] ) ) {
    // Sanitize the inputs
    $name = sanitize_text_field( $_POST['rsvp_name'] );
    $email = sanitize_email( $_POST['rsvp_email'] );
    $event_title = sanitize_text_field( $_POST['event_title'] );
    $event_date = sanitize_text_field( $_POST['event_date'] );

    // Insert the data into the custom table
    global $wpdb;
    $table_name = $wpdb->prefix . 'rsvps';
    
    $wpdb->insert( $table_name, array(
      'event_title' => $event_title,
      'event_date'  => $event_date,
      'name'        => $name,
      'email'       => $email,
    ) );

    // Reload the event's page on submission.
    wp_redirect( get_permalink());
    exit;
  }
}
add_action( 'wp_head', 'handle_rsvp_submission' );

// Add a custom rewrite rule to handle the RSVP page's path structure.
function add_custom_rsvp_rewrite_rule() {
  add_rewrite_rule(
    '^rsvp/([^/]+)/([^/]+)/?$',
    'index.php?event=$matches[1]&date=$matches[2]',
  );
}
add_action( 'init', 'add_custom_rsvp_rewrite_rule' );

// Add the custom query variables to the query_vars array.
function add_custom_rsvp_query_vars( $vars ) {
  $vars[] = 'event';
  $vars[] = 'date';
  return $vars;
}
add_filter( 'query_vars', 'add_custom_rsvp_query_vars' );

// Get the event's page.
function locate_event_date( $query ) {
  if ( ! is_admin() && $query->is_main_query() && isset( $query->query_vars['event'], $query->query_vars['date'] ) ) {
    $page_slug = 'rsvp/' . $query->query_vars['event'] . '/' . $query->query_vars['date'];

    // Set the post type as page.
    if ( empty($query->query_vars['pagename']) || $query->query_vars['pagename'] !== $page_slug ) {
      $query->set( 'post_type', 'page' );
      $query->set( 'pagename', $page_slug );
    }

    $page = get_page_by_path( $page_slug );
    if ( $page ) {
      $query->set( 'pagename', $page_slug );
      global $post;
      $post = $page;
      setup_postdata( $post );
    }
  }
}
add_action( 'pre_get_posts', 'locate_event_date' );

// Create the RSVP page, child page, and grandchild page. (This structure is being used so that each event may have its own page. The URI of each event's page follows this pattern: /rsvp/{event-title}/{event-date}/.)
function create_event_rsvp_page( $event_title, $event_date ) {
  // Create a parent "RSVP" page if it doesn't exist.
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

  // Create the event page.
  $event_title_slug = sanitize_title( $event_title );
  $event_title_page = get_page_by_path( 'rsvp/' . $event_title_slug );
  if ( ! $event_title_page ) {
    $event_title_page = wp_insert_post( array(
      'post_title'   => $event_title,
      'post_name'    => $event_title_slug,
      'post_status'  => 'publish',
      'post_type'    => 'page',
      'post_parent'  => $rsvp_parent_id,
    ) );
  }
  $event_title_id = is_object( $event_title_page ) ? $event_title_page->ID : $event_title_page;

  // Create the event date page.
  $event_date_slug = sanitize_title( $event_date );
  $event_date_page = get_page_by_path( 'rsvp/' . $event_title_slug . '/' . $event_date_slug );
  if ( ! $event_date_page ) {
    $event_date_page = wp_insert_post( array(
      'post_title'   => $event_title . ' on ' . $event_date,
      'post_name'    => $event_date_slug,
      'post_status'  => 'publish',
      'post_type'    => 'page',
      'post_parent'  => $event_title_id,
      'post_content' => 'RSVP for ' . $event_title . ' | ' . $event_date . "\n\n" .
                        '[rsvp_form event_title="' . esc_attr( $event_title ) . '" event_date="' . esc_attr( $event_date ) . '"]',    
    ) );
  }

  return is_object( $event_date_page ) ? $event_date_page->ID : $event_date_page;
}

// Generate the RSVP form.
function generate_rsvp_form( $event_title, $event_date ) {
  global $wpdb;

  // Fetch the current user to dynamically populate the name and email fields.
  $current_user = wp_get_current_user();
  $user_name = is_user_logged_in() ? esc_attr( $current_user->display_name ) : '';
  $user_email = is_user_logged_in() ? esc_attr( $current_user->user_email ) : '';
  $query_rsvps = "SELECT name, email FROM wp_rsvps WHERE event_title = %s AND event_date = %s";

  // Fetch existing RSVPs for the event.
  $rsvps = $wpdb->get_results( $wpdb->prepare(
    $query_rsvps,
    $event_title, $event_date
  ) );

  // Check if the user's email already exists for this event and date.
  $existing_rsvp = 0;
  if ( $user_email ) {
    $existing_rsvp = $wpdb->get_var( $wpdb->prepare(
      "SELECT COUNT(*) FROM wp_rsvps WHERE email = %s AND event_title = %s AND event_date = %s",
      $user_email, $event_title, $event_date
    ) );
  }

  ob_start();

  // If the email exists, show a message and do not insert the RSVP
  if ( $existing_rsvp > 0 ) {
    echo '<p>Thanks for signing up to attend this event.</p><p>Should you have any questions, please <a href="/contact-us/">reach out</a>.</p>';
  } else { ?>
    <form method="POST" action="">
      <input type="hidden" name="event_title" value="<?php echo esc_attr( $event_title ); ?>">
      <input type="hidden" name="event_date" value="<?php echo esc_attr( $event_date ); ?>">
  
      <label for="rsvp_name">Your Name</label>
      <input type="text" name="rsvp_name" id="rsvp_name" value="<?php echo $user_name; ?>" required>
  
      <label for="rsvp_email">Your Email</label>
      <input type="email" name="rsvp_email" id="rsvp_email" value="<?php echo $user_email; ?>" required>
  
      <input type="submit" name="submit_rsvp" value="RSVP">
    </form>
  <?php }
  ?>
    <h3>RSVP'd Attendees</h3>
    <ul>
      <?php if ( $rsvps ) : ?>
        <?php foreach ( $rsvps as $rsvp ) : ?>
          <li><?php echo esc_html( $rsvp->name ); ?></li>
        <?php endforeach; ?>
      <?php else : ?>
        <li>No RSVPs yet.</li>
      <?php endif; ?>
    </ul>
  <?php

  return ob_get_clean();
}

function add_rsvp_submenu_link( $items, $args ) {
  // Check if we're working with the correct menu.
  if ( isset( $args->menu ) && 'left-main-menu' === $args->menu && is_user_logged_in() ) {
    // Load the menu items into a DOMDocument object
    $dom = new DOMDocument();

    // Suppress warnings from invalid HTML in the items string.
    @$dom->loadHTML( '<?xml encoding="UTF-8">' . $items );
    
    // Find the "Members" menu item.
    $xpath = new DOMXPath( $dom );
    $members_link = $xpath->query("//a[contains(text(), 'Members')]");

    if ( $members_link->length > 0 ) {
      // Create the new submenu for RSVP pages.
      $rsvp_base_url = site_url( '/rsvp/' );
      $rsvp_submenu = $dom->createElement('li');
      $rsvp_submenu->setAttribute('id', 'menu-item-rsvp');
      $rsvp_submenu->setAttribute('class', 'menu-item menu-item-has-children');
      $rsvp_link = $dom->createElement('a', 'RSVP');
      $rsvp_link->setAttribute('href', '#');
      $rsvp_submenu->appendChild($rsvp_link);
      $rsvp_submenu_ul = $dom->createElement('ul');
      $rsvp_submenu_ul->setAttribute('class', 'sub-menu');

      // Query for all pages under 'rsvp' (this will include event titles).
      $event_pages = get_pages( array(
        'parent' => get_page_by_path( 'rsvp' )->ID,
        'post_type' => 'page',
        'sort_column' => 'menu_order',
        'sort_order' => 'asc',
      ) );

    
      error_log( 'rsvp pages: ' . print_r( $rsvp_pages, true));

      $events = [];
      $current_event_title = '';

      // Loop through all RSVP pages and organize them by event
      foreach ( $rsvp_pages as $page ) {
        $slug = $page->post_name;
        $event_title = $page->post_title;
        $event_url = get_permalink( $page->ID );

        error_log( 'event url: ' . $event_url );

        // If it's an event title page (parent page)
        if ( substr_count( $slug, '/' ) === 1 ) {
          // Set the current event title
          $current_event_title = $event_title;
          $events[$current_event_title] = [];
        }

        // If it's an event date page (child page)
        if ( substr_count( $slug, '/' ) === 2 ) {
          // Add the event date as a sub-menu item
          $event_item = $dom->createElement('li');
          $event_link = $dom->createElement('a', $event_title);
          $event_link->setAttribute('href', $event_url);
          $event_item->appendChild($event_link);
          $rsvp_submenu_ul->appendChild($event_item);
        }
      }

      // Append the RSVP submenu to the "Members" item
      $members_item = $members_link->item(0)->parentNode->parentNode;
      $members_item->appendChild($rsvp_submenu);
      $rsvp_submenu->appendChild($rsvp_submenu_ul);
      
      // Save the modified HTML back to a string
      $items = $dom->saveHTML();
    }
  }

  return $items;
}
// add_filter( 'wp_nav_menu_items', 'add_rsvp_submenu_link', 10, 2 );

// Scehdule a cleanup of old RSVP events.
function schedule_rsvp_cleanup() {
  if ( !wp_next_scheduled( 'rsvp_cleanup_event' ) ) {
    wp_schedule_event( time(), 'daily', 'rsvp_cleanup_event' );
  }
}
add_action( 'wp', 'schedule_rsvp_cleanup' );

// Remove old events from the db.
function cleanup_old_rsvps() {
  global $wpdb;
  $table_name = $wpdb->prefix . 'rsvps';

  // Get today's date in YYYY-MM-DD format.
  $today = date('Y-m-d');

  // Get all event titles and event dates that need to be reomved.
  $events_to_remove = $wpdb->get_results(
    $wpdb->prepare(
      "SELECT DISTINCT event_title, event_date FROM $table_name WHERE event_date < %s",
      $today
    )
  );

  if ( !empty( $events_to_remove ) ) {
    foreach( $events_to_remove as $event ) {
      $event_title_slug = sanitize_title( $event->event_title );
      $event_date_slug = sanitize_title( $event->event_date );

      // Find the event's RSVP page.
      $event_page = get_page_by_path( "rsvp/$event_title_slug/$event_date_slug" );
      if ( $event_page ) wp_trash_post( $event_page->ID );
    }
  }

  // Run the DELETE query.
  $wpdb->query(
    $wpdb->prepare( "DELETE FROM $table_name WHERE event_date < %s", $today ) );
}
add_action( 'rsvp_cleanup_event', 'cleanup_old_rsvps' );
