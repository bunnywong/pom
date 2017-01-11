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
    case 'user_id':
      return 'User ID';
    default:
      // For `interest_n`
      $month_pos = strlen($query) - strrpos($query, '_');
      return '利息 ' . substr($query, -($month_pos) + 1);
  }
}
/**
 * Render from by title
 */
function my_title_in_from($from, $is_admin) {
  if ($from === 'a') {
    // $from = array('申請時間', '股權生效', '認購股數', '金額', '參考編號', 'user_id');
    $from = array('stock_time', 'stock_effect', 'stock_unit', 'stock_price', 'stock_ref', 'user_id');
    // Append 派息 1 ~ 12 to array for ADMIN role
    if ($is_admin) {
      for($i=1; $i <= 12; $i++) {
        $from[] = 'stock_interest_' . $i;
      }
    }
  }

  if ($from === 'b') {
    // $from = array('日期', '類別', '金額', '詳細');
    $from = array('transaction_date', 'transaction_class', 'transaction_price', 'transaction_detail', 'transaction_contact'); // , 'user_id'
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
function get_transaction_table($posts, $is_admin, $from_number, $user_id) {
  $str = '';
  foreach ($posts as $key => $val) {
    $custom_field = get_post_meta($val->ID);

    if ($custom_field['user_id'][0] === $user_id) {
      // Get meta by post ID
      $data = my_title_in_from('b', $is_admin);

      switch ($from_number) {
        case 'from_a':
          // Condition for stock-interest only
          if ($custom_field['transaction_class'][0] === '股息') { // '股息' is value
            foreach ($data as $k => $v) {
              $str .= '<td>' . my_field_alter($custom_field[$v], $v) . '</td>';
            }
            $str .= '<td><a href="/wp-admin/post.php?post=' . $val->ID . '&action=edit" class="pull-right"><button type="button" class="btn btn-primary">edit</button></a></td>';
          }
          break;

        case 'from_b':
          foreach ($data as $k => $v) {
            $str .= '<td>' . my_field_alter($custom_field[$v], $v) . '</td>';
          }
          $str .= '<td><a href="/wp-admin/post.php?post=' . $val->ID . '&action=edit" class="pull-right"><button type="button" class="btn btn-primary">edit</button></a></td>';
          break;
      }

      // Attach wrapper with result
      if ($str !== '') {
        $str = '<tr>' . $str;
        $str .= '</tr>';
      }

    }
  }
  return $str;
 }

/**
 * Query post by condition
 */
function get_my_post($cid, $user_id) {
  return query_posts( array( 'post_type' => 'post', 'category__and'=> $cid, 'posts_per_page' => 6, 'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit'), 'paged' => ( get_query_var('paged') ? get_query_var('paged') : 1 ) ) );
}



