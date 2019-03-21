<?php
namespace BuddyBoss\Memberships\Classes;

use BuddyBoss\Memberships\Classes\BpMemberships;
use BuddyBoss\Memberships\Classes\BpmsView;

class MpHelper {

	private static $instance;

	public function __construct() {
		// @See getInstance();
	}

	/**
	 * Will return instance of MpHelper(Memberpress Helper) class
	 * @return object - Singleton
	 */
	public static function getInstance() {
		if (!isset(self::$instance)) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Injecting memberpess tabs lists on MemberPress membership edit screen
	 * @return {void}
	 */
	public static function mpLearndashTab() {

		error_log("MpHelper::mpLearndashTab()");

		$lmsTypes = BpMemberships::getLmsTypesSelected(LD_POST_TYPE);
		foreach ($lmsTypes as $key => $lmsType) {
			if ($lmsType == LD_POST_TYPE) {
				BpmsView::render('memberpress/tab', get_defined_vars());
			} else {
				// NOTE : Implementation for another LMS when required
			}
		}
	}

	/**
	 * Output tab content for LearnDash tab on MemberPress membership edit screen
	 *
	 * @param  {array}  $product MemberPress product information
	 * @return  {void}
	 */
	public static function mpTabContent($product) {

		error_log("MpHelper::mpTabContent()");

		$lmsTypes = BpMemberships::getLmsTypesSelected(LD_POST_TYPE);
		$membershipType = MP_POST_TYPE;
		$themeName = wp_get_theme();

		foreach ($lmsTypes as $lmsType) {
			if ($lmsType == LD_POST_TYPE) {
				// NOTE : Implementation for Learndash LMS

				$allCourses = BpMemberships::getLearndashCourses();
				$isEnabled = get_post_meta($product->rec->ID, "_bpms-$lmsType-$membershipType-is_enabled", true);
				$courseAccessMethod = get_post_meta($product->rec->ID, "_bpms-$lmsType-$membershipType-course_access_method", true);
				$coursesEnrolled = unserialize(get_post_meta($product->rec->ID, "_bpms-$lmsType-$membershipType-courses_enrolled", true));
				$allowFromPricebox = get_post_meta($product->rec->ID, "_bpms-$lmsType-$membershipType-allow_from_pricebox", true);
				$buttonText = get_post_meta($product->rec->ID, "_bpms-$lmsType-$membershipType-purchase_button_text", true);
				$buttonOrder = get_post_meta($product->rec->ID, "_bpms-$lmsType-$membershipType-purchase_button_order", true);
				$pId = $product->rec->ID; //Required for ajax-call
				$accessMethods = BpMemberships::getCourseOptions();
				$groups = learndash_get_groups();
				error_log("Implementation for Learndash LMS, $lmsType");

				BpmsView::render('memberpress/tab-content', get_defined_vars());

			} else {
				// NOTE : Implementation for another LMS when required
			}

		}
	}

	/**
	 * @param  {object}  $meprObj MemberPress Subscription (Updated) information
	 * @return  {void}
	 */
	public static function mpSubscriptionUpdated($meprObj) {
		if (BPMS_DEBUG) {
			error_log("mpSubscriptionUpdated() injected, subscription is updated at this point");
			// error_log(print_r($meprObj, true));
		}

		$status = $meprObj->status;
		$grantValues = array('active');
		$revokeValues = array('pending', 'suspended', 'cancelled');
		error_log("Status : $status");

		if (in_array($status, $revokeValues)) {
			// revoke access
			BpMemberships::bbmsUpdateMembershipAccess($meprObj, MP_POST_TYPE, false);
		} else if (in_array($status, $grantValues)) {
			// grant access
			BpMemberships::bbmsUpdateMembershipAccess($meprObj, MP_POST_TYPE, true);
		}

	}

	/**
	 * @param  {object}  $transaction MemberPress transaction (Updated) information
	 * @return  {void}
	 */
	public static function mpTransactionUpdated($meprObj) {
		if (BPMS_DEBUG) {
			error_log("mpTransactionUpdated() injected, transaction is updated at this point");
			// error_log(print_r($meprObj, true));
		}

		$status = $meprObj->status;
		$grantValues = array('complete');
		$revokeValues = array('pending', 'failed', 'refunded');
		error_log("Status : $status");

		if (in_array($status, $revokeValues)) {
			// revoke access
			BpMemberships::bbmsUpdateMembershipAccess($meprObj, MP_POST_TYPE, false);
		} else if (in_array($status, $grantValues)) {
			// grant access
			BpMemberships::bbmsUpdateMembershipAccess($meprObj, MP_POST_TYPE, true);
		}

	}

	/**
	 * @param  {string}  $oldStatus - Status such as 'created', 'paused', 'resumed', 'stopped', 'upgraded', 'downgraded', 'expired'
	 * @param  {string}  $newStatus - Status such as 'created', 'paused', 'resumed', 'stopped', 'upgraded', 'downgraded', 'expired'
	 * @param  array  $meprObj MemberPress Subscription information
	 * @return  {void}
	 */
	public static function mpSubscriptionTransitionStatus($oldStatus, $newStatus, $meprObj) {
		if (BPMS_DEBUG) {
			error_log("mpSubscriptionTransitionStatus() injected, old status : $oldStatus, new status : $newStatus");
			// error_log(print_r($meprObj, true));
		}
	}

	/**
	 * @param  array  $transaction MemberPress Transaction information
	 * @return  {void}
	 */
	public static function mpSignUp($meprObj) {
		error_log("mpSignup() injected, subscription_id would be non-ZERO");

		if ($meprObj->subscription_id > 0) {
			self::mpSubscriptionUpdated($meprObj);
		} else {
			self::mpTransactionUpdated($meprObj);
		}

	}

	/**
	 * Save LearnDash meta for MemberPress membership post object
	 * @param  {array}  $product MemberPress product information
	 * @return  {void}
	 */
	public static function mpSaveProduct($product) {
		if (BPMS_DEBUG) {
			error_log("mpSaveProduct()");
			// error_log(print_r($product, true));
		}
		$lmsTypes = BpMemberships::getLmsTypesSelected(LD_POST_TYPE);
		$membershipType = MP_POST_TYPE;
		foreach ($lmsTypes as $lmsType) {
			if ($lmsType == LD_POST_TYPE) {
				// NOTE : Implementation for Learndash LMS
				$isEnabled = $_REQUEST["bpms-$lmsType-$membershipType-is_enabled"];
				update_post_meta($product->rec->ID, "_bpms-$lmsType-$membershipType-is_enabled", $isEnabled);

				if ($isEnabled) {

					$courseAccessMethod = $_REQUEST["bpms-$lmsType-$membershipType-course_access_method"];
					update_post_meta($product->rec->ID, "_bpms-$lmsType-$membershipType-course_access_method", $courseAccessMethod);

					if ($courseAccessMethod == 'SINGLE_COURSES') {

						$newCourses = array_filter($_REQUEST["bpms-$lmsType-$membershipType-courses_enrolled"]);
						// error_log(print_r($newCourses, true));

						update_post_meta($product->rec->ID, "_bpms-$lmsType-$membershipType-courses_enrolled", serialize(array_values($newCourses)));
					} else if ($courseAccessMethod == 'ALL_COURSES') {

						if (BPMS_DEBUG) {
							error_log("ALL_COURSES selected");
						}

						// NOTE : Array format is consistent with GUI
						$allClosedCourses = BpMemberships::getLearndashClosedCourses();
					} else if ($courseAccessMethod == 'LD_GROUPS') {

						if (BPMS_DEBUG) {
							error_log("LD_GROUPS selected");
						}

						$newGroups = array_filter($_REQUEST["bpms-$lmsType-$membershipType-groups_attached"]);
						update_post_meta($product->rec->ID, "_bpms-$lmsType-$membershipType-groups_attached", serialize(array_values($newGroups)));
					}

					// Update Allow From PriceBox
					$allowFromPricebox = $_REQUEST["bpms-$lmsType-$membershipType-allow_from_pricebox"];
					update_post_meta($product->rec->ID, "_bpms-$lmsType-$membershipType-allow_from_pricebox", $allowFromPricebox);

					if ($allowFromPricebox) {
						$buttonText = $_REQUEST["bpms-$lmsType-$membershipType-purchase_button_text"];
						$buttonOrder = $_REQUEST["bpms-$lmsType-$membershipType-purchase_button_order"];

						update_post_meta($product->rec->ID, "_bpms-$lmsType-$membershipType-purchase_button_text", $buttonText);
						update_post_meta($product->rec->ID, "_bpms-$lmsType-$membershipType-purchase_button_order", $buttonOrder);
					}

				}

			} else {
				// NOTE : Implementation for another LMS when required
			}
		}

	}

}