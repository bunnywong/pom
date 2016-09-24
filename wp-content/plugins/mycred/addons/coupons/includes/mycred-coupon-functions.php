<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Get Coupon
 * Returns a coupon object based on the post ID.
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_coupon' ) ) :
	function mycred_get_coupon( $coupon_post_id = NULL ) {

		if ( $coupon_post_id === NULL || get_post_type( $coupon_post_id ) != 'mycred_coupon' ) return false;

		$coupon                    = new StdClass();
		$coupon->post_id           = absint( $coupon_post_id );
		$coupon->code              = get_the_title( $coupon_post_id );
		$coupon->value             = mycred_get_coupon_value( $coupon_post_id );
		$coupon->point_type        = get_post_meta( $coupon_post_id, 'type', true );
		$coupon->max_global        = mycred_get_coupon_global_max( $coupon_post_id );
		$coupon->max_user          = mycred_get_coupon_user_max( $coupon_post_id );
		$coupon->requires_min      = mycred_get_coupon_min_balance( $coupon_post_id );
		$coupon->requires_min_type = get_post_meta( $coupon_post_id, 'min_balance_type', true );
		$coupon->requires_max      = mycred_get_coupon_max_balance( $coupon_post_id );
		$coupon->requires_max_type = get_post_meta( $coupon_post_id, 'max_balance_type', true );
		$coupon->expires           = mycred_get_coupon_expire_date( $coupon_post_id );
		$coupon->used              = mycred_get_global_coupon_count( $coupon_post_id );

		if ( ! mycred_point_type_exists( $coupon->point_type ) )
			$coupon->point_type = MYCRED_DEFAULT_TYPE_KEY;

		if ( ! mycred_point_type_exists( $coupon->requires_min_type ) )
			$coupon->requires_min_type = MYCRED_DEFAULT_TYPE_KEY;

		if ( ! mycred_point_type_exists( $coupon->requires_max_type ) )
			$coupon->requires_max_type = MYCRED_DEFAULT_TYPE_KEY;

		return apply_filters( 'mycred_get_coupon', $coupon, $coupon_post_id );

	}
endif;

/**
 * Get Coupon Value
 * @filter mycred_coupon_value
 * @since 1.4
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_coupon_value' ) ) :
	function mycred_get_coupon_value( $post_id = 0 ) {

		return apply_filters( 'mycred_coupon_value', get_post_meta( $post_id, 'value', true ), $post_id );

	}
endif;

/**
 * Get Coupon Expire Date
 * @filter mycred_coupon_max_balance
 * @since 1.4
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_coupon_expire_date' ) ) :
	function mycred_get_coupon_expire_date( $post_id = 0, $unix = false ) {

		$date = get_post_meta( $post_id, 'expires', true );

		if ( ! empty( $date ) && $unix )
			$date = strtotime( $date );

		return apply_filters( 'mycred_coupon_expires', $date, $post_id, $unix );

	}
endif;

/**
 * Get Coupon User Max
 * The maximum number a user can use this coupon.
 * @filter mycred_coupon_user_max
 * @since 1.4
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_coupon_user_max' ) ) :
	function mycred_get_coupon_user_max( $post_id = 0 ) {

		return apply_filters( 'mycred_coupon_user_max', get_post_meta( $post_id, 'user', true ), $post_id );

	}
endif;

/**
 * Get Coupons Global Max
 * @filter mycred_coupon_global_max
 * @since 1.4
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_coupon_global_max' ) ) :
	function mycred_get_coupon_global_max( $post_id = 0 ) {

		return apply_filters( 'mycred_coupon_global_max', get_post_meta( $post_id, 'global', true ), $post_id );

	}
endif;

/**
 * Create New Coupon
 * Creates a new myCRED coupon post.
 * @filter mycred_create_new_coupon_post
 * @filter mycred_create_new_coupon
 * @returns false if data is missing, post ID on success or wp_error / 0 if 
 * post creation failed.
 * @since 1.4
 * @version 1.1
 */
if ( ! function_exists( 'mycred_create_new_coupon' ) ) :
	function mycred_create_new_coupon( $data = array() ) {

		// Required data is missing
		if ( empty( $data ) ) return false;

		// Apply defaults
		extract( shortcode_atts( array(
			'code'             => mycred_get_unique_coupon_code(),
			'value'            => 0,
			'global_max'       => 1,
			'user_max'         => 1,
			'min_balance'      => 0,
			'min_balance_type' => MYCRED_DEFAULT_TYPE_KEY,
			'max_balance'      => 0,
			'max_balance_type' => MYCRED_DEFAULT_TYPE_KEY,
			'expires'          => '',
			'type'             => MYCRED_DEFAULT_TYPE_KEY
		), $data ) );

		// Create Coupon Post
		$post_id = wp_insert_post( apply_filters( 'mycred_create_new_coupon_post', array(
			'post_type'      => 'mycred_coupon',
			'post_title'     => $code,
			'post_status'    => 'publish',
			'comment_status' => 'closed',
			'ping_status'    => 'closed'
		), $data ) );

		// Error
		if ( $post_id !== 0 && ! is_wp_error( $post_id ) ) {

			// Save Coupon Details
			add_post_meta( $post_id, 'value',            $value, true );
			add_post_meta( $post_id, 'global',           $global_max, true );
			add_post_meta( $post_id, 'user',             $user_max, true );
			add_post_meta( $post_id, 'min',              $min_balance, true );
			add_post_meta( $post_id, 'min_balance_type', $min_balance_type, true );
			add_post_meta( $post_id, 'max',              $max_balance, true );
			add_post_meta( $post_id, 'max_balance_type', $max_balance_type, true );

			if ( ! empty( $expires ) )
				add_post_meta( $post_id, 'expires', $expires );

		}

		return apply_filters( 'mycred_create_new_coupon', $post_id, $data );

	}
endif;

/**
 * Get Unique Coupon Code
 * Generates a unique 12 character alphanumeric coupon code.
 * @filter mycred_get_unique_coupon_code
 * @since 1.4
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_unique_coupon_code' ) ) :
	function mycred_get_unique_coupon_code() {

		global $wpdb;

		do {

			$id    = strtoupper( wp_generate_password( 12, false, false ) );
			$query = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} WHERE post_title = %s AND post_type = %s;", $id, 'mycred_coupon' ) );

		} while ( ! empty( $query ) );

		return apply_filters( 'mycred_get_unique_coupon_code', $id );

	}
endif;

/**
 * Get Coupon Post
 * @filter mycred_get_coupon_by_code
 * @since 1.4
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_coupon_post' ) ) :
	function mycred_get_coupon_post( $code = '' ) {

		return apply_filters( 'mycred_get_coupon_by_code', get_page_by_title( strtoupper( $code ), 'OBJECT', 'mycred_coupon' ), $code );

	}
endif;

/**
 * Use Coupon
 * Will attempt to use a given coupon and award it's value
 * to a given user. Requires you to provide a log entry template.
 * @action mycred_use_coupon
 * @since 1.4
 * @version 1.1
 */
if ( ! function_exists( 'mycred_use_coupon' ) ) :
	function mycred_use_coupon( $code = '', $user_id = 0 ) {

		// Missing required information
		if ( empty( $code ) || $user_id === 0 ) return 'missing';

		$can_use = true;

		// Get coupon by code (post title)
		$coupon = mycred_get_coupon_post( $code );

		// Coupon does not exist
		if ( $coupon === NULL )
			return 'missing';

		// Check Expiration
		$now     = current_time( 'timestamp' );
		$expires = mycred_get_coupon_expire_date( $coupon->ID, true );
		if ( ! empty( $expires ) && $expires !== 0 && $expires <= $now )
			$can_use = 'expired';

		// Get Global Count
		if ( $can_use === true ) {

			$global_count = mycred_get_global_coupon_count( $coupon->ID );
			$global_max   = mycred_get_coupon_global_max( $coupon->ID );

			if ( $global_count >= $global_max )
				$can_use = 'expired';

		}

		// Get User max
		if ( $can_use === true ) {

			$user_count = mycred_get_users_coupon_count( $code, $user_id );
			$user_max   = mycred_get_coupon_user_max( $coupon->ID );

			if ( $user_count >= $user_max )
				$can_use = 'max';

		}

		$type = get_post_meta( $coupon->ID, 'type', true );
		if ( $type == '' )
			$type = MYCRED_DEFAULT_TYPE_KEY;

		$mycred = mycred( $type );

		if ( $can_use === true ) {

			// Min balance requirement
			$users_balance = $mycred->get_users_balance( $user_id, $type );
			$min_balance   = mycred_get_coupon_min_balance( $coupon->ID );

			if ( $min_balance['type'] != $type ) {

				$mycred        = mycred( $min_balance['type'] );
				$users_balance = $mycred->get_users_balance( $user_id, $min_balance['type'] );

			}

			if ( $mycred->number( $min_balance['value'] ) > $mycred->zero() && $users_balance < $mycred->number( $min_balance['value'] ) )
				$can_use = 'min_balance';

			// Max balance requirement
			if ( $can_use === true ) {

				$max_balance = mycred_get_coupon_max_balance( $coupon->ID );

				if ( $max_balance['type'] != $type ) {

					$mycred        = mycred( $max_balance['type'] );
					$users_balance = $mycred->get_users_balance( $user_id, $max_balance['type'] );

				}

				if ( $mycred->number( $max_balance['value'] ) > $mycred->zero() && $users_balance >= $mycred->number( $max_balance['value'] ) )
					$can_use = 'max_balance';

			}

		}

		// Let other play and change the value of $can_use
		$can_use = apply_filters( 'mycred_can_use_coupon', $can_use, $code, $user_id, $coupon );

		// Ready to use coupon!
		if ( $can_use === true ) {

			$mycred = mycred( $type );

			$value = mycred_get_coupon_value( $coupon->ID );
			$value = $mycred->number( $value );

			// Get Coupon log template
			if ( ! isset( $mycred->core['coupons']['log'] ) )
				$mycred->core['coupons']['log'] = 'Coupon redemption';

			// Apply Coupon
			$mycred->add_creds(
				'coupon',
				$user_id,
				$value,
				$mycred->core['coupons']['log'],
				$coupon->ID,
				$code,
				$type
			);

			do_action( 'mycred_use_coupon', $user_id, $coupon );

			// Increment global counter
			$global_count ++;
			update_post_meta( $coupon->ID, 'global_count', $global_count );

			// If the updated counter reaches the max, trash the coupon now
			if ( $global_count >= $global_max )
				wp_trash_post( $coupon->ID );

			// This should resolves issues where caching prevents the new global count from being loaded.
			else {
				clean_post_cache( $coupon->ID );
			}

			return $mycred->number( $users_balance+$value );

		}

		// Trash expired coupons to preent further usage
		elseif ( $can_use == 'expired' ) {

			wp_trash_post( $coupon->ID );

		}

		return $can_use;

	}
endif;

/**
 * Get Users Coupon Count
 * Counts the number of times a user has used a given coupon.
 * @filter mycred_get_users_coupon_count
 * @since 1.4
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_users_coupon_count' ) ) :
	function mycred_get_users_coupon_count( $code = '', $user_id = '' ) {

		global $wpdb, $mycred;

		// Count how many times a given user has used a given coupon
		$result = $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT( * ) 
			FROM {$mycred->log_table} 
			WHERE ref = %s 
				AND user_id = %d
				AND data = %s;", 'coupon', $user_id, $code ) );

		return apply_filters( 'mycred_get_users_coupon_count', $result, $code, $user_id );

	}
endif;

/**
 * Get Coupons Global Count
 * @filter mycred_get_global_coupon_count
 * @since 1.4
 * @version 1.1
 */
if ( ! function_exists( 'mycred_get_global_coupon_count' ) ) :
	function mycred_get_global_coupon_count( $post_id = 0 ) {

		global $wpdb, $mycred;

		$count = $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(*) 
			FROM {$mycred->log_table} 
			WHERE ref = 'coupon' AND ref_id = %d;", $post_id ) );

		if ( $count === NULL ) $count = 0;

		return apply_filters( 'mycred_get_global_coupon_count', $count, $post_id );

	}
endif;

/**
 * Get Coupons Minimum Balance Requirement
 * @filter mycred_coupon_min_balance
 * @since 1.4
 * @version 1.1
 */
if ( ! function_exists( 'mycred_get_coupon_min_balance' ) ) :
	function mycred_get_coupon_min_balance( $post_id = 0 ) {

		$type = get_post_meta( $post_id, 'min_balance_type', true );
		if ( ! mycred_point_type_exists( $type ) ) $type = MYCRED_DEFAULT_TYPE_KEY;

		$min  = get_post_meta( $post_id, 'min_balance', true );
		if ( $min == '' ) $min == 0;

		return apply_filters( 'mycred_coupon_min_balance', array(
			'type'  => $type,
			'value' => $min
		), $post_id );

	}
endif;

/**
 * Get Coupons Maximum Balance Requirement
 * @filter mycred_coupon_max_balance
 * @since 1.4
 * @version 1.1
 */
if ( ! function_exists( 'mycred_get_coupon_max_balance' ) ) :
	function mycred_get_coupon_max_balance( $post_id = 0 ) {

		$type = get_post_meta( $post_id, 'max_balance_type', true );
		if ( ! mycred_point_type_exists( $type ) ) $type = MYCRED_DEFAULT_TYPE_KEY;

		$max  = get_post_meta( $post_id, 'max_balance', true );
		if ( $max == '' ) $max == 0;

		return apply_filters( 'mycred_coupon_max_balance', array(
			'type'  => $type,
			'value' => $max
		), $post_id );

	}
endif;
?>