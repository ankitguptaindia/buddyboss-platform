<?php
/**
 * BuddyBoss - Groups Zoom Meetings
 *
 * @since BuddyBoss 1.2.10
 */
?>
<div class="meeting-item-container">
<?php
	if ( bp_has_zoom_meetings() ) { ?>
		<div class="meeting-item-table">
			<div class="meeting-item-header">
				<div class="meeting-item-head"><?php _e( 'Date', 'buddyboss' ); ?></div>
				<div class="meeting-item-head"><?php _e( 'Topic', 'buddyboss' ); ?></div>
				<div class="meeting-item-head"><?php _e( 'Meeting ID', 'buddyboss' ); ?></div>
				<div class="meeting-item-head"></div>
			</div>
			<?php
			while ( bp_zoom_meeting() ) {
				bp_the_zoom_meeting();

				$group_link = bp_get_group_permalink( buddypress()->groups->current_group );
				$url        = trailingslashit( $group_link . '/zoom/meetings/' . bp_get_zoom_meeting_id() );

				?>
				<div class="meeting-item" data-id="<?php bp_zoom_meeting_id(); ?>"
					data-meeting-id="<?php bp_zoom_meeting_zoom_meeting_id(); ?>">
					<div class="meeting-item-col meeting-date">
						<?php echo bp_core_get_format_date( bp_get_zoom_meeting_start_date(), bp_core_date_format( true, true ) ); ?><br/>
						<?php bp_zoom_meeting_timezone(); ?>
					</div>
					<div class="meeting-item-col meeting-topic">
						<a href="<?php echo $url; ?>" class="sort-headers meeting-link"
						data="topic"><?php bp_zoom_meeting_title(); ?></a>
					</div>
					<div class="meeting-item-col meeting-id"><?php bp_zoom_meeting_zoom_meeting_id(); ?></div>
					<div class="meeting-item-col meeting-action">
						<?php if ( bp_zoom_can_current_user_start_meeting( bp_get_zoom_meeting_id() ) ) : ?>
							<a role="button" target="_blank" href="<?php bp_zoom_meeting_zoom_start_url(); ?>" class="button small meeting-start"><?php _e( 'Start', 'buddyboss' ); ?></a>
						<?php endif; ?>
						<?php if ( bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), bp_get_current_group_id() ) ) : ?>
							<a role="button" id="bp-zoom-meeting-delete" data-nonce="<?php echo wp_create_nonce( 'bp_zoom_meeting_delete' ); ?>" href="#" class="button small outline bp-zoom-meeting-delete"><?php _e( 'Delete', 'buddyboss' ); ?></a>
						<?php endif; ?>
						<a role="button" id="bp-zoom-meeting-view-recordings" href="#" class="button small outline"><?php _e( 'View Recordings', 'buddyboss' ); ?></a>
					</div>
					<div class="form-group recording-list">

					</div>
				</div>
				<?php
			} ?>

		</div>

		<?php
		if ( bp_zoom_meeting_has_more_items() ) {
			?>
			<div class="load-more">
				<a class="button full outline" href="<?php bp_zoom_meeting_load_more_link(); ?>"><?php esc_html_e( 'Load More', 'buddyboss' ); ?></a>
			</div>
			<?php
		}
	} else {
		bp_nouveau_user_feedback( 'meetings-loop-none' );
	} ?>
</div>