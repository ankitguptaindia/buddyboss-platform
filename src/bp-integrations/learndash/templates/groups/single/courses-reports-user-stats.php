
<h3 class="ld-report-course-name"><?php echo $course->post_title; ?></h3>

<div class="ld-report-user-stats">
	<div class="user-info">
		<div class="user-avatar">
			<a href="<?php echo bp_core_get_user_domain( $user->ID ); ?>"><?php echo bp_core_fetch_avatar( array( 'item_id' => $user->ID ) ); ?></a>
		</div>
		<div class="user-name">
			<h5 class="list-title member-name"><a href="<?php echo bp_core_get_user_domain( $user->ID ); ?>"><?php echo $user->display_name; ?></a></h5>
			<p class="item-meta"><?php echo groups_is_user_admin($user->ID, $group->id)? __('Teacher', 'buddyboss') : __('Student', 'buddyboss'); ?></p>
		</div>
	</div>

	<div class="user-steps">
		<p><?php printf(
			__('<b>%d out of %d</b> %s completed', 'buddyboss'),
			learndash_course_get_completed_steps($user->ID, $course->ID),
			$totalSteps = learndash_get_course_steps_count($course->ID),
			_n('step', 'steps', $totalSteps, 'buddyboss')
		); ?></p>
	</div>

	<?php if ($points = learndash_get_user_course_points($user->ID)): ?>
		<div class="user-points">
			<p><?php printf(
				__('<b>%d</b> %s earned', 'buddyboss'),
				$points,
				_n('point', 'points', $points, 'buddyboss')
			); ?></p>
		</div>
	<?php endif; ?>

	<?php if (bp_is_active('messages') && $user->ID != bp_loggedin_user_id()): ?>
		<div class="user-message">
			<?php
				$link = apply_filters(
					'bp_get_send_private_message_link',
					wp_nonce_url(
						bp_loggedin_user_domain() . bp_get_messages_slug() . '/compose/?r=' . bp_core_get_username($user->ID)
					)
				);

				echo bp_get_send_message_button([
					'link_href' => $link,
					'link_text' => __( 'Message', 'buddyboss' ),
				]);
			?>
		</div>
	<?php endif; ?>
</div>
