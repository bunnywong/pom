<div class="container">
<?php
  /*
  Template Name: 1. block view
  */

  // @TODO: warning for non permission access. now displayed empty table

  global $current_user;
  $wpuf_form_id = 49;
  $user = $current_user;
  $user_id = get_current_user_id();
  $is_admin = FALSE;

  if ($current_user->roles[0] === 'administrator') {
    $is_admin = TRUE;
  }

  if (isset($_GET['user_id']) && $is_admin) {
    $user_id = $_GET['user_id'];
    $user = get_userdata($_GET['user_id']);
  }

  $cid = 4; // category__and: 1 = Uncategorized, 4 = 存入股本, 3 =  往來記錄
  $posts = get_my_post($cid, $user_id);

  include( get_my_block_view_template() );
  wp_reset_postdata();
  get_header();

  $str = '';

  if (isset($user->display_name)) {
    $str .= '<h2 class="text-center">Welcome ' . $user->display_name . '</h2><hr>';
  }

  foreach ($posts as $key => $val) {
    if ($val->post_status === 'publish' || $is_admin === TRUE) {
      // Get meta by post ID
      $stock_init_table = '';
      $custom_field = get_post_meta($val->ID);
      // 1. Collect data
      foreach (my_title_in_from('a', $is_admin) as $k => $v) {
        if ($custom_field['user_id'][0] === $user_id) {
          // work for necessary user ID only
          $stock_init_table .= '<tr><td>' . get_title_from_a($v) . '</td><td>' . my_field_alter($custom_field[$v], $v) . '</td></tr>';
        }
      }
      // 2. Output stock initial table
      if ($stock_init_table !== '')  { // Exclude not match user ID posts
        $str .= '<table class="table table-responsive table-striped table-bordered table-hover my-table"><thead><th colspan="2"><a href="/wp-admin/post.php?post=' . $post->ID . '&action=edit" class="pull-right"><button type="button" class="btn btn-primary">edit</button></a></th></thead><tbody>';
        $str .= $stock_init_table;
        $str .= '</tbody></table>';
      }
    }
  }

  // 2. Get stock interest table
  $cid = 3; // category__and: 1 = Uncategorized, 4 = 存入股本, 3 =  往來記錄
  $posts = get_my_post($cid, $user_id);
  $stock_interest_table = get_transaction_table($posts, $is_admin, 'from_a', $user_id);

  // Output stock interest table
  if (count($stock_interest_table) > 0) {
    $str .= '<table class="table table-responsive table-striped table-bordered Xtable-hover my-table"><thead><tr><th>日期</th><th>類別</th><th>金額</th><th>詳細</th><th>收款人手機或電郵</th><th>Action</th></tr></thead><tbody>';
    $str .= $stock_interest_table;
    $str .= '</tbody></table>';
  }
  else {
    // No stock interest table handle
    $str .= '<div class="alert alert-info">No 股息 record.</div>';
  }

  echo $str;

  if ($is_admin) {
    // echo '<hr><h2 class="text-center">存入股本</h2>';
    // echo do_shortcode('[wpuf_form id="' . $wpuf_form_id . '"]'); //@admin only //@DEBUG: disable
    echo "<script>jQuery('body').addClass('user-is-admin');</script>";
  } else {
    echo "<script>jQuery('body').addClass('user-is-client');</script>";
  }
?>
</div>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script>
   window.app.userId = <?= $user_id; ?>
</script>
