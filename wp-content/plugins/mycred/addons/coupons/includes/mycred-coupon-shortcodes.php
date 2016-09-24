<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Load Coupon Shortcode
 * @filter mycred_get_coupon_by_code
 * @filter mycred_load_coupon
 * @since 1.4
 * @version 1.1
 */
if ( ! function_exists( 'mycred_render_shortcode_load_coupon' ) ) :
	function mycred_render_shortcode_load_coupon( $atts, $content = NULL ) {

		if ( ! is_user_logged_in() )
			return $content;

		extract( shortcode_atts( array(
			'label'       => 'Coupon',
			'button'      => 'Apply Coupon',
			'placeholder' => ''
		), $atts ) );

		$mycred = mycred();
		if ( ! isset( $mycred->coupons ) )
			return '<p><strong>Coupon Add-on settings are missing! Please visit the myCRED > Settings page to save your settings before using this shortcode.</strong></p>';

		// Prep
		$user_id = get_current_user_id();

		$output  = '<div class="mycred-coupon-form">';

		// No show for excluded users
		if ( ! $mycred->exclude_user( $user_id ) ) {

			// On submits
			if ( isset( $_POST['mycred_coupon_load']['token'] ) && wp_verify_nonce( $_POST['mycred_coupon_load']['token'], 'mycred-load-coupon' . $user_id ) ) {

				$coupon = mycred_get_coupon_post( $_POST['mycred_coupon_load']['couponkey'] );
				$load   = mycred_use_coupon( $_POST['mycred_coupon_load']['couponkey'], $user_id );

				// Coupon does not exist
				if ( $load === 'missing' )
					$output  .= '<div class="alert alert-danger">' . $mycred->coupons['invalid'] . '</div>';

				// Coupon has expired
				elseif ( $load === 'expired' )
					$output  .= '<div class="alert alert-danger">' . $mycred->coupons['expired'] . '</div>';

				// User limit reached
				elseif ( $load === 'max' )
					$output  .= '<div class="alert alert-warning">' . $mycred->coupons['user_limit'] . '</div>';

				// Failed minimum balance requirement
				elseif ( $load === 'min_balance' ) {
					$min      = get_post_meta( $coupon->ID, 'min', true );
					$template = str_replace( '%min%', $min, $mycred->coupons['min'] );
					$output  .= '<div class="alert alert-danger">' . $template . '</div>';
				}

				// Failed maximum balance requirement
				elseif ( $load === 'max_balance' ) {
					$max      = get_post_meta( $coupon->ID, 'max', true );
					$template = str_replace( '%max%', $max, $mycred->coupons['max'] );
					$output  .= '<div class="alert alert-danger">' . $template . '</div>';
				}

				// Success
				else
					$output  .= '<div class="alert alert-success">' . $mycred->coupons['success'] . '</div>';
	
			}

		}

		if ( $label != '' )
			$label = '<label for="mycred-coupon-code">' . $label . '</label>';

		$output .= '
	<form action="" method="post" class="form-inline">
		<div class="form-group">
			' . $label . '
			<input type="text" name="mycred_coupon_load[couponkey]" placeholder="' . esc_attr( $placeholder ) . '" id="mycred-coupon-couponkey" class="form-control" value="" />
		</div>
		<div class="form-group">
			<input type="hidden" name="mycred_coupon_load[token]" value="' . wp_create_nonce( 'mycred-load-coupon' . $user_id ) . '" />
			<input type="submit" class="btn btn-primary" value="' . $button . '" />
		</div>
	</form>
</div>';

		return apply_filters( 'mycred_load_coupon', $output, $atts, $content );

	}
endif;

?>