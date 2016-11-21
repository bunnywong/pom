<div class="container">
<?php
  /*
  Template Name: 2. transaction list view
  */

  global $current_user;
  $wpuf_form_id = 57;
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

  $cid = 3; // category__and: 1 = Uncategorized, 4 = 存入股本, 3 =  往來記錄
  // vd($user_id); //@DEBUG
  $posts = get_my_post($cid, $user_id);
  // vd($posts); //@DEBUG
  include( get_my_block_view_template() );
  wp_reset_postdata();
  get_header();

  $str = '';

  if (isset($user->display_name)) {
    $str .= '<h2 class="text-center">Welcome ' . $user->display_name . '</h2><hr>';
  }


  // $str .= get_transaction_table($posts, $is_admin, 'from_b');
  $stock_transaction_table = get_transaction_table($posts, $is_admin, 'from_b');
  if (count($stock_transaction_table) > 0) {
    $str .= '<table class="table table-responsive table-striped table-bordered Xtable-hover my-table"><thead><tr><th>日期</th><th>類別</th><th>金額</th><th>詳細</th><th>Action</th></tr></thead><tbody>';
    $str .= $stock_transaction_table;
    $str .= '</tbody></table>';
  }
  else {
    $str .= '<div class="alert alert-info">No 存入股本 record.</div>';
  }

  echo $str;
  echo '<hr><h2 class="text-center">提取或轉移</h2>';
  echo do_shortcode('[wpuf_form id="' . $wpuf_form_id . '"]');

  if (!$is_admin) {
    echo "<script>jQuery('body').addClass('user-is-client');</script>";
  }
?>

</div>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script>
   window.app.userId = <?= $user_id; ?>
</script>

