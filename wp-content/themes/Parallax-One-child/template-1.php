<?php
  /*
  Template Name: 1. block view
  */

  global $current_user;
  $wpuf_form_id = 49;
  $user_id = get_current_user_id();
  $is_admin = FALSE;
  $user = $current_user;

  if ($current_user->roles[0] === 'administrator') {
    $is_admin = TRUE;
  }

  if (isset($_GET['user_id']) && $is_admin) {
    $user = get_userdata($_GET['user_id']);
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

      // vd(my_title_in_from('a', $is_admin));
      // vd($custom_field);
      foreach (my_title_in_from('a', $is_admin) as $k => $v) {
        $str .= '<tr><td>'. get_title_from_a($v) . '</td><td>' . my_field_alter($custom_field[$v], $v) . '</td></tr>';
      }
      $str .= '</tbody></table>';
    }
    $i++; //@DEBUG
  }
/*
  $cid = 3; // category__and: 1 = Uncategorized, 4 = 存入股本, 3 =  往來記錄
  $posts = get_my_post($cid, $user_id);
  $str .= get_transaction_table($posts, $is_admin, 'from_a');*/
  $str .= '</div>';

  echo $str;
  echo '<hr><h2 class="text-center">存入股本</h2>';

  if ($is_admin) {
    echo do_shortcode('[wpuf_form id="' . $wpuf_form_id . '"]'); //@admin only
    echo "<script>jQuery('body').addClass('user-is-admin');</script>";
  } else {
    echo "<script>jQuery('body').addClass('user-is-client');</script>";
  }
?>

<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script>
   // jQuery( ".date input" ).datepicker({dateFormat: "yy-mm-dd"});
</script>
