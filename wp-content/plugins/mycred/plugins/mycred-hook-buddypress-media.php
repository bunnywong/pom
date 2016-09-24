<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Register Hook
 * @since 1.4
 * @version 1.0
 */
add_filter( 'mycred_setup_hooks', 'mycred_register_buddypress_media_hook', 35 );
function mycred_register_buddypress_media_hook( $installed ) {

	if ( ! function_exists( 'rtmedia_autoloader' ) ) return $installed;

	$installed['rtmedia'] = array(
		'title'       => __( 'rtMedia Galleries', 'mycred' ),
		'description' => __( 'Award / Deduct %_plural% for users creating albums or uploading new photos.', 'mycred' ),
		'callback'    => array( 'myCRED_rtMedia' )
	);

	return $installed;

}

/**
 * rtMedia Hook
 * @since 1.4
 * @version 1.1
 */
add_action( 'mycred_load_hooks', 'mycred_load_buddypress_media_hook', 35 );
function mycred_load_buddypress_media_hook() {

	// If the hook has been replaced or if plugin is not installed, exit now
	if ( class_exists( 'myCRED_rtMedia' ) || ! function_exists( 'rtmedia_autoloader' ) ) return;

	class myCRED_rtMedia extends myCRED_Hook {

		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = 'mycred_default' ) {

			parent::__construct( array(
				'id'       => 'rtmedia',
				'defaults' => array(
					'new_media'      => array(
						'photo'          => 0,
						'photo_log'      => '%plural% for new photo',
						'photo_limit'    => '0/x',
						'video'          => 0,
						'video_log'      => '%plural% for new video',
						'video_limit'    => '0/x',
						'music'          => 0,
						'music_log'      => '%plural% for new music',
						'music_limit'    => '0/x',
					),
					'delete_media'   => array(
						'photo'          => 0,
						'photo_log'      => '%plural% for deleting photo',
						'video'          => 0,
						'video_log'      => '%plural% for deleting video',
						'music'          => 0,
						'music_log'      => '%plural% for deleting music'
					)
				)
			), $hook_prefs, $type );

		}

		/**
		 * Run
		 * @since 1.4
		 * @version 1.0.1
		 */
		public function run() {

			add_action( 'rtmedia_after_add_media',     array( $this, 'new_media' ) );
			add_action( 'rtmedia_before_delete_media', array( $this, 'delete_media' ) );

		}

		/**
		 * New Media
		 * @since 1.4
		 * @version 1.2
		 */
		public function new_media( $media_ids ) {

			// Loop through all uploaded files
			foreach ( $media_ids as $media_id ) {

				// Get media details from id
				$model     = new RTMediaModel();
				$media     = $model->get_media( array( 'id' => $media_id ) );

				if ( ! isset( $media[0]->media_type ) ) continue;

				$reference = $media[0]->media_type . '_upload';
				$user_id   = $media[0]->media_author;

				if ( $this->core->exclude_user( $user_id ) ) continue;

				$points    = $this->prefs['new_media'][ $media[0]->media_type ];
				$log_entry = $this->prefs['new_media'][ $media[0]->media_type . '_log' ];
				$data      = array( 'ref_type' => 'media', 'attachment_id' => $media_id );

				// If this media type awards zero, bail
				if ( $points == $this->core->zero() ) continue;

				// Limit
				if ( $this->over_hook_limit( $media[0]->media_type, $reference, $user_id ) ) continue;

				// Make sure this is unique
				if ( $this->core->has_entry( $reference, $user_id, $media_id, $data, $this->mycred_type ) ) continue;

				// Execute
				$this->core->add_creds(
					$reference,
					$user_id,
					$points,
					$log_entry,
					$media_id,
					$data,
					$this->mycred_type
				);

			}

		}

		/**
		 * Delete Media
		 * @since 1.4
		 * @version 1.1
		 */
		public function delete_media( $media_id ) {

			// Get media details from id
			$model     = new RTMediaModel();
			$media     = $model->get_media( array( 'id' => $media_id ) );

			if ( ! isset( $media[0]->media_type ) ) return;

			$reference = $media[0]->media_type . '_deletion';
			$user_id   = $media[0]->media_author;
			$points    = $this->prefs['delete_media'][ $media[0]->media_type ];
			$log_entry = $this->prefs['delete_media'][ $media[0]->media_type . '_log' ];
			$data      = array( 'ref_type' => 'media', 'attachment_id' => $media_id );

			// If this media type awards zero, bail
			if ( $points == $this->core->zero() ) return;

			// Check for exclusion
			if ( $this->core->exclude_user( $user_id ) ) return;

			// Only delete points once
			if ( $this->core->has_entry( $reference, $user_id, $media_id, $data, $this->mycred_type ) ) return;

			// Execute
			$this->core->add_creds(
				$reference,
				$user_id,
				$points,
				$log_entry,
				$media_id,
				$data,
				$this->mycred_type
			);

		}

		/**
		 * Check Limit
		 * @since 1.6
		 * @version 1.1
		 */
		function over_hook_limit( $instance = '', $reference = '', $user_id = NULL, $ref_id = NULL ) {

			global $wpdb;

			// Prep
			$wheres = array();
			$now    = current_time( 'timestamp' );

			$prefs = $this->prefs['new_media'][ $instance . '_limit' ];

			// If the user ID is not set use the current one
			if ( $user_id === NULL )
				$user_id = get_current_user_id();

			// If this an existance check or just a regular limit check?
			$exists_check = false;
			if ( $ref_id !== NULL && strlen( $ref_id ) > 0 )
				$exists_check = true;

			if ( count( explode( '/', $prefs ) ) != 2 )
				$prefs = '0/x';

			// Prep settings
			list ( $amount, $period ) = explode( '/', $prefs );
			$amount = (int) $amount;

			// We start constructing the query.
			$wheres[] = $wpdb->prepare( "user_id = %d", $user_id );
			$wheres[] = $wpdb->prepare( "ref = %s", $reference );
			$wheres[] = $wpdb->prepare( "ctype = %s", $this->mycred_type );

			if ( $exists_check )
				$wheres[] = $wpdb->prepare( "ref_id = %d", $ref_id );

			// If check is based on time
			if ( ! in_array( $period, array( 't', 'x' ) ) ) {

				// Per day
				if ( $period == 'd' )
					$from = mktime( 0, 0, 0, date( 'n', $now ), date( 'j', $now ), date( 'Y', $now ) );

				// Per week
				elseif ( $period == 'w' )
					$from = mktime( 0, 0, 0, date( "n", $now ), date( "j", $now ) - date( "N", $now ) + 1 );

				// Per Month
				elseif ( $period == 'm' )
					$from = mktime( 0, 0, 0, date( "n", $now ), 1, date( 'Y', $now ) );

				$wheres[] = $wpdb->prepare( "time BETWEEN %d AND %d", $from, $now );

			}

			// Put all wheres together into one string
			$wheres = implode( " AND ", $wheres );

			// Count
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->core->log_table} WHERE {$wheres};" );
			if ( $count === NULL ) $count = 0;

			$over_limit = false;

			// Existence check has first priority
			if ( $count > 0 && $exists_check )
				$over_limit = true;

			// Limit check is second priority
			elseif ( $period != 'x' && $count >= $amount )
				$over_limit = true;

			return $over_limit;

		}

		/**
		 * Adjust Limit Name
		 * @since 1.6
		 * @version 1.0
		 */
		public function hook_limit_name( $name ) {

			$name = str_replace( '[photo_limit]', '[photo_limit_by]', $name );
			$name = str_replace( '[video_limit]', '[video_limit_by]', $name );
			$name = str_replace( '[music_limit]', '[music_limit_by]', $name );
			return $name;

		}

		/**
		 * Adjust Limit ID
		 * @since 1.6
		 * @version 1.0
		 */
		public function hook_limit_id( $id ) {

			$id = str_replace( 'photo-limit', 'photo-limit-by', $id );
			$id = str_replace( 'video-limit', 'video-limit-by', $id );
			$id = str_replace( 'music-limit', 'music-limit-by', $id );
			return $id;

		}

		/**
		 * Preferences for rtMedia Gallery Hook
		 * @since 1.4
		 * @version 1.0
		 */
		public function preferences() {

			$prefs = $this->prefs;

			global $rtmedia;

			$photos = ' readonly="readonly"';
			if ( array_key_exists( 'allowedTypes_photo_enabled', $rtmedia->options ) && $rtmedia->options['allowedTypes_photo_enabled'] == 1 )
				$photos = '';

			$videos = ' readonly="readonly"';
			if ( array_key_exists( 'allowedTypes_video_enabled', $rtmedia->options ) && $rtmedia->options['allowedTypes_video_enabled'] == 1 )
				$videos = '';

			$music = ' readonly="readonly"';
			if ( array_key_exists( 'allowedTypes_music_enabled', $rtmedia->options ) && $rtmedia->options['allowedTypes_music_enabled'] == 1 )
				$music = '';

			add_filter( 'mycred_hook_limit_name_by', array( $this, 'hook_limit_name' ) );
			add_filter( 'mycred_hook_limit_id_by',   array( $this, 'hook_limit_id' ) );

?>
<label class="subheader"><?php _e( 'New Media Upload', 'mycred' ); ?></label>
<ol>
	<li>
		<label for="<?php echo $this->field_id( array( 'new_media', 'photo' ) ); ?>"><?php _e( 'Photo Upload', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'new_media', 'photo' ) ); ?>" id="<?php echo $this->field_id( array( 'new_media', 'photo' ) ); ?>"<?php echo $photos; ?> value="<?php echo $this->core->number( $prefs['new_media']['photo'] ); ?>" size="8" /></div>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'new_media', 'photo_limit' ) ); ?>"><?php _e( 'Limit', 'mycred' ); ?></label>
		<?php echo $this->hook_limit_setting( $this->field_name( array( 'new_media', 'photo_limit' ) ), $this->field_id( array( 'new_media', 'photo_limit' ) ), $prefs['new_media']['photo_limit'] ); ?>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'new_media', 'photo_log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'new_media', 'photo_log' ) ); ?>" id="<?php echo $this->field_id( array( 'new_media', 'photo_log' ) ); ?>"<?php echo $photos; ?> value="<?php echo esc_attr( $prefs['new_media']['photo_log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'new_media', 'video' ) ); ?>"><?php _e( 'Video Upload', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'new_media', 'video' ) ); ?>" id="<?php echo $this->field_id( array( 'new_media', 'video' ) ); ?>"<?php echo $videos; ?> value="<?php echo $this->core->number( $prefs['new_media']['video'] ); ?>" size="8" /></div>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'new_media', 'video_limit' ) ); ?>"><?php _e( 'Limit', 'mycred' ); ?></label>
		<?php echo $this->hook_limit_setting( $this->field_name( array( 'new_media', 'video_limit' ) ), $this->field_id( array( 'new_media', 'video_limit' ) ), $prefs['new_media']['video_limit'] ); ?>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'new_media', 'video_log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'new_media', 'video_log' ) ); ?>" id="<?php echo $this->field_id( array( 'new_media', 'video_log' ) ); ?>"<?php echo $videos; ?> value="<?php echo esc_attr( $prefs['new_media']['video_log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'new_media', 'music' ) ); ?>"><?php _e( 'Music Upload', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'new_media', 'music' ) ); ?>" id="<?php echo $this->field_id( array( 'new_media', 'music' ) ); ?>"<?php echo $music; ?> value="<?php echo $this->core->number( $prefs['new_media']['music'] ); ?>" size="8" /></div>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'new_media', 'music_limit' ) ); ?>"><?php _e( 'Limit', 'mycred' ); ?></label>
		<?php echo $this->hook_limit_setting( $this->field_name( array( 'new_media', 'music_limit' ) ), $this->field_id( array( 'new_media', 'music_limit' ) ), $prefs['new_media']['music_limit'] ); ?>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'new_media', 'music_log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'new_media', 'music_log' ) ); ?>" id="<?php echo $this->field_id( array( 'new_media', 'music_log' ) ); ?>"<?php echo $music; ?> value="<?php echo esc_attr( $prefs['new_media']['music_log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
</ol>

<label for="<?php echo $this->field_id( array( 'delete_media', 'creds' ) ); ?>" class="subheader"><?php _e( 'Delete Media', 'mycred' ); ?></label>
<ol>
	<li>
		<label for="<?php echo $this->field_id( array( 'delete_media', 'photo' ) ); ?>"><?php _e( 'Delete Photo', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'delete_media', 'photo' ) ); ?>" id="<?php echo $this->field_id( array( 'delete_media', 'photo' ) ); ?>"<?php echo $photos; ?> value="<?php echo $this->core->number( $prefs['delete_media']['photo'] ); ?>" size="8" /></div>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'delete_media', 'photo_log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'delete_media', 'photo_log' ) ); ?>" id="<?php echo $this->field_id( array( 'delete_media', 'photo_log' ) ); ?>"<?php echo $photos; ?> value="<?php echo esc_attr( $prefs['delete_media']['photo_log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'delete_media', 'video' ) ); ?>"><?php _e( 'Delete Video', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'delete_media', 'video' ) ); ?>" id="<?php echo $this->field_id( array( 'delete_media', 'video' ) ); ?>"<?php echo $videos; ?> value="<?php echo $this->core->number( $prefs['delete_media']['video'] ); ?>" size="8" /></div>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'delete_media', 'video_log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'delete_media', 'video_log' ) ); ?>" id="<?php echo $this->field_id( array( 'delete_media', 'video_log' ) ); ?>"<?php echo $videos; ?> value="<?php echo esc_attr( $prefs['delete_media']['video_log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'delete_media', 'music' ) ); ?>"><?php _e( 'Delete Music', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'delete_media', 'music' ) ); ?>" id="<?php echo $this->field_id( array( 'delete_media', 'music' ) ); ?>"<?php echo $music; ?> value="<?php echo $this->core->number( $prefs['delete_media']['music'] ); ?>" size="8" /></div>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'delete_media', 'music_log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'delete_media', 'music_log' ) ); ?>" id="<?php echo $this->field_id( array( 'delete_media', 'music_log' ) ); ?>"<?php echo $music; ?> value="<?php echo esc_attr( $prefs['delete_media']['music_log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
</ol>
<?php

		}

		/**
		 * Sanitise Preferences
		 * @since 1.6
		 * @version 1.0
		 */
		function sanitise_preferences( $data ) {

			if ( isset( $data['new_media']['photo_limit'] ) && isset( $data['new_media']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['new_media']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['new_media']['photo_limit'] = $limit . '/' . $data['new_media']['limit_by'];
				unset( $data['new_media']['limit_by'] );
			}

			if ( isset( $data['new_media']['limit'] ) && isset( $data['new_media']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['new_media']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['new_media']['limit'] = $limit . '/' . $data['new_media']['limit_by'];
				unset( $data['new_media']['limit_by'] );
			}

			if ( isset( $data['new_media']['limit'] ) && isset( $data['new_media']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['new_media']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['new_media']['limit'] = $limit . '/' . $data['new_media']['limit_by'];
				unset( $data['new_media']['limit_by'] );
			}

			return $data;

		}

	}

}

?>