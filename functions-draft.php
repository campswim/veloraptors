<?php

// function log_user_login_redirect($user_login, $user) {
//     $redirect_url = $_SERVER['REQUEST_URI'];
//     error_log("User logged in: $user_login. Redirect URL: $redirect_url");
// }
// add_action('wp_login', 'log_user_login_redirect', 10, 2);

// function log_wp_redirect($location) {
//     error_log("Redirect to: $location");
//     return $location;
// }
// add_filter('wp_redirect', 'log_wp_redirect');

// add_filter('login_redirect', function($redirect_to, $request, $user) {
//     error_log("Login redirect to: $redirect_to");
//     return $redirect_to;
// }, 10, 3);

// add_action('admin_init', function() {
//     if (defined('DOING_AJAX') && DOING_AJAX) {
//         error_log('AJAX Request triggered: ' . $_SERVER['REQUEST_URI']);
//     }
// });

// add_action('pmpro_after_login', function($user) {
//     error_log('PMPro login redirect triggered for user: ' . $user->user_login);
// });

// add_action('init', function () {
//   // Check if we're handling a form submission with the problematic key
//   if (
//       isset($_POST['action']) &&
//       $_POST['action'] === 'form_processing' &&
//       isset($_POST['login_member_redirect'])
//   ) {
//       // Fix the redirect URL
//       $_POST['login_member_redirect'] = home_url();
//   }
// });

// Restrict the "Members" and "Board Members" menu items and subitems to users with the corresponding membership level. Not in use, because it made more sense within the context of the PMPro and BuddyPress integrations to make the Board Member level available to sign up and then just hide the sign-up options.
// function restrict_menu_to_members($items) {

//   $current_user_id = get_current_user_id();
//   $membership = pmpro_getMembershipLevelForUser($current_user_id);

//   // Track parent item keys to remove child items
//   $parent_keys_to_remove = [];

//   foreach ($items as $key => $item) {
//     // Check for "Board Members"
//     if (strpos($item->title, 'Board Members') !== false) {
//       // Check if the user has the 'Board Member' level
//       if (!pmpro_hasMembershipLevel('Board Member')) {
//         $parent_keys_to_remove[] = $item->ID;
//         unset($items[$key]);
//       }
//     }
//   }

//   // Remove child items if the parent "Members" has been removed
//   foreach ($items as $key => $item) {
//     // Check if the item is a child of "Members" (i.e., check if its parent is in the removed list)
//     if (in_array($item->menu_item_parent, $parent_keys_to_remove)) {
//       unset($items[$key]);
//     }
//   }

//   // Clear the array of keys to remove.
//   $parent_keys_to_remove = [];

//   // Check for "Members".
//   foreach ($items as $key => $item) {
//     if ($item->title === 'Members') {
//       // Check if the user has the 'Member' level
//       if (!pmpro_hasMembershipLevel('Member')) {
//         // Add parent item key to remove its children later
//         $parent_keys_to_remove[] = $item->ID;
//         unset($items[$key]);
//       }
//     }
//   }

//   // Remove child items if the parent "Members" has been removed
//   foreach ($items as $key => $item) {
//     // Check if the item is a child of "Members" (i.e., check if its parent is in the removed list)
//     if (in_array($item->menu_item_parent, $parent_keys_to_remove)) {
//       unset($items[$key]);
//     }
//   }

//   // Check for the public calendar and remove it if the user is a member.
//   foreach ($items as $key => $item) {
//     if ($item->title === 'Calendar' && empty( $item->menu_item_parent )) {
//       // Check if the user has the 'Member' level
//       if (pmpro_hasMembershipLevel('Member')) {
//         unset($items[$key]);
//       }
//     }
//   }

//   return $items;
// }
// add_filter('wp_nav_menu_objects', 'restrict_menu_to_members', 10, 2);
