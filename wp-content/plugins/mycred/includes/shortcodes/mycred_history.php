<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED Shortcode: mycred_history
 * Returns the points history.
 * @see http://codex.mycred.me/shortcodes/mycred_history/
 * @since 1.0.9
 * @version 1.3
 */
if ( ! function_exists( 'mycred_render_shortcode_history' ) ) :
	function mycred_render_shortcode_history( $atts, $content = '' ) {

		extract( shortcode_atts( array(
			'user_id'    => '',
			'number'     => 10,
			'time'       => '',
			'ref'        => '',
			'order'      => '',
			'show_user'  => 0,
			'show_nav'   => 1,
			'login'      => '',
			'type'       => MYCRED_DEFAULT_TYPE_KEY,
			'pagination' => 10
		), $atts ) );

		// If we are not logged in
		if ( ! is_user_logged_in() && $login != '' )
			return $login . $content;

		if ( ! MYCRED_ENABLE_LOGGING ) return '';

		$user_id = mycred_get_user_id( $user_id );

		if ( ! mycred_point_type_exists( $type ) )
			$type = MYCRED_DEFAULT_TYPE_KEY;

		$args = array( 'ctype' => $type );

		if ( $user_id != 0 && $user_id != '' )
			$args['user_id'] = absint( $user_id );

		if ( absint( $number ) > 0 )
			$args['number'] = absint( $number );

		if ( $time != '' )
			$args['time'] = $time;

		if ( $ref != '' )
			$args['ref'] = $ref;

		if ( $order != '' )
			$args['order'] = $order;

		if ( isset( $_GET['paged'] ) && $_GET['paged'] != '' )
			$args['paged'] = absint( $_GET['paged'] );

		elseif ( isset( $_GET['page'] ) && $_GET['page'] != '' )
			$args['paged'] = absint( $_GET['page'] );

		$log = new myCRED_Query_Log( apply_filters( 'mycred_front_history_args', $args, $atts ) );

		ob_start();

		do_action( 'mycred_front_history', $user_id );

?>
<div class="mycred-history-wrapper">
<form class="form-inline" role="form" method="get" action="">

	<?php if ( $show_nav == 1 ) $log->front_navigation( 'top', $pagination ); ?>

	<?php $log->display(); ?>

	<?php if ( $show_nav == 1 ) $log->front_navigation( 'bottom', $pagination ); ?>

</form>
</div>
<?php

		$content = ob_get_contents();
		ob_end_clean();

		$log->reset_query();

		return $content;

	}
endif;
add_shortcode( 'mycred_history', 'mycred_render_shortcode_history' );

?>