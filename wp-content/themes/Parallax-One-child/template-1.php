<?php
  /*
  Template Name: 1. block view
  */

  global $current_user;
  $user_id = get_current_user_id();
  $is_admin = FALSE;
  $user = $current_user;

  if ($current_user->roles[0] === 'administrator') {
    $is_admin = TRUE;
  }

  if (isset($_GET['uid']) && $is_admin) {
    $user = get_userdata($_GET['uid']);
  }

  $cid = 4; // category__and: 1 = Uncategorized, 4 = 存入股本, 3 =  往來記錄
  $posts = get_my_post($cid, $user_id);

  include( get_my_block_view_template() );
  wp_reset_postdata();
  get_header();

  $str;
  $str .= '<div class="container">';
  $str .= '<h2 class="text-center">Welcome ' . $user->display_name . '</h2><hr>';
  $i = 0; //@DEBUG

  foreach ($posts as $key => $val) {
    if ($val->post_status === 'publish' || $is_admin === TRUE) {
      // Get meta by post ID
      $custom_field = get_post_meta($val->ID);
      $str .= '<table class="table table-responsive table-striped table-bordered Xtable-hover my-table"><tbody>';

      foreach (my_title_in_from('a', $is_admin) as $k => $v) {
        $str .= '<tr><td>'.($v) . '</td><td>' . my_field_render($custom_field[$v], $v) . '</td></tr>';
      }
      $str .= '</tbody></table>';
    }
    $i++; //@DEBUG
  }

  $cid = 3; // category__and: 1 = Uncategorized, 4 = 存入股本, 3 =  往來記錄
  $posts = get_my_post($cid, $user_id);
  $str .= get_transaction_table($posts, $is_admin, 'from_a');

  $str .= '</div>';

  echo $str;

  echo '<hr><h2 class="text-center">A. 存入股本</h2>';
  echo do_shortcode('[wpuf_form id="49"]'); //@admin only
  // echo do_shortcode('[wpuf_form id="57"]'); //@admin only

  if ($is_admin) {
    echo "<script>jQuery('body').addClass('user-is-admin');</script>";
  }
?>

<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script>
   jQuery( ".date input" ).datepicker({dateFormat: "yy-mm-dd"});
</script>
