<?php
namespace BuddyBoss\Integrations;

// Block direct requests
if (!defined('ABSPATH')) {
	die("Sorry, you can't access this directly - Security established");
}

define('BBMS_DEBUG', true);
define('LD_POST_TYPE', 'sfwd-courses');
define('MP_POST_TYPE', 'memberpressproduct');
define('WC_POST_TYPE', 'product');
define('BBMS_URL', BP_PLUGIN_URL . '/src/bp-integrations');

class BbmsHelper {

	public function __construct() {

		/* Add scripts for admin section for plugin */
		add_action('admin_enqueue_scripts', array($this, 'addAdminScripts'));

		// Ajax services, related to courses
		// -----------------------------------------------------------------------------
		add_action('wp_ajax_search_courses', array($this, 'searchLearndashCourses'));
		add_action('wp_ajax_get_courses', array($this, 'getLearndashCoursesAsJson'));
		add_action('wp_ajax_selected_courses', array($this, 'selectedCourses'));

		// Ajax services, related to groups
		// -----------------------------------------------------------------------------
		add_action('wp_ajax_search_groups', array($this, 'searchLearndashGroups'));
		add_action('wp_ajax_get_groups', array($this, 'getLearndashGroupsAsJson'));
		add_action('wp_ajax_selected_groups', array($this, 'selectedGroups'));

		/**
		 * Available as hook, runs after a bbms(BuddyBoss Membership) plugin is loaded.
		 */
		do_action('bbms_loaded');

	}

	/**
	 * Need to sanitize value before updating to options table
	 * @param {array} $inputs - Form inputs/element
	 * @return {array} $sanitaryValues - Modified values
	 */
	public static function bbmsSettingsSanitize($inputs) {
		error_log("bbmsSettingsSanitize()");
		error_log(print_r($inputs, true));

		$sanitaryValues = array();
		if (isset($inputs['bp-learndash-memberpess'])) {
			$sanitaryValues['bp-learndash-memberpess'] = $inputs['bp-learndash-memberpess'];
		}
		if (isset($inputs['bp-learndash-woocommerce'])) {
			$sanitaryValues['bp-learndash-woocommerce'] = $inputs['bp-learndash-woocommerce'];
		}

		return $sanitaryValues;
	}

	/**
	 * Enqueue plugin scripts/styles
	 * @param  {string}      $hook_suffix - Refers to the hook suffix for the admin page
	 * @return {void}
	 */
	public static function addAdminScripts($hook_suffix) {
		global $pagenow;

		if ($pagenow == 'post.php') {
			global $post;
			$postType = $post->post_type;

			if (in_array($postType, array(MP_POST_TYPE, WC_POST_TYPE))) {

				if (BBMS_DEBUG) {
				}

				// Select2 Js
				wp_enqueue_script('select2-js', BBMS_URL . '/assets/scripts/select2.min.js');

				// Select2 Css
				wp_enqueue_style('select2', BBMS_URL . '/assets/styles/select2.min.css');

				// Localize the script with new data
				$bbmsVars = array(
					'ajax_url' => admin_url('admin-ajax.php'),
					'lms_type' => self::getLmsTypesSelected(LD_POST_TYPE),
					// 'membership_type' => self::getVendorTypesSelected(),
					'membership_type' => $post->post_type,
					'p_id' => $post->ID,
				);

				// Custom
				wp_register_script('bbms-js', BBMS_URL . '/assets/scripts/bbms.js');
				wp_localize_script('bbms-js', 'bbmsVars', $bbmsVars);
				wp_enqueue_script('bbms-js');

				wp_enqueue_style('bbms', BBMS_URL . '/assets/styles/bbms.css');

			}
		}

	}

	/**
	 * Get all LearnDash courses
	 * @return {object} LearnDash courses
	 */
	public static function getLearndashCourses() {
		// @todo : use post_where filter. Inject the ld-functions/hooks.
		// @todo : https://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
		global $wpdb;
		$query = "SELECT posts.ID as 'id', CONCAT(posts.post_title, \" (ID=\" , posts.ID, \")\")  as 'text' FROM $wpdb->posts posts WHERE posts.post_type = 'sfwd-courses' AND posts.post_status = 'publish' ORDER BY posts.post_title";

		return $wpdb->get_results($query, OBJECT);
	}

	/**
	 * Get all LearnDash courses
	 * @return {object} LearnDash course
	 */
	public static function getAllLearndashCourses() {
		// @todo : use post_where filter. Inject the ld-functions/hooks.
		// @todo : https://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
		global $wpdb;
		$query = "SELECT posts.ID as 'id', posts.post_title as 'text' FROM $wpdb->posts posts WHERE posts.post_type = 'sfwd-courses' AND posts.post_status = 'publish' ORDER BY posts.post_title";

		$results = $wpdb->get_results($query, OBJECT);

		return $results;
	}

	/**
	 * Get all LearnDash coursesIds
	 * @return {object} LearnDash course
	 */
	public static function getAllLearndashCoursesIds() {
		// @todo : use post_where filter. Inject the ld-functions/hooks.
		// @todo : https://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
		global $wpdb;
		$query = "SELECT postmeta.post_id as post_id FROM " . $wpdb->postmeta . " as postmeta INNER JOIN " . $wpdb->posts . " as posts ON posts.ID = postmeta.post_id WHERE posts.post_status='publish' AND posts.post_type='sfwd-courses' AND postmeta.meta_key='_sfwd-courses' AND ( postmeta.meta_value REGEXP '\"sfwd-courses_course_price_type\";s:6:\"closed\";' )";
		$courseIds = $wpdb->get_col($query);

		return $courseIds;
	}

	/**
	 * Search LearnDash courses as Ajax
	 * @param  {string}   $_GET['search'] - Term searched from UI
	 * @return {JSON} Searched LearnDash course(s) if found
	 */
	public function searchLearndashCourses() {
		error_log("searchLearndashCourses()");
		// @todo : use post_where filter. Inject the ld-functions/hooks.
		// @todo : https://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
		global $wpdb;

		$search = $_GET['search'];

		$query = "SELECT CONCAT(posts.ID, \":\" , posts.post_title, \"\") as 'id', CONCAT(posts.post_title, \"(ID:\" , posts.ID, \")\")  as 'text' FROM $wpdb->posts posts WHERE posts.post_type = 'sfwd-courses' AND posts.post_status = 'publish' AND posts.post_title LIKE \"%$search%\" OR posts.ID LIKE \"%$search%\" limit 2";

		$courses = $wpdb->get_results($query, OBJECT);
		$data = array();
		$data['results'] = $courses;
		wp_send_json_success($data, JSON_PRETTY_PRINT);
	}

	/**
	 * get All LearnDash courses for Ajax-call
	 *
	 * @return {JSON} All LearnDash courses
	 */
	public function getLearndashCoursesAsJson() {
		// @todo : use post_where filter. Inject the ld-functions/hooks.
		// @todo : https://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
		global $wpdb;

		$query = "SELECT posts.ID as 'id', CONCAT(posts.post_title, \"(ID:\" , posts.ID, \")\")  as 'text' FROM $wpdb->posts posts WHERE posts.post_type = 'sfwd-courses' AND posts.post_status = 'publish'";

		$courses = $wpdb->get_results($query, OBJECT);
		$data = array();
		$data['results'] = $courses;
		wp_send_json_success($data, JSON_PRETTY_PRINT);
	}

	/**
	 * get selected course for product
	 * @return {JSON} selected courses
	 */
	public static function selectedCourses() {
		// @todo : use post_where filter. Inject the ld-functions/hooks.
		// @todo : https://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
		global $wpdb;

		if (isset($_GET['pid']) && isset($_GET['meta_key'])) {
			$productId = $_GET['pid'];
			$metaKey = $_GET['meta_key'];

			$selectedCourses = unserialize(get_post_meta($productId, $metaKey, true));
			if (BBMS_DEBUG) {
				error_log(print_r($selectedCourses, true));
			}

			// Eg : SELECT ID as 'id', post_title as 'text' FROM wp_posts WHERE ID IN ('1','39','35')
			$query = "SELECT ID as 'id', CONCAT(posts.post_title, \"(ID:\" , posts.ID, \")\")  as 'text' FROM $wpdb->posts posts WHERE posts.ID IN ('" . implode("','", $selectedCourses) . "')";

			$selected = $wpdb->get_results($query, OBJECT);
			$data = array();
			$data['results'] = $selected;
			wp_send_json_success($data, JSON_PRETTY_PRINT);
		} else {
			wp_send_json_error(array('error_msg' => 'Bad request since pid and meta_key is required'), JSON_PRETTY_PRINT);
		}

	}

	/**
	 * Get value if set else return default
	 * @param  {string}   $_GET['search'] - Term searched from UI
	 * @return {JSON} Searched LearnDash group(s) if found
	 */
	public static function getParam($key, $default) {
		if (isset($_GET[$key])) {
			return $_GET[$key];
		} else {
			return $default;
		}
	}

	/**
	 * Search LearnDash groups as Ajax
	 * @param  {string}   $_GET['search'] - Term searched from UI
	 * @return {JSON} Searched LearnDash group(s) if found
	 */
	public static function searchLearndashGroups() {
		// @todo : use post_where filter. Inject the ld-functions/hooks.
		// @todo : https://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
		global $wpdb;

		$search = $_GET['search'];

		$query = "SELECT CONCAT(posts.ID, \":\" , posts.post_title, \"\") as 'id', CONCAT(posts.post_title, \"(ID:\" , posts.ID, \")\")  as 'text' FROM $wpdb->posts posts WHERE posts.post_type = 'groups' AND posts.post_status = 'publish' AND posts.post_title LIKE \"%$search%\" OR posts.ID LIKE \"%$search%\" limit 2";

		$courses = $wpdb->get_results($query, OBJECT);
		$data = array();
		$data['results'] = $courses;
		wp_send_json_success($data, JSON_PRETTY_PRINT);
	}

	/**
	 * get All LearnDash groups for Ajax-call
	 * @return {JSON} All LearnDash groups
	 */
	public static function getLearndashGroupsAsJson() {
		// @todo : use post_where filter. Inject the ld-functions/hooks.
		// @todo : https://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
		global $wpdb;

		$query = "SELECT posts.ID as 'id', CONCAT(posts.post_title, \"(ID:\" , posts.ID, \")\")  as 'text' FROM $wpdb->posts posts WHERE posts.post_type = 'groups' AND posts.post_status = 'publish'";

		$courses = $wpdb->get_results($query, OBJECT);
		$data = array();
		$data['results'] = $courses;
		wp_send_json_success($data, JSON_PRETTY_PRINT);
	}

	/**
	 * get selected course for product
	 * @return {JSON} selected courses
	 */
	public static function selectedGroups() {
		// @todo : use post_where filter. Inject the ld-functions/hooks.
		// @todo : https://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
		global $wpdb;

		if (isset($_GET['pid']) && isset($_GET['meta_key'])) {

			$productId = $_GET['pid'];
			$metaKey = $_GET['meta_key'];

			$selectedCourses = unserialize(get_post_meta($productId, $metaKey, true));
			if (BBMS_DEBUG) {
				error_log(print_r($selectedCourses, true));
			}

			// Eg : SELECT ID as 'id', post_title as 'text' FROM wp_posts WHERE ID IN ('1','39','35')
			$query = "SELECT ID as 'id', CONCAT(posts.post_title, \"(ID:\" , posts.ID, \")\")  as 'text' FROM $wpdb->posts posts WHERE posts.ID IN ('" . implode("','", $selectedCourses) . "')";

			$selected = $wpdb->get_results($query, OBJECT);
			$data = array();
			$data['results'] = $selected;
			wp_send_json_success($data, JSON_PRETTY_PRINT);
		} else {
			wp_send_json_error(array('error_msg' => 'Bad request since pid and meta_key is required'), JSON_PRETTY_PRINT);
		}
	}

	/**
	 * This will verify all cases and return
	 * @param  {int}   $courseId - Unique ID post(sfwd-courses as post_type)
	 * @return {boolean}
	 */
	public static function courseHasAnyMembership($courseId) {
		if (BBMS_DEBUG) {
			error_log("Checking : courseHasAnyMembership($courseId)");
		}
		$responseObj = new \stdClass;

		$responseObj = self::courseMembership($courseId);
		$permitFlag = $responseObj->has_membership;

		return $permitFlag;

	}

	/**
	 * This will create a object with all data/property for course membership
	 * @param  {int}   $courseId - Unique ID post(sfwd-courses as post_type)
	 * @return {object}
	 */
	public static function courseMembership($courseId) {
		$responseObj = new \stdClass();
		$responseObj->has_membership = false;

		$responseObj = self::courseBelongsToMp($courseId, $responseObj);
		if (BBMS_DEBUG) {
			error_log(print_r($responseObj, true));
		}
		if (!$responseObj->has_membership) {
			$responseObj = self::courseBelongsToWc($courseId, $responseObj);
			if (BBMS_DEBUG) {
				error_log(print_r($responseObj, true));
			}
		}

		return $responseObj;

	}

	/**
	 * Runs after a users list of courses is been updated
	 * @param  {string}      $userId
	 * @param  {string}      $courseId
	 * @param  {string}      $courseAccessList - This many users have access to above course($courseId); Eg : 1,12,13
	 * @param {boolean}     $remove
	 * @return {void}
	 */
	public static function learndashUpdateCourseAccess($userId, $courseId, $courseAccessList, $remove) {

		if (BBMS_DEBUG) {
			error_log("learndashUpdateCourseAccess()");
			error_log("userId : $userId");
			error_log("courseId : $courseId");
			error_log("remove : $remove");
			error_log(print_r($courseAccessList, true));
		}

	}

	/**
	 * Runs when Learndash course is been added/updated.
	 * @param  {int}      $postId Post ID.
	 * @param  {WP_Post Object}      $post Post object.
	 * @param  {bool}      $update - Whether this is an existing post being updated or not.
	 * @return {void}
	 */
	public static function learndashCourseAdded($postId, $post, $update) {
		if (BBMS_DEBUG) {
			error_log("learndashCourseAdded()");
		}

		// Only if it is NEW post
		if (!$update) {
		}
	}

	/**
	 * Runs when Learndash group is been added/updated.
	 * @param  {int}      $postId Post ID.
	 * @param  {WP_Post Object}      $post Post object.
	 * @param  {bool}      $update - Whether this is an existing post being updated or not.
	 * @return {void}
	 * @todo : This hook is triggered twice, seems like learndash is trigger save_post_groups as well
	 */
	public static function learndashGroupUpdated($postId, $post, $update) {
		if (BBMS_DEBUG) {
			error_log("learndashGroupUpdated(), update is :  $update");
		}

		// Don't save revisions and autosaves.
		if (wp_is_post_revision($postId) || wp_is_post_autosave($postId) || 'product' !== $post->post_type || !current_user_can('edit_post', $postId)) {
			return $postId;
		}

		// Only if it is UPDATE post
		if ($update) {
		}
	}

	/**
	 * Return All product events
	 */
	public static function getProductEvents() {

		$lmsTypes = self::getLmsTypesSelected();
		$vendorTypes = self::getVendorTypesSelected();

		$products = get_posts(array("post_type" => self::getVendorTypesSelected()));

		$results = array();
		foreach ($products as $product) {

			if ($product->post_type == MP_POST_TYPE) {
				error_log("Count : $count");

				$isEnabled = get_post_meta($product->ID, "_bbms-$lmsTypes-$product->post_type-is_enabled", true);

				// Display only enabled ones
				if ($isEnabled) {
					$events = unserialize(get_post_meta($product->ID, '_bbms-events', true));
					foreach ($events as $eventIdentifier => $eventMeta) {
						$results[$eventIdentifier] = array('event_identifier' => $eventIdentifier, 'user_id' => $eventMeta['user_id'], 'course_attached' => $eventMeta['course_attached'], 'grant_access' => $eventMeta['grant_access'], 'product_id' => $product->ID, 'created_at' => $eventMeta['created_at'], 'updated_at' => $eventMeta['updated_at']);
					}
				}

			} else if ($product->post_type == WC_POST_TYPE) {

			}

		}

		// error_log(print_r($results, true));
		return $results;

	}

	/**
	 * Update BBMS enrolment(grant/revoke) for particular user
	 * @param {int} $userId - Wordpress's unique ID for user identification
	 * @param {array} $activeVendorTypes - Active membership vendors such as memberpressproduct(By memberpress), product(By WooCommerce)
	 * @return {void}
	 */
	public static function updateBbmsEnrollments($userId) {

		if (BBMS_DEBUG) {
			error_log("updateBbmsEnrollments() for userId : $userId");
		}
		$accessList = array();
		$lmsType = self::getLmsTypesSelected();
		$products = get_posts(array("post_type" => self::getVendorTypesSelected()));
		foreach ($products as $product) {

			$isEnabled = get_post_meta($product->ID, "_bbms-$lmsType-$product->post_type-is_enabled", true);
			$courseAccessMethod = get_post_meta($product->ID, "_bbms-$lmsType-$product->post_type-course_access_method", true);

			if ($isEnabled) {

				$events = unserialize(get_post_meta($product->ID, '_bbms-events', true));

				if (is_array($events) && !empty($events)) {
					foreach ($events as $eventIdentifier => $eventMeta) {

						if ($eventMeta['user_id'] == $userId) {
							$coursesEnrolled = unserialize($eventMeta['course_attached']);

							if (is_array($coursesEnrolled) && !empty($coursesEnrolled)) {

								foreach ($coursesEnrolled as $courseId) {

									if (isset($accessList[$courseId])) {
										//NOTE : Change flag to true
										if ($eventMeta['grant_access']) {
											$accessList[$courseId] = true;
										}
									} else {
										//NOTE : Setting up first time value
										$accessList[$courseId] = $eventMeta['grant_access'] ? true : false;
									}

								}
							}

						}
					}
				}

			}

		}

		error_log("accessList is below:");
		error_log(print_r($accessList, true));

		// Grant or Revoke based on grantFlag
		foreach ($accessList as $courseId => $grantFlag) {
			if (self::getLmsTypesSelected(LD_POST_TYPE) == LD_POST_TYPE) {
				$grantFlag ? ld_update_course_access($userId, $courseId, false) : ld_update_course_access($userId, $courseId, true);
			}

		}

	}

	/**
	 * @param  {object}      $vendorObj
	 * @param  {string}      $vendorType - Product post type, eg : memberpressproduct, product
	 * @param  {boolean}     $grantAccess - Whether to grant/revoke access
	 * @return {void}
	 */
	public static function updateBbmsEvent($vendorObj, $vendorType, $grantAccess = true) {

		if (BBMS_DEBUG) {
			error_log("updateBbmsEvent(), $vendorObj->id");
			error_log("updateBbmsEvent(),productPostType :  $vendorType");
		}
		$lmsType = self::getLmsTypesSelected(LD_POST_TYPE);

		if ($vendorType == MP_POST_TYPE) {

			if ($vendorObj->subscription_id == 0) {
				$eventIdentifier = $vendorType . '-' . $vendorObj->id;
			} else {
				$eventIdentifier = $vendorType . '-' . $vendorObj->subscription_id;
			}

			$events = unserialize(get_post_meta($vendorObj->product_id, '_bbms-events', true));
			if (isset($events[$eventIdentifier])) {
				error_log("Event EXISTS for this user, just update grant access");
				error_log(print_r($events[$eventIdentifier], true));

				$events[$eventIdentifier]['grant_access'] = $grantAccess;
				$events[$eventIdentifier]['updated_at'] = date('Y-m-d H:i:s');
			} else {
				error_log("Event DO NOT for this user : $vendorObj->user_id");

				$courseAccessMethod = get_post_meta($vendorObj->product_id, "_bbms-$lmsType-$vendorType-course_access_method", true);
				if ($courseAccessMethod == 'SINGLE_COURSES') {
					if (BBMS_DEBUG) {
						error_log("SINGLE_COURSES selected");
					}
					$coursesAttached = unserialize(get_post_meta($vendorObj->product_id, "_bbms-$lmsType-$vendorType-courses_enrolled", true));

				} else if ($courseAccessMethod == 'ALL_COURSES') {
					if (BBMS_DEBUG) {
						error_log("ALL_COURSES selected");
					}
					$coursesAttached = BbmsHelper::getLearndashClosedCourses();

				} else if ($courseAccessMethod == 'LD_GROUPS') {

					if (BBMS_DEBUG) {
						error_log("LD_GROUPS selected");
					}
					$groupsAttached = unserialize(get_post_meta($vendorObj->product_id, "_bbms-$lmsType-$vendorType-groups_attached", true));
					$coursesAttached = array();
					foreach ($groupsAttached as $groupId) {
						$ids = learndash_group_enrolled_courses($groupId);
						// NOTE : Array format is consistent with GUI
						$coursesAttached = array_merge($ids, $coursesAttached);
					}
				}
				// error_log(print_r($coursesAttached, true));
				// error_log("BEFORE Saving");
				$events[$eventIdentifier] = array('user_id' => $vendorObj->user_id, 'course_attached' => serialize(array_values($coursesAttached)), 'grant_access' => $grantAccess, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'));

			}
			error_log("Events on Product:");
			error_log(print_r($events, true));

			// Finally serialize and update
			update_post_meta($vendorObj->product_id, '_bbms-events', serialize($events));
			BbmsHelper::updateBbmsEnrollments($vendorObj->user_id);

		} else if ($vendorType == WC_POST_TYPE) {
			error_log("IMPLEMENT for wooCommerce");
		}

	}

	/**
	 * Retrieve selected LMS such as sfwd-courses(Learndash) or lifter-courses(LifterLms)
	 * @return {array | null}
	 */
	public static function getLmsTypesSelected($default = null) {

		if (isset(get_option('bbms-settings')['bbms-lms-types'])) {
			return get_option('bbms-settings')['bbms-lms-types'];
		} else {
			return $default;
		}
	}

	/**
	 * Retrieve vendorTypes such as memberpress(Memberpress) or product(WooCommerce)
	 * @return {array | null}
	 */
	public static function getVendorTypesSelected() {

		if (isset(get_option('bbms-settings')['bbms-vendor-types'])) {
			return get_option('bbms-settings')['bbms-vendor-types'];
		} else {
			return null;
		}
	}

	/**
	 * @param  {Object}      $vendorObj
	 * @param  {string}      $vendorType
	 * @param  {boolean}     $grantAccess
	 * @return {void}
	 */
	public static function bbmsUpdateMembershipAccess($vendorObj, $vendorType, $grantAccess = true) {
		if (BBMS_DEBUG) {
			error_log("grant_access is $grantAccess");
		}

		global $wpdb;
		$lmsType = self::getLmsTypesSelected(LD_POST_TYPE);

		if ($vendorType == MP_POST_TYPE) {

			$isEnabled = get_post_meta($vendorObj->product_id, "_bbms-$lmsType-$vendorType-is_enabled", true);
			if ($isEnabled) {
				// NOTE : Update BBMS Event
				BbmsHelper::updateBbmsEvent($vendorObj, $vendorType, $grantAccess);
			}
		} else if ($vendorType == WC_POST_TYPE) {
			//@todo : Finish wooCommerce here
			error_log("Finish wooCommerce here");
		}

	}

	/**
	 * Course access UI(selectbox) option(value and text)
	 * @return {array}
	 */
	public function getCourseOptions() {
		$options = array("SINGLE_COURSES" => "Single courses", "ALL_COURSES" => "All courses", "LD_GROUPS" => "LearnDash groups");
		return $options;
	}

	/**
	 * Get All learndash course which are 'closed'
	 * @param {boolean} $bypass_transient - Whether to bypass or reuse existing transient for quick retrieval
	 * @return {array}
	 */
	public static function getLearndashClosedCourses($bypass_transient = false) {

		global $wpdb;

		$transient_key = "bbms_learndash_closed_courses";

		if (!$bypass_transient) {
			$courses_ids_transient = learndash_get_valid_transient($transient_key);
		} else {
			$courses_ids_transient = false;
		}

		if ($courses_ids_transient === false) {

			$sql_str = "SELECT postmeta.post_id as post_id FROM " . $wpdb->postmeta . " as postmeta INNER JOIN " . $wpdb->posts . " as posts ON posts.ID = postmeta.post_id WHERE posts.post_status='publish' AND posts.post_type='sfwd-courses' AND postmeta.meta_key='_sfwd-courses' AND ( postmeta.meta_value REGEXP '\"sfwd-courses_course_price_type\";s:6:\"closed\";' )";
			$course_ids = $wpdb->get_col($sql_str);

			set_transient($transient_key, $course_ids, MINUTE_IN_SECONDS);

		} else {
			$course_ids = $courses_ids_transient;
		}

		return $course_ids;
	}
}