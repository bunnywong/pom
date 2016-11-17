<?php
  /*
  Template Name: User Listing
  */

  include( get_my_block_view_template() );
  wp_reset_postdata();
  get_header();

  $users =  get_users();

  // $all_meta_for_user = get_user_meta( 1);
  // vd( $all_meta_for_user );
?>

<div class="container">
  <div class="col-xs-12">
    <h1 class="text-center">Admin Home</h1>
  </div>

  <div class="col-xs-12">
    <p>

    </p>
  </div>
  <table class="table table-responsive table-striped table-bordered table-hover my-table">
    <thead>
      <th>User Name</th>
      <th >Email</th>
      <th>存入股本</th>
      <th>股息報表</th>
      <th>提取或轉移</th>
    </thead>
    <tbody>
    <?php //vd($users); ?>
      <?php for($i = 0; $i < count($users); $i++): ?>
        <tr>
          <td><a href="/user?user_id=<?php echo $users[$i]->data->ID;?>"><?php echo $users[$i]->data->user_login;?></a></td>
          <td><?php echo $users[$i]->data->user_email;?></td>
          <td><a href="from-a?user_id=<?php echo $users[$i]->data->ID;?>"><button type="button" class="btn btn-default">add</button></a></td>
          <td><a href="a?user_id=<?php echo $users[$i]->data->ID;?>"><button type="button" class="btn btn-primary">show</button></a></td>
          <td><a href="b?user_id=<?php echo $users[$i]->data->ID;?>"><button type="button" class="btn btn-success">show</button></a></td>

        </tr>
      <?php endfor; ?>
    </tbody>
  </table>

  <a href="/new-user"><button type="button" class="center-block btn btn-primary btn-lg">註冊新股東</button></a>

  <?php // @TODO: enhance fields option by request ?>
  <?php //dvm($users); ?>
  <?php //dvm($users[0]); ?>
</div>
