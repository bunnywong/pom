<?php
  /*
  Template Name: 1. block view(clone from blog)
  */
  $user_id = get_current_user_id(); //@TODO: admin view all
  $user_role =  get_userdata($user_id);
  $user_role =  $user_role->roles;
  $user_role =  $user_role[0];

  get_userdata($user_id);
  dvm($user_id);
  $posts = query_posts( array( 'author'=> $user_id, 'post_type' => 'post', 'posts_per_page' => 6, 'paged' => ( get_query_var('paged') ? get_query_var('paged') : 1 ) ) );
  // include( get_my_block_view_template() );
  wp_reset_postdata();
  get_header(); //@DEBUG

  $str;
  $str .= '<div class="container" style="margin-top: 200px;">';

  foreach ($posts as $key => $val) {
    // Get meta by post ID
    $custom_field = get_post_meta($val->ID);
    $str .= '<table class="table table-hover my-table"><tbody>';
    foreach (my_title_in_from('a', $user_role) as $k => $v) {
      $str .= '<tr><td>'.($v) . '</td><td>' .my_field_render($custom_field[$v], $v) . '</td></tr>';
    }
    $str .= '</tbody></table>';
  }

  $str .= '</div>';

  echo $str;
  echo '<hr><h2 class="text-center">提取或轉移</h2>';
  echo do_shortcode('[wpuf_form id="49"]'); //@admin only

  if ($user_role === 'administrator') {
    echo "<script>jQuery('body').addClass('user-is-admin');</script>";
  }
?>

