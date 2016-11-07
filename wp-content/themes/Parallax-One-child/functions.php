<?php
  /**
   * Child theme init
   */

   /**
   * Debug tool
   */
  function vd($var) {
    echo '<pre class="db">';
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
 * Render from by title
 */
function my_title_in_from($from, $is_admin) {
  if ($from === 'a') {
    $from = array('申請時間', '股權生效', '認購股數', '金額', '參考編號', '詳細');
    // Append 派息 1 ~ 12 to array for ADMIN role
    if ($is_admin) {
      for($i=1; $i < 13; $i++) {
        $from[] = '派息' . $i;
      }
    }
  }
  return $from;
}

/**
 * Render ✅ as result
 */
function my_field_render($val, $title) {
  if (substr($title,0, 6) === '派息') {
    if ($val[0] === 'yes') { // [0] as default
      // dvm($val);
      $val[0] = '✅';
    }
    else {
      $val[0] = '❌';
    }
  }
  return $val[0];
}



