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