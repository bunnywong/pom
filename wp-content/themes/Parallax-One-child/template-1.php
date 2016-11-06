<?php
  /*
  Template Name: 1. block view(clone from blog)
  */
  $user_id = get_current_user_id(); //@TODO: admin view all
  $posts = query_posts( array( 'author'=> $user_id, 'post_type' => 'post', 'posts_per_page' => 6, 'paged' => ( get_query_var('paged') ? get_query_var('paged') : 1 ) ) );
  // include( get_my_block_view_template() );
  wp_reset_postdata();
  get_header();

    // echo do_shortcode('[wpuf_form id="49"]'); //@admin only
  $ele = array('申請時間', '股權生效', '認購股數', '金額', '參考編號', '詳細');
  // Append 派息 1 ~ 12 to array
  for($i=1; $i < 13; $i++) {
    $ele[] = '派息' . $i;
  }

  $str = '<div class="container" style="margin-top: 200px;"><table class="table table-hover"><tbody>';
  foreach ($posts as $key => $val) {
    // Get meta by post ID
    $custom_field = get_post_meta($val->ID);
    foreach ($ele as $k => $v) {
      $str .= '<tr><td>'.$v . '</td><td>' .tick_filter($custom_field[$v][0]) . '</td></tr>'; // [0] as default
    }
  }
  $str .= '</tbody></table></div>';

  echo $str;

?>

