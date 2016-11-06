<?php
  /**
   * Child theme init
   */

   /**
   * Debug tool
   */
  function vd($var) {
    echo '<pre>';
    echo var_dump($var);
    echo '</pre>';
  }
  function dvm($var) {
    vd($var);
  }
  function vm($var) {
    vd($var);
  }

/**
 * Import parent theem JS/CSS
 */
function theme_enqueue_styles() {
    $parent_style = 'parent-style';
    wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
    wp_enqueue_script( 'my-custom-script', get_template_directory_uri() . '-child/app.js');
  }
  add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles' );

/**
 * Apply child theme block view
 */
function get_my_block_view_template() {
  $templates = array( 'my-block-view.php', 'index.php' );

  return get_query_template( 'home', $templates );
}

/**
 * Render ✅ as result
 */
function tick_filter($val) {
  if ($val === 'yes') {
    $val = '✅';
  }
  return $val;
}


