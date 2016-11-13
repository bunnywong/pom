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

  if ($from === 'b') {
    $from = array('日期', '類別', '金額', '詳細');
  }

  return $from;
}

/**
 * Render ✅ as result
 */
function my_field_render($val, $title) {
  if (substr($title,0, 6) === '派息') {
    if ($val[0] === 'yes') { // [0] as default
      $val[0] = '✅';
    }
    else {
      $val[0] = '❌';
    }
  }
  return $val[0];
}

/**
 * Render transaction table
 */
function get_transaction_table($posts, $is_admin, $from_number) {
  $i = 0; //@DEBUG
  $str .= '<table class="table table-responsive table-striped table-bordered Xtable-hover my-table"><thead><tr><th>日期</th><th>類別</th><th>金額</th><th>詳細</th></tr></thead><tbody>';
      $str .= '<tr>';

  foreach ($posts as $key => $val) {
    if ($val->post_status === 'publish' || $is_admin === TRUE) {
      // Get meta by post ID
      $custom_field = get_post_meta($val->ID);
      $str .= '<tr>';
      $data = my_title_in_from('b', $is_admin);
      // vd($data[1]);
      if ($from_number === 'from_a') {
        if ($custom_field['類別'][0] === '股息') {
          foreach ($data as $k => $v) {
            $str .= '<td>' . my_field_render($custom_field[$v], $v) . '</td>';
          }
        }
      }
      else {
        foreach ($data as $k => $v) {
          $str .= '<td>' . my_field_render($custom_field[$v], $v) . '</td>';
        }
      }
      $str .= '</tr>';
    }
    $i++; //@DEBUG
  }
  $str .= '</tbody></table>';
// echo '<h3 class="db text-center">POST COUNT: '.$i.'</h3>'; //@DEBUG
  return $str;
 }

/**
 * Query post
 */
function get_my_post($cid, $user_id) {
  return query_posts( array( 'author'=> $user_id, 'post_type' => 'post', 'category__and'=> $cid, 'posts_per_page' => 6, 'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit'), 'paged' => ( get_query_var('paged') ? get_query_var('paged') : 1 ) ) );
}


