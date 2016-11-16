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

function get_title_from_a($query) {
  switch ($query) {
    case 'stock_time':
      return '申請時間';
    case 'stock_effect':
      return  '股權生效';
    case 'stock_unit':
      return '認購股數';
    case 'stock_price':
      return '金額';
    case 'stock_ref':
      return '參考編號';
    case 'stock_detail':
      return '詳細';
    default:
      // For `interest_n`
      return '利息 ' . substr($query, -1);
  }
}
/**
 * Render from by title
 */
function my_title_in_from($from, $is_admin) {
  if ($from === 'a') {
    // $from = array('申請時間', '股權生效', '認購股數', '金額', '參考編號', '詳細');
    $from = array('stock_time', 'stock_effect', 'stock_unit', 'stock_price', 'stock_ref', 'stock_detail');
    // Append 派息 1 ~ 12 to array for ADMIN role
    if ($is_admin) {
      for($i=1; $i < 13; $i++) {
        // $from[] = '派息' . $i;
        $from[] = 'stock_interest_' . $i;
      }
    }
  }

  if ($from === 'b') {
    // $from = array('日期', '類別', '金額', '詳細');
    $from = array('transaction_date', 'transaction_class', 'transaction_price', 'transaction_detail');
  }

  return $from;
}

/**
 * Render ✅ as result
 */
function my_field_alter($val, $title) {
  // if (substr($title,0, 6) === '派息') {
  if (substr($title,0, 15) === 'stock_interest_') {
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
  $str = '';
  foreach ($posts as $key => $val) {
      // Get meta by post ID
      $custom_field = get_post_meta($val->ID);
      $str .= '<tr>';
      $data = my_title_in_from('b', $is_admin);

      if ($from_number === 'from_a') {
        //@TODO: Remove empty DOM for return
        if ($custom_field['transaction_class'][0] === '股息') { // '股息' is value
          foreach ($data as $k => $v) {
            $str .= '<td>' . my_field_alter($custom_field[$v], $v) . '</td>';
          }
        }
      }
      else {
        // From B
        foreach ($data as $k => $v) {
          $str .= '<td>' . my_field_alter($custom_field[$v], $v) . '</td>';
        }
      }
      $str .= '</tr>';
  }
  return $str;
 }

/**
 * Query post
 */
function get_my_post($cid, $user_id) {
  return query_posts( array( 'author'=> $user_id, 'post_type' => 'post', 'category__and'=> $cid, 'posts_per_page' => 6, 'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit'), 'paged' => ( get_query_var('paged') ? get_query_var('paged') : 1 ) ) );
}


