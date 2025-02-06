<?php
// Import the utility functions.
require_once get_stylesheet_directory() . '/utility-functions.php';

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
    $page = get_posts( array(
      'name' => $page_slug,
      'post_type' => 'rsvp',
      'post_status' => 'publish',
      'numberposts' => 1,
    ) );

    if ( empty( $page ) ) {
      // Create the RSVP page if it doesn't exist.
      $page_id = create_event_rsvp_page( $event_title, $event_date );

      // Get the permalink of the newly created page
      $redirect_url = get_permalink( $page_id );
    } else {
      // If the page exists, use the existing permalink
      $redirect_url = get_permalink( $page[0]->ID );
    }

    // Redirect to the correct page
    wp_redirect( $redirect_url );
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

// Add the RSVP to the database and reload the page to show a confirmation message and a list of attendees.
function handle_rsvp_submission() {
  if ( isset( $_POST['submit_rsvp'] ) ) {
    global $wpdb;

    // Sanitize the inputs.
    $name = sanitize_text_field( $_POST['rsvp_name'] );
    $email = sanitize_email( $_POST['rsvp_email'] );
    $event_title = sanitize_text_field( $_POST['event_title'] );
    $event_date = sanitize_text_field( $_POST['event_date'] );

    // Insert the data into the custom table.
    $table_name = $wpdb->prefix . 'rsvps';

    // Check if an RSVP already exists for the same email & event_date.
    $existing_rsvp = $wpdb->get_var( $wpdb->prepare(
      "SELECT COUNT(*) FROM $table_name WHERE email = %s AND event_title = %s AND event_date = %s",
      $email, $event_title, $event_date
    ));

    if ( !$existing_rsvp ) {
      $wpdb->insert( $table_name, array(
        'event_title' => $event_title,
        'event_date'  => $event_date,
        'name'        => $name,
        'email'       => $email,
      ) );
    }

    // After inserting the RSVP data
    $redirect_url = add_query_arg( array(
      'event_title' => urlencode( $event_title ),
      'event_date'  => urlencode( $event_date ),
    ), get_permalink() );

    // Reload the event's page on submission.
    wp_redirect( $redirect_url );
    exit;
  }
}
add_action( 'wp_head', 'handle_rsvp_submission' );

// Set the localStorage item after the form submission and redirect, in order to capture RSVP info of public (non-logged-in) visitors.
function add_rsvp_local_storage() {
  // Get the event title and date from the URL query.
  $event_title = isset($_GET['event_title']) ? sanitize_text_field( $_GET['event_title'] ) : '';
  $event_date = isset($_GET['event_date']) ? sanitize_text_field( $_GET['event_date'] ) : '';
  ?>
    <script type="text/javascript">
      const storageKey = '<?php echo esc_js( $event_title ); ?>';
      let rsvpData = [];
      
      if (storageKey) {
        const extantRsvps = localStorage.getItem('rsvp');
        
        if (!extantRsvps) {
          const entry = { 
            [storageKey]: [
              { '<?php echo esc_js( $event_date ); ?>': true }
            ]
          };
          
          rsvpData.push(entry);
        } else {
          const eventRsvps = JSON.parse(extantRsvps); // An array.

          for (const rsvp of eventRsvps) {
            const event = Object.keys(rsvp)[0];

            if (event === '<?php echo esc_js( $event_title ); ?>') { // Repeated event.
              const newEntry = { '<?php echo esc_js( $event_date ); ?>': true };

              rsvp[event].push(newEntry);
              break;
            } else { // New event.
              const newEntry = {
                [storageKey]: [
                  { '<?php echo esc_js( $event_date ); ?>': true }
                ]
              };
              
              eventRsvps.push(newEntry);
              break;
            }
          }

          rsvpData = eventRsvps;
        }
      }
      
      // Save to localStorage.
      if (rsvpData.length > 0) localStorage.setItem('rsvp', JSON.stringify(rsvpData));
            
      // Remove query params from the URL without reloading.
      const newUrl = window.location.pathname;
      window.history.replaceState({}, document.title, newUrl);
    </script>
  <?php
}
add_action( 'wp_footer', 'add_rsvp_local_storage' );

// Clear local storage of RSVPs that have expired, i.e., the day after the event.
function remove_rsvp_local_storage() {
  if ( !is_user_logged_in() ) : ?>
    <script>
      const rsvps = localStorage.getItem('rsvp');
      const rsvpsParsed = rsvps ? JSON.parse(rsvps) : null; // An array.
      const today = new Date();
      today.setHours(0, 0, 0, 0);

      if (rsvpsParsed && rsvpsParsed.length > 0) {
        const updatedRsvps = rsvpsParsed.map(event => {
          const eventName = Object.keys(event)[0];
          const details = Object.values(event)[0]; // An array of date objects.

          // Filter out expired dates.
          const updatedDetails = details.filter(dateObj => {
            const date = Object.keys(dateObj)[0];
            const dateObject = new Date(date);
            dateObject.setHours(0, 0, 0, 0);

            // Keep only valid (future) dates.
            return dateObject >= today;
          });

          return updatedDetails.length > 0 ? { [eventName]: updatedDetails } : null;
        }).filter(event => event !== null);

        localStorage.setItem('rsvp', JSON.stringify(updatedRsvps));
      }
      
    </script>
  <?php endif;
}
add_action( 'wp_footer', 'remove_rsvp_local_storage');

// Clear local storage of public RSVPs that are deleted by Admin.

// Add the RSVP URL's custom query variables to the query_vars array.
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
      $query->set( 'post_type', 'rsvp' );
      $query->set( 'pagename', $page_slug );
    }

    $page = get_posts( array(
      'name' => $page_slug,
      'post_type' => 'rsvp',
      'post_status' => 'publish',
      'numberposts' => 1,
    ) );

    if ( $page ) {
      $query->set( 'pagename', $page_slug );
      global $post;
      $post = $page;
      setup_postdata( $post );
    }
  }
}
add_action( 'pre_get_posts', 'locate_event_date' );

// Add the /RSVP/{event-title}/ path to the Members menu dropdown.
function add_rsvp_submenu_link( $items, $args ) {
  global $wpdb;

  // Grab all custom posts of type "rsvp."
  $rsvp_posts = get_posts(array(
    'post_type'      => 'rsvp',
    'posts_per_page' => 1,
  ));

  // Add the "RSVPs" link if there are any such pages.
  if (!empty($rsvp_posts)) {
    $all_child_pages = [];

    // Check the DB for ANY extant AND future RSVPs, including today's date: if none, don't render the RSVP link.
    $today = date('Y-m-d');
    $table_name = $wpdb->prefix . 'rsvps';
    $extant_rsvps = $wpdb->get_results(
      $wpdb->prepare(
        "SELECT event_date FROM $table_name WHERE event_date >= %s",
        $today
      )
    );

    if ( count( $extant_rsvps ) > 0 && isset( $args->menu ) && 'left-main-menu' === $args->menu && is_user_logged_in() ) {
      $dom = new DOMDocument();
      @$dom->loadHTML( '<?xml encoding="UTF-8">' . $items );

      $xpath = new DOMXPath( $dom );
      $members_item = $xpath->query("//li[contains(@class, 'menu-item') and a[contains(text(), 'Members')]]")->item(0);

      if ( $members_item ) {
        // Find or create the Members submenu.
        $members_submenu = $xpath->query("./ul[@class='sub-menu']", $members_item)->item(0);
        
        if ( !$members_submenu ) {
          $members_submenu = $dom->createElement('ul');
          $members_submenu->setAttribute('class', 'sub-menu');
          $members_submenu->setAttribute('style', 'margin-left: -7em;');
          $members_item->appendChild($members_submenu);
        }

        // Get the Calendar link from the Members link's submenu.
        $calendar_item = $xpath->query(".//li[a[contains(text(), 'Calendar')]]", $members_submenu)->item(0);

        // Create RSVP menu item.
        $rsvp_submenu = $dom->createElement('li');
        $rsvp_submenu->setAttribute('id', 'menu-item-rsvp');
        $rsvp_submenu->setAttribute('class', 'menu-item menu-item-has-children');
        
        $rsvp_link = $dom->createElement('a', 'RSVPs');
        $rsvp_link->setAttribute('class', 'gp-menu-link');
        $rsvp_link->setAttribute('href', '#');
        $rsvp_submenu->appendChild($rsvp_link);

        $rsvp_link_dropdown_icon = $dom->createElement('span');
        $rsvp_link_dropdown_icon->setAttribute('class', 'gp-dropdown-menu-icon');
        $rsvp_link->appendChild($rsvp_link_dropdown_icon);

        $rsvp_submenu_ul = $dom->createElement('ul');
        $rsvp_submenu_ul->setAttribute('class', 'sub-menu');
        $rsvp_submenu_ul->setAttribute( 'style', 'margin-left: -7em;' );
        
        $all_child_pages = get_posts( array( 
          'post_type'   => 'rsvp',
          'post_status' => 'publish',
        ) );

        $event_items = [];

        foreach ( $all_child_pages as $child_page ) {
          $event_title = $child_page->post_title;
          $event_url = get_permalink( $child_page->ID );

          if ( !$child_page->post_parent ) { // Working with an event's main page, which will have children pages.
            $event_li = $dom->createElement('li');
            $event_li->setAttribute('class', 'menu-item menu-item-type-post_type menu-item-object-page menu-item-has-children');
            
            $event_link = $dom->createElement('a', $event_title);
            $event_link->setAttribute('class', 'gp-menu-link');
            $event_link->setAttribute('href', $event_url);
            $event_li->appendChild($event_link);

            // $event_ul = $dom->createElement('ul');
            // $event_ul->setAttribute('class', 'sub-menu');

            $event_items[$child_page->ID] = [
              'li' => $event_li,
              // 'ul' => $event_ul
            ];
          }

          // if ( isset($event_items[$child_page->post_parent]) ) {
          //   error_log("Adding Date Page: $event_title under Event: " . $event_items[$child_page->post_parent]['li']->textContent);

          //   $event_item = $dom->createElement('li');
          //   $event_link = $dom->createElement('a', $event_title);
          //   $event_link->setAttribute('href', $event_url);
          //   $event_item->appendChild($event_link);

          //   $event_items[$child_page->post_parent]['ul']->appendChild($event_item);
          // }
        }

        foreach ($event_items as $event) {
          // $event['li']->appendChild($event['ul']);
          $rsvp_submenu_ul->appendChild($event['li']);
        }

        // Append RSVP submenu inside the Members submenu.
        $rsvp_submenu->appendChild($rsvp_submenu_ul);
        
        // Insert RSVP menu item **after** the Members Calendar item.
          if ( $calendar_item && $calendar_item->parentNode ) {
            $calendar_item->parentNode->insertBefore($rsvp_submenu, $calendar_item->nextSibling);
          } else {
            // Fallback: Append at the end if Calendar link isn't found.
            $members_submenu->appendChild($rsvp_submenu);
          }      

        $items = $dom->saveHTML();
      }
    }
  }

  return $items;
}
add_filter( 'wp_nav_menu_items', 'add_rsvp_submenu_link', 10, 2 );

// Scehdule a cron-job cleanup of old RSVP events.
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
  $event_titles = [];

  // Get today's date in YYYY-MM-DD format.
  $today = date('Y-m-d');

  // Get all event titles and event dates that need to be reomved.
  $event_rsvp_pages_to_remove = $wpdb->get_results(
    $wpdb->prepare(
      "SELECT DISTINCT event_title, event_date FROM $table_name WHERE event_date < %s",
      $today
    )
  );

  if ( !empty( $event_rsvp_pages_to_remove ) ) {
    foreach( $event_rsvp_pages_to_remove as $event_rsvp ) {
      $event_title_slug = sanitize_title( $event_rsvp->event_title );
      $event_date_slug = sanitize_title( $event_rsvp->event_date );

      // Find the event's RSVP page.
      $event_rsvp_page = get_posts( array( 
        'name' => "rsvp/$event_title_slug/$event_date_slug",
        'post_type'   => 'rsvp',
        'post_status' => 'publish',
        'numberposts' => 1,   
      ) );
      
      if ( $event_rsvp_page ) {
        wp_trash_post( $event_rsvp_page->ID ); // Trash the page.
      }

      // Push the event title into the events_titles array to remove the event page when there are no RSVPs for it at all.
      $event_titles[] = $event_title_slug;
    }
  }

  // Run the DELETE query to remove the RSVPs from their db table.
  $wpdb->query( $wpdb->prepare( "DELETE FROM $table_name WHERE event_date < %s", $today ) );

  // Trash all event pages for which there are no RSVPs in the DB.
  if ( count( $event_titles) > 0 ) {
    foreach( $event_titles as $event_title) {
      $event_page = get_posts( array(
        'name' => "rsvp/$event_title",
        'post_type'   => 'rsvp', // Custom post type 'rsvp'
        'post_status' => 'publish', // Only get published posts
      ) );
      wp_trash_post( $event_page->ID );

    }
  }
}
add_action( 'rsvp_cleanup_event', 'cleanup_old_rsvps' );

// Remove event pages for which no dates have been RSVP'd yet.
function remove_empty_event_pages() {
  if (is_page()) {
    global $wpdb;
    global $post;

    $post_parent_id = $post->post_parent;
    $table_name = $wpdb->prefix . 'rsvps';
    $rsvp_page = get_page_by_path( 'rsvp' );
    $rsvp_page_id = !empty( $rsvp_page ) ? $rsvp_page->ID : null;
    $event_titles = [];

    if ( $rsvp_page_id ) {
      $child_pages = get_pages( array(
        'child_of' => $rsvp_page_id,
        'post_type' => 'rsvp',
        'sort_column' => 'menu_order',
        'sort_order' => 'asc',
      ) );

      if ( count( $child_pages ) > 0 ) {
        foreach( $child_pages as $page ) {
          $parent_id = $page->post_parent;

          // If the parent ID of the page is the RSVP page, we have an event page (not an RSVP date page).
          if ( $parent_id === $rsvp_page_id && $post_parent_id !== $page->ID ) {
            $event_titles[$page->ID] = $page->post_title;
          }
        }
      }
    }

    if ( count( $event_titles ) > 0 ) {
      // Prepare the SQL query to check which titles are missing
      $placeholders = implode(',', array_fill(0, count($event_titles), '%s'));
      $sql = "
        SELECT event_title
        FROM $table_name
        WHERE event_title IN ($placeholders)
      ";
      $query = $wpdb->prepare($sql, ...$event_titles);

      // Execute the query and get the results.
      $existing_titles = $wpdb->get_col($query);

      // Find the titles that don't exist in the table.
      $missing_titles = array_diff($event_titles, $existing_titles);

      // Output or process the missing titles.
      if ( !empty( $missing_titles ) ) {
        foreach( $missing_titles as $ID => $missing_title ) {
          wp_trash_post( $ID ); // Trash the page.
        }        
      } 
    }
  }
}
add_action('template_redirect', 'remove_empty_event_pages');

// Add links that redirect to each event page to the RSVP form for each date.
function add_rsvp_links_to_event_page( $content ) {
  if ( is_singular('rsvp') ) { // Checks for custom post types.
    global $post;

    // Check if this is an RSVP event page (i.e., it's a child of the RSVP parent).
    $rsvp_parent = get_page_by_path( 'rsvp' );

    if ( !$post->post_parent ) {

      // Get child RSVP date pages.
      $child_pages = get_pages( array(
        'child_of' => $post->ID,
        'post_type'   => 'rsvp',
        'post_status' => 'publish',
        'sort_column' => 'menu_order',
        'sort_order'  => 'asc',
      ) );

      if ( !empty( $child_pages ) ) {
        $content .= '<h3 class="rsvp-header">RSVP Dates:</h3><ul class="rsvp-dates-list-container">';
        foreach ( $child_pages as $child_page ) {          
          $content .= '<li class="rsvp-dates-list-item"><a href="' . get_permalink( $child_page->ID ) . '">' . esc_html( $child_page->post_name ) . '</a></li>';
        }
        $content .= '</ul>';
      }
    }
  }

  return $content;
}
add_filter( 'the_content', 'add_rsvp_links_to_event_page' );

// Create the RSVP page, child page, and grandchild page. (This structure is being used so that each event may have its own page. The URI of each event's page follows this pattern: /rsvp/{event-title}/{event-date}/.)
function create_event_rsvp_page( $event_title, $event_date ) {  
  // Create the event page.
  $event_title_slug = sanitize_title( $event_title );
  $event_title_formatted = format_event_titles( $event_title_slug );
  $event_title_page = get_posts( array(
    'name' => $event_title_slug,
    'post_type'   => 'rsvp',
    'post_status' => 'publish',
    'numberposts' => 1,
  ) );
  $event_title_id = null;

  if (!$event_title_page) {
    $event_title_id = wp_insert_post(array(
      'post_title'   => $event_title_formatted,
      'post_name'    => $event_title_slug,
      'post_status'  => 'publish',
      'post_type'    => 'rsvp',
      'post_parent'  => $rsvp_page_id,
    ));
  } else {
    $event_title_id = $event_title_page[0]->ID;
  }

  // Create the event date page.
  $event_date_slug = sanitize_title( $event_date );
  $event_date_formatted = format_event_date( $event_date_slug );
  $event_date_page = get_posts( array( 
    'name' => $event_date_slug,
    'post_type'   => 'rsvp',
    'post_status' => 'publish',
    'numberposts' => 1,
    'post_parent' => $event_title_id,
    ) );
  $event_date_id = null;

  if ( !$event_date_page ) {
    $event_date_id = wp_insert_post(array(
      'post_title'   => $event_title_formatted . ' | ' . $event_date_formatted,
      'post_name'    => $event_date_slug,
      'post_status'  => 'publish',
      'post_type'    => 'rsvp',
      'post_parent'  => $event_title_id,
      'post_content' => '<h3 class="rsvp-page-title">RSVP for "' . $event_title_formatted . '" on ' . $event_date_formatted . "</h3>\n\n" .
                        '[rsvp_form event_title="' . esc_attr($event_title) . '" event_date="' . esc_attr($event_date) . '"]',
    ));
  } else {
    $event_date_id = $event_date_page[0]->ID;
  }

  return $event_date_id;
}

// Generate the RSVP form and confirmation messages.
function generate_rsvp_form( $event_title, $event_date ) {
  global $wpdb;

  // Fetch the current user to dynamically populate the name and email fields.
  $current_user = wp_get_current_user();
  $user_name = is_user_logged_in() ? esc_attr( $current_user->display_name ) : '';
  $user_email = is_user_logged_in() ? esc_attr( $current_user->user_email ) : '';

  // Fetch existing RSVPs for the event.
  $query_rsvps = "SELECT id, name, email FROM wp_rsvps WHERE event_title = %s AND event_date = %s";
  $rsvps = $wpdb->get_results( $wpdb->prepare(
    $query_rsvps,
    $event_title, $event_date
  ) );

  // Check if the user's email already exists for this event and date.
  $existing_rsvp = false;
  $user_rsvp_id = null;
  if ( $user_email ) {
    $existing_rsvp = $wpdb->get_row( $wpdb->prepare(
      "SELECT id FROM wp_rsvps WHERE email = %s AND event_title = %s AND event_date = %s",
      $user_email, $event_title, $event_date
    ) );
    if ( $existing_rsvp ) {
      $user_rsvp_id = $existing_rsvp->id;
    }
  }

  // If the user isn't logged in but has already registered for this event (per an entry in the user's browser's local storage), don't render the RSVP form.
  if ( !is_user_logged_in() ) : ?>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const rsvpData = JSON.parse(localStorage.getItem('rsvp')); // An array, if exists.
        let breakOut = false;
        if (rsvpData && rsvpData.length > 0) {
          for (const rsvp of rsvpData) {
            const event = Object.keys(rsvp)[0];
            if (event === '<?php echo esc_js( $event_title ); ?>') {
              for (const details of rsvp[event]) {
                const date = Object.keys(details)[0];
                if (date === '<?php echo esc_js( $event_date ); ?>') {
                  const rsvpForm = document.getElementById('rsvp-form');
                  if (rsvpForm) {
                    const confirmationElement = document.createElement('div');
                    confirmationElement.innerHTML = '<p>Thanks for signing up to attend this event. Should you have any questions or need to cancel your RSVP, please <a href="/contact-us/">reach out</a>.</p>';
                    rsvpForm.style.display = 'none';
                    rsvpForm.insertAdjacentElement('afterend', confirmationElement);

                    breakOut = true;
                    break;
                  }
                }
              }
            }

            if (breakOut) break;
          }
        }
      });
    </script>
  <?php endif;

  ob_start();

  // If the email has already been used to register for the event, show a message and do not insert the RSVP form.
  if ( $user_rsvp_id ) {
    echo '<p>Thanks for signing up to attend this event. Should you have any questions, please <a href="/contact-us/">reach out</a>.</p>';
    echo '<p>If you\'d like to cancel your RSVP, <a href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=cancel_rsvp&rsvp_id=' . $user_rsvp_id ), 'cancel_rsvp_' . $user_rsvp_id ) ) . '" class="cancel-rsvp">click here.</a></p>';
  } elseif ( $existing_rsvp > 0 ) {
    echo '<p>Thanks for signing up to attend this event.</p><p>Should you have any questions, please <a href="/contact-us/">reach out</a>.</p>';
  } else { ?>
    <form class="rsvp-form" id="rsvp-form" method="POST" action="">
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
    <h4>List of Attendees</h4>
    <ul class="rsvp-attendees-list-container">
      <?php if ( $rsvps ) : ?>
        <?php foreach ( $rsvps as $rsvp ) : ?>
          <li class="rsvp-attendees-list-item">
            <?php echo esc_html( $rsvp->name ); ?>
            <?php if ( is_user_logged_in() && $rsvp->email === $user_email ) : ?>
              <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=cancel_rsvp&rsvp_id=' . $rsvp->id ), 'cancel_rsvp_' . $rsvp->id ) ); ?>" class="cancel-rsvp">Cancel RSVP</a>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      <?php else : ?>
        <li class="rsvp-attendees-list-item">No RSVPs yet.</li>
      <?php endif; ?>
    </ul>
  <?php

  return ob_get_clean();
}

// Update the RSVP message based on whether the logged-in user has already RSVP'd or not.
function render_rsvp_confirmation( $content ) {
  if (is_singular( 'rsvp' ) ) {
    if ( strpos( $content, 'rsvp_form' ) === false ) {
  if ( preg_match('/<h3[^>]*class=["\']rsvp-page-title["\']>\s*RSVP/iu', $content) ) {
        $content = preg_replace('/(<h3[^>]*class=["\']rsvp-page-title["\']>\s*RSVP)/iu', '$1\'d', $content);
      }
    }
  }
  return $content;
}
add_filter( 'the_content', 'render_rsvp_confirmation', 20 );

// Add an admin link and page to manage RSVPs to WP Admin.
function custom_rsvp_dashboard_menu() {
  add_menu_page(
    'RSVPs', // Page title
    'RSVPs', // Menu title
    'manage_options', // Capability
    'rsvp-dashboard', // Menu slug
    'rsvp_dashboard_page', // Function to display the page content
    'dashicons-calendar', // Icon
    21 // Position in the menu
  );
}
add_action('admin_menu', 'custom_rsvp_dashboard_menu');

// Populate the RSVPs admin page.
function rsvp_dashboard_page() {
  global $wpdb;
  
  // Default sorting by event_date in ascending order.
  $sort_by = isset($_GET['sort_by']) ? sanitize_text_field($_GET['sort_by']) : 'event_date';
  $sort_order = isset($_GET['sort_order']) && $_GET['sort_order'] == 'asc' ? 'asc' : 'desc';

  // If the sort parameters are set, reload the page with the correct order.
  $current_url = admin_url('admin.php?page=rsvp-dashboard');
  
  ?>
  <div class="wrap">
    <h1>RSVP Dashboard</h1>
    <table class="wp-list-table widefat fixed striped posts">
      <thead>
        <tr>
          <th scope="col" class="manage-column">
            <a href="<?php echo esc_url( add_query_arg( array( 'sort_by' => 'event_title', 'sort_order' => $sort_order === 'asc' ? 'desc' : 'asc' ), $current_url ) ); ?>">
              Event Title
              <?php if ($sort_by === 'event_title') { echo $sort_order === 'asc' ? '▲' : '▼'; } ?>
            </a>
          </th>
          <th scope="col" class="manage-column">
            <a href="<?php echo esc_url( add_query_arg( array( 'sort_by' => 'event_date', 'sort_order' => $sort_order === 'asc' ? 'desc' : 'asc' ), $current_url ) ); ?>">
              Event Date
              <?php if ($sort_by === 'event_date') { echo $sort_order === 'asc' ? '▲' : '▼'; } ?>
            </a>
          </th>
          <th scope="col" class="manage-column">
            <a href="<?php echo esc_url( add_query_arg( array( 'sort_by' => 'rsvp_count', 'sort_order' => $sort_order === 'asc' ? 'desc' : 'asc' ), $current_url ) ); ?>">
              RSVP Count
              <?php if ($sort_by === 'rsvp_count') { echo $sort_order === 'asc' ? '▲' : '▼'; } ?>
            </a>
          </th>
        </tr>
      </thead>
      <tbody>
        <?php
        // Query the database for events stored in the wp_rsvps table.
        $events = $wpdb->get_results("SELECT DISTINCT event_title, event_date FROM {$wpdb->prefix}rsvps");

        // Sort the events array based on the selected sort column.
        usort($events, function($a, $b) use ($sort_by, $sort_order, $wpdb) {
          if ($sort_by === 'rsvp_count') {
            // Query to get RSVP count for the specific events
            $rsvp_a_count = $wpdb->get_var($wpdb->prepare(
              "SELECT COUNT(*) FROM {$wpdb->prefix}rsvps WHERE event_title = %s AND event_date = %s",
              $a->event_title, $a->event_date
            ));
            $rsvp_b_count = $wpdb->get_var($wpdb->prepare(
              "SELECT COUNT(*) FROM {$wpdb->prefix}rsvps WHERE event_title = %s AND event_date = %s",
              $b->event_title, $b->event_date
            ));

            return $sort_order === 'asc' ? $rsvp_a_count - $rsvp_b_count : $rsvp_b_count - $rsvp_a_count;
          } elseif ($sort_by === 'event_date') {
            // Sort by event_date, converting to a timestamp for comparison.
            $a_timestamp = strtotime($a->event_date);
            $b_timestamp = strtotime($b->event_date);
            return $sort_order === 'asc' ? $a_timestamp - $b_timestamp : $b_timestamp - $a_timestamp;
          } else {
            return $sort_order === 'asc' ? strcmp($a->event_title, $b->event_title) : strcmp($b->event_title, $a->event_title);
          }
        });

        if ($events) {
          foreach ($events as $event) {
            $event_title = $event->event_title;
            $event_title_formatted = format_event_titles( $event_title );
            $event_date = $event->event_date;
            
            // Query to get the RSVP data count for the specific event.
            $rsvp_data = $wpdb->get_results($wpdb->prepare(
              "SELECT * FROM {$wpdb->prefix}rsvps WHERE event_title = %s AND event_date = %s",
              $event_title, $event_date
            ));
            $rsvp_count = count($rsvp_data);
            ?>
            <tr>
              <td>
                <a href="<?php echo esc_url( add_query_arg( array( 'event_title' => $event_title, 'event_date' => $event_date ), admin_url('admin.php?page=rsvp-event') ) ); ?>">
                  <?php echo esc_html($event_title_formatted); ?>
                </a>
              </td>
              <td><?php echo esc_html(date('F j, Y', strtotime($event_date))); ?></td>
              <td><?php echo esc_html($rsvp_count); ?></td>
            </tr>
            <?php
          }
        } else {
          echo '<tr><td colspan="3">No events found.</td></tr>';
        }
        ?>
      </tbody>
    </table>
  </div>
  <?php
}

// Create the admin page for individual events.
function rsvp_event_admin_page() {
  global $wpdb;
 
  $event_title = isset($_GET['event_title']) ? sanitize_text_field($_GET['event_title']) : '';
  $event_title_formatted = format_event_titles($event_title);
  $event_date = isset($_GET['event_date']) ? sanitize_text_field($_GET['event_date']) : '';

  if (empty($event_title) || empty($event_date)) {
    echo '<p>No event specified.</p>';
    return;
  }

  $order_by = isset($_GET['order_by']) ? sanitize_sql_orderby($_GET['order_by']) : 'name';
  $order = (isset($_GET['order']) && $_GET['order'] === 'desc') ? 'DESC' : 'ASC';
  $new_order = ($order === 'ASC') ? 'desc' : 'asc';

  $rsvp_data = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}rsvps WHERE event_title = %s AND event_date = %s ORDER BY $order_by $order",
    $event_title, $event_date
  ));

  if (empty($rsvp_data)) {
    echo '<p>No RSVPs found for this event.</p>';
    return;
  }

  $nonce = wp_create_nonce('delete_rsvps_nonce');
  $base_url = admin_url('admin.php?page=rsvp-event&event_title=' . urlencode($event_title) . '&event_date=' . urlencode($event_date));

  ?>
    <div class="wrap">
      <h1>Event Details</h1>
      <h2><?php echo esc_html($event_title_formatted); ?> | <?php echo esc_html(date('F j, Y', strtotime($event_date))); ?></h2>
      <h4>List of Attendees</h4>
      <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="delete_rsvps">
        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr($nonce); ?>">
        <input type="hidden" name="event_title" value="<?php echo esc_attr($event_title); ?>">
        <input type="hidden" name="event_date" value="<?php echo esc_attr($event_date); ?>">
        <table class="wp-list-table widefat striped posts">
          <thead>
            <tr>
              <th scope="col" class="manage-column">
                <input type="checkbox" id="select-all">
              </th>
              <th scope="col" class="manage-column">
                <a href="<?php echo esc_url($base_url . '&order_by=name&order=' . $new_order); ?>">
                  Name
                  <?php if ($order_by === 'name') { echo $new_order === 'asc' ? '▲' : '▼'; } ?>
                </a>
              </th>
              <th scope="col" class="manage-column">
                <a href="<?php echo esc_url($base_url . '&order_by=email&order=' . $new_order); ?>">
                  Email
                  <?php if ($order_by === 'email') { echo $new_order === 'asc' ? '▲' : '▼'; } ?>
                </a>
              </th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rsvp_data as $rsvp): ?>
              <tr>
                <td>
                  <input type="checkbox" name="rsvp_ids[]" value="<?php echo esc_attr($rsvp->id); ?>">
                </td>
                <td><?php echo esc_html($rsvp->name); ?></td>
                <td><?php echo esc_html($rsvp->email); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <br>
        <button type="submit" class="button button-primary">Delete Selected</button>
      </form>
    </div>
    <script>
      document.getElementById("select-all").addEventListener("change", function() {
        let checkboxes = document.querySelectorAll('input[name="rsvp_ids[]"]');
        checkboxes.forEach(cb => cb.checked = this.checked);
      });
    </script>
  <?php
}

// Register the event page as a submenu item, without rendering the menu link.
function rsvp_event_page() {
  // Only trigger the admin page for event details when event_title is set.
  if ( isset( $_GET['event_title'] ) && isset( $_GET['event_date'] ) ) {
    add_submenu_page(
      null, // No parent menu (hidden)
      'RSVP Event Details', // Page title
      'RSVP Event',        // Menu title
      'manage_options',    // Capability
      'rsvp-event',        // Slug (this is the URL parameter)
      'rsvp_event_admin_page', // Function to render the page
    );
  }
}
add_action( 'admin_menu', 'rsvp_event_page' );

// Handle the deletion of RSVPs in the admin dashboard.
function handle_delete_rsvps() {
  if (!current_user_can('manage_options')) {
    wp_die(__('Unauthorized request.'));
  }

  if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'delete_rsvps_nonce')) {
    wp_die(__('Security check failed.'));
  }

  global $wpdb;

  $event_title = isset($_POST['event_title']) ? sanitize_text_field($_POST['event_title']) : '';
  $event_date = isset($_POST['event_date']) ? sanitize_text_field($_POST['event_date']) : '';

  if (isset($_POST['delete_all']) && $_POST['delete_all'] === "1") {
    // Delete all RSVPs for this event.
    $wpdb->delete("{$wpdb->prefix}rsvps", ['event_title' => $event_title, 'event_date' => $event_date]);
  } elseif (!empty($_POST['rsvp_ids'])) {
    // Delete selected RSVPs
    $rsvp_ids = array_map('intval', $_POST['rsvp_ids']);
    $placeholders = implode(',', array_fill(0, count($rsvp_ids), '%d'));
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}rsvps WHERE id IN ($placeholders)", $rsvp_ids));
  }

  wp_redirect(admin_url("admin.php?page=rsvp-event&event_title={$event_title}&event_date={$event_date}"));
  exit;
}
add_action('admin_post_delete_rsvps', 'handle_delete_rsvps');

// Handle the cancellation of an RSVP by a user.
function handle_cancel_rsvp() {
  if ( !is_user_logged_in() ) {
    wp_die(__('You must be logged in to cancel an RSVP.', 'textdomain'));
  }

  if ( !isset($_GET['rsvp_id']) || !isset($_GET['_wpnonce']) ) {
    wp_die(__('Invalid request.', 'textdomain'));
  }

  $rsvp_id = intval($_GET['rsvp_id']);
  if ( !wp_verify_nonce($_GET['_wpnonce'], 'cancel_rsvp_' . $rsvp_id) ) {
    wp_die(__('Security check failed.', 'textdomain'));
  }

  global $wpdb;
  $user_email = wp_get_current_user()->user_email;

  // Check if the RSVP belongs to the logged-in user before deleting.
  $result = $wpdb->delete(
    $wpdb->prefix . 'rsvps',
    array( 'id' => $rsvp_id, 'email' => $user_email ),
    array( '%d', '%s' )
  );

  if ( $result ) {
    wp_redirect( wp_get_referer() ? wp_get_referer() : home_url() );
    exit;
  } else {
    wp_die(__('Unable to cancel RSVP. It may have already been removed.', 'textdomain'));
  }
}
add_action('admin_post_cancel_rsvp', 'handle_cancel_rsvp');