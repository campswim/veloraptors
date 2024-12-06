<?php
// Enqueue the parent theme's stylesheet
function magzine_child_enqueue_styles() {
    wp_enqueue_style('magzine-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('magzine-child-style', get_stylesheet_uri(), array('magzine-style'));
}
add_action('wp_enqueue_scripts', 'magzine_child_enqueue_styles');

// // Enable SVG upload support
// function enable_svg_upload($mimes) {
//   $mimes['svg'] = 'image/svg+xml';
//   return $mimes;
// }
// add_filter('upload_mimes', 'enable_svg_upload');

// // Optional: Sanitize SVG files
// function sanitize_svg($data, $file, $filename, $mimes) {
//   if ('svg' === $file['type']) {
//       $data['ext'] = 'svg';
//       $data['type'] = 'image/svg+xml';
//   }
//   return $data;
// }
// add_filter('wp_check_filetype_and_ext', 'sanitize_svg', 10, 4);