<?php
/**
 * Add admin Activity settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main activity settings class.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_Admin_Setting_Activity extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'Activity', 'buddyboss' );
		$this->tab_name  = 'bp-activity';
		$this->tab_order = 15;
	}

	public function is_active() {
		return bp_is_active( 'activity' );
	}

	public function settings_save() {
		parent::settings_save();

		$bp = buddypress();
		$active_components = $bp->active_components;

		// Flag for activate the blogs component
		$is_blog_component_active = false;

		// Get all active custom post type.
		$post_types = get_post_types( [ 'public' => true ] );

		foreach ( $post_types as $cpt ) {
			// Exclude all the custom post type which is already in BuddyPress Activity support.
			if ( in_array( $cpt,
				[ 'forum', 'topic', 'reply', 'page', 'attachment', 'bp-group-type', 'bp-member-type' ] ) ) {
				continue;
			}

			$enable_blog_feeds = isset( $_POST["bp-feed-custom-post-type-$cpt"] );

			if ( $enable_blog_feeds ) {
				$is_blog_component_active = true;
			}

		}

		if ( $is_blog_component_active ) {
			$active_components['blogs'] = '1';
		} else {
			unset( $active_components['blogs'] );
		}

		// Save settings and upgrade schema.
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		require_once( $bp->plugin_dir . '/bp-core/admin/bp-core-admin-schema.php' );

		$bp->active_components = $active_components;
		bp_core_install( $bp->active_components );
		bp_core_add_page_mappings( $bp->active_components );
		bp_update_option( 'bp-active-components', $bp->active_components );

	}

	public function register_fields() {
		$this->add_section( 'bp_activity', __( 'Activity Settings', 'buddyboss' ) );

		// Blog Feeds Option (will sync with "blogs" component)
//		$this->add_checkbox_field( 'bp-enable-blog-feeds', __( 'Blog Posts', 'buddyboss' ), [
//			'input_text' => __( 'Automatically publish new blog posts into the activity feed', 'buddyboss' )
//		] );

		// Activity commenting on post and comments.
		$this->add_field( 'bp-disable-blogforum-comments', __( 'Post Comments', 'buddyboss' ), 'bp_admin_setting_callback_blogforum_comments', 'bp_admin_sanitize_callback_blogforum_comments' );

		// Allow subscriptions setting.
		$this->add_field( '_bp_enable_heartbeat_refresh', __( 'Activity auto-refresh', 'buddyboss' ), 'bp_admin_setting_callback_heartbeat', 'intval' );

		// Allow follow.
		$this->add_field( '_bp_enable_activity_follow', __( 'Follow', 'buddyboss' ), 'bp_admin_setting_callback_enable_activity_follow', 'intval' );

		// Allow like.
		$this->add_field( '_bp_enable_activity_like', __( 'Likes', 'buddyboss' ), 'bp_admin_setting_callback_enable_activity_like', 'intval' );

		// Allow link preview.
		$this->add_field( '_bp_enable_activity_link_preview', __( 'Link Preview', 'buddyboss' ), 'bp_admin_setting_callback_enable_activity_link_preview', 'intval' );

		// Allow gif.
		$this->add_field( '_bp_enable_activity_gif', __( 'GIFs', 'buddyboss' ), 'bp_admin_setting_callback_enable_activity_gif', 'intval' );
		$this->add_field( '_bp_activity_gif_api_key', __( 'GIFs Key', 'buddyboss' ), '__return_true', [], [
			'class' => 'hidden'
		] );


		// Allow subscriptions setting.
		if ( is_plugin_active( 'akismet/akismet.php' ) && defined( 'AKISMET_VERSION' ) ) {
			// $this->add_field( '_bp_enable_akismet', __( 'Akismet', 'buddyboss' ), 'bp_admin_setting_callback_activity_akismet', 'intval' );
		}

		$this->add_section( 'bp_custom_post_type', __( 'Posts in Activity Feed', 'buddyboss' ) );

		// create field for default Platform activity feed.
		$get_default_platform_activity_types = bp_platform_default_activity_types();
		$is_first = true;
		foreach ( $get_default_platform_activity_types as $type ) {
			$name = $type['activity_name'];
			$class = ( true === $is_first ) ? 'child-no-padding-first' : 'child-no-padding';
			$type['class'] = $class;
			$this->add_field( "bp-feed-platform-$name", ( true === $is_first ) ? __( 'BuddyBoss Platform', 'buddyboss' ) : '', 'bp_feed_settings_callback_platform', 'intval', $type );
			$is_first = false;
		}

		// Get all active custom post type.
		$post_types = get_post_types( [ 'public' => true ] );

		// Exclude BP CPT
		$bp_exclude_cpt = [ 'forum', 'topic', 'reply', 'page', 'attachment', 'bp-group-type', 'bp-member-type' ];

		$bp_excluded_cpt = array();
		foreach ( $post_types as $post_type ) {
			// Exclude all the custom post type which is already in BuddyPress Activity support.
			if ( in_array( $post_type, $bp_exclude_cpt ) ) {
				continue;
			}

			$bp_excluded_cpt[] = $post_type;
		}

		// flag for adding conditional CSS class.
		$count = 0;

		foreach ( $bp_excluded_cpt as $key => $post_type ) {

			$fields = [];

			$fields['args'] = [
					'post_type' => $post_type,
					'description' => false,
			];

			// set the description flag for adding text below the last CPT
			if( $key == ( count( $bp_excluded_cpt ) - 1 ) ) {
				$fields['args']['description'] = true;
			}

			if ( 'post' === $post_type ) {
				// create field for each of custom post type.
				$this->add_field( "bp-feed-custom-post-type-$post_type", __( 'WordPress', 'buddyboss' ), 'bp_feed_settings_callback_post_type', 'intval', $fields['args'] );
			} else {
				if ( 0 === $count ) {
					$fields['args']['class'] = 'child-no-padding-first';
					// create field for each of custom post type.
					$this->add_field( "bp-feed-custom-post-type-$post_type", __( 'Custom Post Types', 'buddyboss' ), 'bp_feed_settings_callback_post_type', 'intval', $fields['args'] );
				} else {
					$fields['args']['class'] = 'child-no-padding';
					// create field for each of custom post type.
					$this->add_field( "bp-feed-custom-post-type-$post_type", '&#65279;', 'bp_feed_settings_callback_post_type', 'intval', $fields['args'] );
				}
				$count++;
			}
		}
	}

}

return new BP_Admin_Setting_Activity;
