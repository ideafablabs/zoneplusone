<?php

/**
 * Plugin Name: IFL Zone Plus One
 * Plugin URI:
 * Description: This plugin manages plus ones of zones with tokens.
 * Version: 1.0.0
 * Author: Idea Fab Labs Teams
 * Author URI: https://github.com/ideafablabs/
 * License: GPL3
 */

include 'rest-api.php';

/// These are unintegrated.
include 'class-registration-actions-hooks.php';
include 'referral-program-actions-hooks.php';
include 'emergency-contact-list-actions-hooks.php';

global $wpdb;

define("IFLZPO_TABLE_PREFIX", "iflzpo_");

define("ZONE_TOKENS_TABLE_NAME", $wpdb->prefix . IFLZPO_TABLE_PREFIX . "zone_tokens");
define("ZONE_TOKENS_DB_VERSION", "1.0");

define("ZONES_TABLE_NAME", $wpdb->prefix . IFLZPO_TABLE_PREFIX . "zones");
define("ZONES_DB_VERSION", "1.0");

define("PLUS_ONE_ZONES_TABLE_NAME", $wpdb->prefix . IFLZPO_TABLE_PREFIX . "plus_one_zones");
define("PLUS_ONE_ZONES_DB_VERSION", "1.0");

define("IFLZPO_PLUGIN_PATH", plugin_dir_path(__FILE__));
define("IFLZPO_VIEWS_PATH", IFLZPO_PLUGIN_PATH . "views/");
define("IFLZPO_LOGFOLDER", plugin_dir_path(__FILE__) . "logs/");
define("IFLZPO_LOGFILE", IFLZPO_LOGFOLDER . date('Y-m') . '-log.csv');

define("IFLZPO_PLUGIN_URL", plugins_url( '/' , __FILE__ ));
define("IFLZPO_VIEWS_URL", plugins_url( 'views/' , __FILE__ ));

// Sunset Paths
define("IFLZPO_SUNSET_IMG_URL", IFLZPO_PLUGIN_URL . 'css/img/');
define("IFLZPO_SUNSET_IMG_PATH", IFLZPO_PLUGIN_PATH . 'css/img/');
define("IFLZPO_SUNSET_IMG_FILE", IFLZPO_SUNSET_IMG_PATH . 'sunset-images.txt');



$IFLZonePlusOne = new IFLZonePlusOne;
$IFLZonePlusOne->run();

Class IFLZonePlusOne
{
	private $settings;

	//Default values if options doesn't exist yet.
	private $default_settings = array(
		'iflzpo_mm_api_key' => '',
		'iflzpo_mm_api_secret' => '',
		'iflzpo_token_reader_count' => 1,
		'iflzpo_class_registration_active' => 0,
		'iflzpo_referral_program_active' => 0,
		'iflzpo_emergency_contacts_active' => 0,
		'iflzpo_mm_api_call_url' => '',
	);

	public function run() {
		
		add_action('admin_init', array($this, 'load_settings'));

		// Init Menu Pages
		add_action('admin_menu', array($this, 'admin_menu_pages'));

		// Init REST API
		add_action('rest_api_init', function () {
			
			// Zone Plus One Controller
			$zoneplusone_controller = new ZonePlusOne_Controller();
			$zoneplusone_controller->register_routes();

			// MemberMouse Controller
			$membermouse_controller = new MemberMouse_Controller();
			$membermouse_controller->register_routes();

		});

		// Enqueue plugin styles and scripts
		add_action('admin_enqueue_scripts', array($this, 'register_iflzpo_scripts'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_iflzpo_scripts'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_iflzpo_styles'));

		// AJAX Actions
		add_action('wp_ajax_ifl_sanity_check', array($this, 'ifl_sanity_check'));
		add_action('wp_ajax_nopriv_ifl_sanity_check', array($this, 'ifl_sanity_check'));

		add_action('wp_ajax_iflzpo_async_controller', array($this, 'ajax_controller'));
		add_action('wp_ajax_nopriv_iflzpo_async_controller', array($this, 'ajax_controller'));

		add_shortcode('iflzpo_get_sunset', array($this, 'iflzpo_sunset_function'));

		register_activation_hook( __FILE__, array($this, 'install_plugin'));
	}

	// Load the plugin settings.
	public function load_settings() {
		$this->settings = get_option('iflzpo_settings', $this->default_settings);
	}

	// Update the plugin settings.
	public function update_settings(array $new_settings) {
		$this->settings = array_merge($this->settings, $new_settings);
		update_option('iflzpo_settings', $this->settings);
	}

	function iflzpo_sunset_function( $atts ) {
		include IFLZPO_VIEWS_PATH . 'sunset.inc.php';
	
 	 	die();
	}

	// Prompt ChatGPT
	public function promptChatGPT($prompt) {
		$apiKey = OPENAI_API_KEY;
		$apiUrl = 'https://api.openai.com/v1/chat/completions'; // Replace with the actual ChatGPT 4 API URL if different
	
		$requestData = [        
			'messages' => array(
				array(
					'role' => 'system',
					'content' => 'You are a numerology expert.'
				),
				array(
					'role' => 'user',
					'content' => $prompt
				)
			),
			'model' => 'gpt-4o',
			'max_tokens' => 150, // Adjust the number of tokens as needed
			'temperature' => 0.7, // Adjust the temperature as needed
		];
	
		$ch = curl_init($apiUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Authorization: Bearer ' . $apiKey
		]);
	
		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
		if ($httpCode == 200) {
			$responseData = json_decode($response, true);
			$reply = $responseData['choices'][0]['message']['content'];
			// echo $reply;
			return $reply;
			
		} else {
			return 'Error prompting ChatGPT: ' . $response;
		}
	
		curl_close($ch);
	}

	public function generateImage($prompt) {
		$apiKey = OPENAI_API_KEY; // Replace with your actual API key
		$apiUrl = 'https://api.openai.com/v1/images/generations'; // Replace with the actual DALL-E 3 API URL if different
	
		$requestData = [
			'prompt' => $prompt,
			'model' => 'dall-e-3',
			'n' => 1, // Number of images to generate
			'size' => '1792x1024' // Image size
			
		];
	
		$ch = curl_init($apiUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
		curl_setopt($ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Authorization: Bearer ' . $apiKey
		]);
	
		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
		if ($httpCode == 200) {
			$responseData = json_decode($response, true);
			$imageUrl = $responseData['data'][0]['url'];
			return $imageUrl;
		} else {
			return 'Error generating image: ' . $response;
		}
	
		curl_close($ch);
	}


	public function get_list_of_active_membermouse_users() {
			
		global $wpdb;

		// MM User Status IDs
		//	1 = Active
		// 2 = cancelled
		// 5 = Overdue
		// 4 = Paused
		// 9 = Pending Cancellation

		$sql = "SELECT first_name, last_name FROM mm_user_data WHERE status = 1 OR status = 5 OR status = 9";

		$result = $wpdb->get_results($sql);

		// pr($result);

		return (array) $result;
		
	}

	// Async Controller
	// Easiest Overview of AJAX Setup for WP: https://stackoverflow.com/questions/17982078/returning-json-data-with-ajax-in-wordpress
	public function ajax_controller() { 

		// Switch on 'request' post var
		$request = (!empty($_POST['request'])) ? $_POST['request'] : false;
		$package = (!empty($_POST['package'])) ? $_POST['package'] : false;
		
		$return['success'] = false;
		$return['notice'] = array(
			'display' => true, 
			'dismissible' => true, 
			'level' => '',
		);
		
		switch ($request) {
			case 'add_token':
				
				// Get the User ID from the AJAX request.
				$user_id = (!empty($package['uid'])) ? $package['uid'] : false;
				
				// If not there, fail.
				if ($user_id === false) {
					$return['message'] = "No user ID Found";
					break;
				} 

				// Get the latest stored token from WP options table..
				$token_id = get_option('token_id');
				// $token_id = rand(10000,90000); ///

				// Try and add to token table.
				/// this should be a try{}...
				$response = $this->add_zone_token_to_zone_tokens_table($token_id,$user_id);
				// $response = "zing";

				// Did we fail?
				if (is_wp_error($response)) {
					$return['message'] = $response->get_error_message();
				} else {
					$return['success'] = true;
					$return['token_id'] = $token_id;
					$return['message'] = $response;
				}

				break;
			case 'remove_token':
				// Get the Token ID from the AJAX request.
				$token_id = (!empty($package['tid'])) ? $package['tid'] : false;
				
				// If not there, fail.
				if ($token_id === false) {
					$return['message'] = "No Token ID Found";
					break;
				} 

				// Get user ID because its currently necessary for deleting a zone token, /// kind of as a safeguard.
				$user_id = $this->get_user_id_from_zone_token_id($token_id);

				// Try and add to token table.
				/// this should be a try{}...
				$response = $this->delete_zone_token($token_id,$user_id);
				// $response = "";///

				// Did we fail?
				if (is_wp_error($response)) {
					$return['message'] = $response->get_error_message();
				} else {
					$return['success'] = true;
					$return['token_id'] = $token_id;
					$return['message'] = $response;
				}

				break;
			default:
				// err out                
				$return['message'] = "Bad request object";                
				break;
		}

		self::log_action($return['message']);

		// Wrap our return response. 
		echo json_encode($return);

		// Always wp_die().
		wp_die();
	}

	public function ajax_nopriv_controller() {}

	public function ifl_sanity_check() {
		// Always die in functions echoing Ajax content
		wp_die('Sane!');
	 }

	public function admin_menu_pages() {
				
		add_menu_page(
			__('Zone +1 Admin', 'textdomain'),  	// Page Title
			'Zone +1',                          	// Menu Title
			'manage_options',                   	// Required Capability
			'iflzpo_dashboard',         				// Menu Slug
			array($this, 'admin_view_controller'),	// Function
			'dashicons-plus',								// Icons
			6
		); 

		add_submenu_page('iflzpo_dashboard',
			"Manage Zone Names",
			"Manage Zone Names",
			'manage_options',
			"iflzpo_manage_zones",
			array($this, 'admin_view_controller')
		);
		add_submenu_page('iflzpo_dashboard',
			"Manage User Tokens",
			"Manage User Tokens",
			'manage_options',
			"iflzpo_members",
			array($this, 'admin_view_controller')
		);		
		add_submenu_page('iflzpo_dashboard',
			"Settings",
			"Settings",
			'manage_options',
			"iflzpo_settings",
			array($this, 'admin_view_controller')
		);
	}

	/**
	 * Admin page controller. Pulls View template files.
	 * @init action
	 */
	public function admin_view_controller() {
		global $plugin_page, $wpdb;
		$page_title = get_admin_page_title();

		/// https://digwp.com/2016/05/wordpress-admin-notices/

		switch ($plugin_page) {
			case 'iflzpo_dashboard':
				include IFLZPO_VIEWS_PATH . 'dashboard.inc.php';
				break;			
			case 'iflzpo_members':
				include IFLZPO_VIEWS_PATH . 'member-manager.inc.php';
				break;
			case 'iflzpo_manage_zones':
				include IFLZPO_VIEWS_PATH . 'zones-manager.inc.php';
				break;	
			case 'iflzpo_settings':
				include IFLZPO_VIEWS_PATH . 'settings.inc.php';
				break;	
			default:
				# code...
				break;
		}

	}

	public function admin_dashboard_page() {

	}

	public function manage_user_tokens_page() { 
		$page_name = 'manage_user_tokens_page';

		$response = ""; // Begin output.
		/// We really should switch to templates.
		
		$member_class = ""; /// ?
		
		$user_id = (isset($_GET['user_id'])) ? $_GET['user_id'] : "";
		$reader_id = (isset($_GET['reader_id'])) ? $_GET['reader_id'] : "";

		if (empty($user_id)) {

			// Get the users from the DB...
			$users = get_users(array('orderby' => 'display_name', 'fields' => 'all_with_meta'));

			// Build search HTML.
			$response .= '<div class="member_select_search"><span class="glyphicon glyphicon-user"></span><input type="text" name="q" value="" placeholder="Search for a member..." id="q"><button  class="clear-search" onclick="document.getElementById(\'q\').value = \'\';$(\'.member_select_search #q\').focus();">Clear</button></div>';

			$response .= '<div class="ajax-message"></div>';

			// Build list HTML
			// $response .= '<ul class="member_select_list list-group">';
			$response .= '<table border="0" class="member_select_list list-group">';
			$response .= '<tr class="member_select_list_head">
							<th>Name</th>
							<th>Email</th>
							<th>Tokens</th>
							<th>Add</th>';

			// Build links for each member...
			foreach ($users as $key => $user) {

				// $formlink = '/wp-admin/admin.php?page='.$page_name.'&user_id=' . $user->ID . '&reader_id=' . $reader_id;

				$query = array(
					'user_id' => $user->ID,                     
				);
				$formlink = esc_url( add_query_arg( $query ) );
 
				///DUMMY ADD TOKENS
				// $res = $this->add_zone_token_to_zone_tokens_table(rand(20,10000),$user->ID);

				// Get users tokens array.
				$tokens = $this->get_zone_token_ids_by_user_id($user->ID);
			   
				$response .= '<tr  class="user-'.$user->ID.'" data-user-id="'.$user->ID.'" data-sort="' . $user->display_name . '">
					<td class="user-displayname">'.$user->display_name.'</td>
					<td class="user-email">'. $user->user_email.'</td>
					<td class="user-tokens">'; 
					if (is_array($tokens)) {
						$response .= '<ul>';
						foreach ($tokens as $key => $token_id) {
							$response.= '<li>'.$token_id.' <a class="remove-token icon" data-tid="'.$token_id.'">x</a></li>';
						}
						$response .= '</ul>';
					} else {
						$response.= '<span>'.$tokens.'</span>';   
					}
					$response .= '</td>
					<td><a class="add-token" data-uid="'.$user->ID.'">Get New Token</a><span class="new-token"></span></td>

					</tr>';

				// $response .= '<li class="list-group-item list-group-item-action" data-sort="' . $user->display_name . '">
				// <span class="glyphicon glyphicon-user"></span>
				// <a id="' . $user->ID . '" class=" ' . $member_class . '" href="' . $formlink . '">
				// <span class="member-displayname">' . $user->display_name . '</span>
				// <br /><span class="member-email">' . $user->user_email . '</span></a>
				// </li>';
			}
			$response .= '</table>';
			// $response .= '</ul>';

		} else {            

			//  We have the user ID so let's show the page where we associate the NFC Token
			$user = get_user_by('ID', $user_id);
			$token_id = get_option('token_id');
			$associate_link = '';

			$response .= '<h2>'.$user->display_name.'</h2>';
			$response .= '<p>Current Token ID:'.$token_id.'</p>';
			$response .= '<p><a href="'.$associate_link.'" title="Associate">Associate this Token with '.$user->display_name.'</a></p>';

		}
		echo $response;

	}

	public function assign_token_to_user_page() {
		if (isset($_POST['submit_user_id_and_token_id'])) {
			$token = trim($_POST['token_id']);
			$user_id = $_POST['selected_user'];

			$result = self::add_zone_token_to_zone_tokens_table($token, $user_id);
			if (is_wp_error($result)) {
				echo "<p style='color:Red'><b><i>Error - " . $result->get_error_message() . "</i></b></p>";
			} else {
				echo "<p style='color:Blue'><b><i>" . $result . "</i></b></p>";
			}
		}

		echo "<h1>Assign Token to User</h1>";

		$users = get_users("orderby=display_name");

		echo "<br><form name='form_assign_token' method='post' action=''><table><tr><th align='left'>Select User</th><th colspan='2' align='left'>Enter Token ID</th></tr>";
		echo "<tr><td><select id='selected_user' name='selected_user'>";
		foreach ($users as $key => $user) {
			echo "<option value='" . strval($user->ID) . "'>" . $user->display_name . "</option>";
		}
		echo "</td><td><input type='text' name='token_id' id='token_id' value=''/></td>
		<td><input type='submit' name='submit_user_id_and_token_id' value='Assign Token'/></td></tr></table></form>";
	}

	public function create_zones_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE " . ZONES_TABLE_NAME . " (
			  record_id mediumint(9) NOT NULL AUTO_INCREMENT,
			  zone_name tinytext NOT NULL,
			  PRIMARY KEY  (record_id)
			) " . $charset_collate . ";";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		add_option('zones_db_version', ZONES_DB_VERSION);

		self::log_action("Zones table created");
	}

	public function create_zone_tokens_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE " . ZONE_TOKENS_TABLE_NAME . " (
			  record_id mediumint(9) NOT NULL AUTO_INCREMENT,
			  user_id bigint(20) unsigned NOT NULL,
			  token_id tinytext NOT NULL,
			  PRIMARY KEY  (record_id),
			  FOREIGN KEY  (user_id) REFERENCES wp_users(ID)
			) " . $charset_collate . ";";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		add_option('zone_tokens_db_version', ZONE_TOKENS_DB_VERSION);

		self::log_action("Zone tokens table created");
	}

	public function create_plus_one_zones_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE " . PLUS_ONE_ZONES_TABLE_NAME . " (
			  record_id mediumint(9) NOT NULL AUTO_INCREMENT,
			  user_id bigint(20) unsigned NOT NULL,
			  zone_id mediumint(9) NOT NULL,
			  date date NOT NULL,
			  PRIMARY KEY  (record_id),
			  FOREIGN KEY  (user_id) REFERENCES wp_users(ID),
			  FOREIGN KEY  (zone_id) REFERENCES " . ZONES_TABLE_NAME . "(record_id)
			) " . $charset_collate . ";";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		add_option('plus_one_zones_db_version', PLUS_ONE_ZONES_DB_VERSION);

		self::log_action("Plus one zones table created");
	}

	public static function does_zones_table_exist_in_database() {
		return self::does_table_exist_in_database(ZONES_TABLE_NAME);
	}

	public static function does_zone_tokens_table_exist_in_database() {
		return self::does_table_exist_in_database(ZONE_TOKENS_TABLE_NAME);
	}

	public static function does_plus_ones_zones_table_exist_in_database() {
		return self::does_table_exist_in_database(PLUS_ONE_ZONES_TABLE_NAME);
	}

	public static function does_table_exist_in_database($table_name) {
		global $wpdb;
		$mytables = $wpdb->get_results("SHOW TABLES");
		foreach ($mytables as $mytable) {
			foreach ($mytable as $t) {
				if ($t == $table_name) {
					return true;
				}
			}
		}
		return false;
	}

	public function is_zones_table_empty() {
		return self::is_table_empty(ZONES_TABLE_NAME);
	}

	public function is_zone_tokens_table_empty() {
		return self::is_table_empty(ZONE_TOKENS_TABLE_NAME);
	}

	public function is_plus_one_zones_table_empty() {
		return self::is_table_empty(PLUS_ONE_ZONES_TABLE_NAME);
	}

	public function is_table_empty($table_name) {
		global $wpdb;
		$rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . $table_name);
		return $rows[0]->num_rows == 0;
	}

	public function delete_all_zones_from_zones_table() {
		self::delete_all_rows_from_table(ZONES_TABLE_NAME);
	}

	public function delete_all_zone_tokens_from_zone_tokens_table() {
		self::delete_all_rows_from_table(ZONE_TOKENS_TABLE_NAME);
	}

	public function delete_all_plus_one_zones_from_plus_one_zones_table() {
		self::delete_all_rows_from_table(PLUS_ONE_ZONES_TABLE_NAME);
	}

	public function delete_all_rows_from_table($table_name) {
		global $wpdb;
		$result = $wpdb->query("TRUNCATE TABLE " . $table_name);
	}

	public function drop_zones_table() {
		self::drop_table(ZONES_TABLE_NAME);
	}

	public function drop_zone_tokens_table() {
		self::drop_table(ZONE_TOKENS_TABLE_NAME);
	}

	public function drop_plus_one_zones_table() {
		self::drop_table(PLUS_ONE_ZONES_TABLE_NAME);
	}

	public function drop_table($table_name) {
		global $wpdb;
		$result = $wpdb->query("DROP TABLE IF EXISTS " . $table_name);
	}

	public function add_zone_token_to_zone_tokens_table($token_id, $user_id) {
		global $wpdb;
		if (!self::does_table_exist_in_database(ZONE_TOKENS_TABLE_NAME)) {
			return new WP_Error('no-token-table', "Zone tokens table does not exist in database");
			// return "Error - zone tokens table does not exist in database";
		}
		$token_id = trim($token_id);
		if ($token_id == "") {
			return new WP_Error('no-token-id', "Empty zone token ID");
			// return "Error - empty zone token ID";
		} else if (!preg_match("/^\d+$/", $token_id)) {
			return new WP_Error('non-numeric-token-id', "Non-numeric characters in zone token ID");
		}

		$user_id = trim($user_id);
		if ($user_id == "") {
			return new WP_Error('no-user-id', "Empty User ID");
			// return "Error - empty user ID";
		}

		if (!self::is_user_id_in_database($user_id)) {
			return new WP_Error('user-missing', "User ID " . $user_id . " is not a registered user");
			// return "Error - user ID " . $user_id . " is not a registered user";
		}

		$result = $wpdb->get_results("SELECT * FROM " . ZONE_TOKENS_TABLE_NAME . " WHERE token_id = '" . $token_id . "'");
		if ($wpdb->num_rows != 0) {
			$user_id_already_registered = $result[0]->user_id;
			if ($user_id_already_registered != $user_id) {
				return new WP_Error('user-mismatch', "Token ID " . $token_id . " is already registered to a different userID " . $user_id_already_registered . " (" . self::get_user_name_from_user_id($user_id_already_registered) . ")");
				// return "Error - zone token ID " . $token_id . " is already registered to a different userID (" . $user_id_already_registered . ")";
			}
			return new WP_Error('user-duplicate', "Token ID " . $token_id . " is already registered to that user ID (" . $user_id . ")");
			// return "Zone token ID " . $token_id . " is already registered to that user ID (" . $user_id . ")";
		}
		$wpdb->insert(
			ZONE_TOKENS_TABLE_NAME,
			array(
				'token_id' => $token_id,
				'user_id' => $user_id,
			)
		);
		$result = $wpdb->get_results("SELECT * FROM " . ZONE_TOKENS_TABLE_NAME . " WHERE token_id = '" . $token_id . "'");
		if ($wpdb->num_rows != 0) {
			return "Zone token ID " . $token_id . " successfully assigned to user ID " . $user_id . " (" . self::get_user_name_from_user_id($user_id) . ") in the zone tokens table";
		} else {
			return new WP_Error('unknown-error', "Error adding zone token ID to the tokens table");
			// return "Error adding zone token ID to the tokens table";
		}
	}

	public function delete_zone_token($token_id, $user_id) {
		// This function removes a token from the zone tokens table, so that that token is no longer registered to
		// any user, but it does not affect any records in the plus one zones table, because those are tied to users
		// rather than individual tokens.
		global $wpdb;
		
		if (!self::does_table_exist_in_database(ZONE_TOKENS_TABLE_NAME)) {
			return new WP_Error('no-token-table', "Zone tokens table does not exist in database");
		}

		$token_id = trim($token_id);
		
		if ($token_id == "") {
			return new WP_Error('no-token-id', "Empty zone token ID");
		} else if (!preg_match("/^\d+$/", $token_id)) {
			return new WP_Error('non-numeric-token-id', "Non-numeric characters in zone token ID");
		}

		$user_id = trim($user_id);
		if ($user_id == "") {
			return new WP_Error('no-user-id', "Empty User ID");
		}

		if (!self::is_user_id_in_database($user_id)) {
			return new WP_Error('user-missing', "User ID " . $user_id . " is not a registered user");
		}

		$response = $this->get_user_id_from_zone_token_id($token_id);
		if (is_wp_error($response)) {
			return $response;
		} else {
			$user_id_registered_to_that_token = $response;
		}

		if ($user_id_registered_to_that_token != $user_id) {
			return new WP_Error('user-mismatch', "Token ID " . $token_id . " is registered to a different userID " . $user_id_registered_to_that_token . " {" . self::get_user_name_from_user_id($user_id_registered_to_that_token) . ")");
		}

		$wpdb->delete(
			ZONE_TOKENS_TABLE_NAME,
			array(
				'token_id' => $token_id,
			)
		);

		$result = $wpdb->get_results("SELECT * FROM " . ZONE_TOKENS_TABLE_NAME . " WHERE token_id = '" . $token_id . "'");
		if ($wpdb->num_rows == 0) {
			return "Zone token ID " . $token_id . " successfully deleted from zone tokens table";
		} else {
			return new WP_Error('unknown-error', "Error deleting zone token ID " . $token_id . " from the tokens table");
		}
	}

	public function add_zone_to_zones_table($zone_name) {
		// This function is used by the Manage Zone Names submenu page, and should probably not be used by an API
		global $wpdb;
		$zone_name = trim($zone_name);
		if ($zone_name == "") {
			return "Error - empty zone name";
		}
		// TODO figure out a good way to handle not letting people enter multiple versions of existing zone names --
		if (!self::does_table_exist_in_database(ZONES_TABLE_NAME)) {
			return "Error - zones table does not exist in database";
		}

		$result = $wpdb->get_results("SELECT * FROM " . ZONES_TABLE_NAME . " WHERE zone_name = '" . $zone_name . "'");
		if ($wpdb->num_rows != 0) {
			return "Zone " . $zone_name . " already exists in the zones table";
		}
		$wpdb->insert(
			ZONES_TABLE_NAME,
			array(
				'zone_name' => $zone_name,
			)
		);
		$result = $wpdb->get_results("SELECT * FROM " . ZONES_TABLE_NAME . " WHERE zone_name = '" . $zone_name . "'");
		if ($wpdb->num_rows != 0) {
			return "Zone " . $zone_name . " successfully added to the zones table";
		} else {
			return "Error adding zone to the zones table";
		}
	}

	public function add_plus_one_to_plus_one_zones_table($zone_id, $token_id) {
		global $wpdb;
		if (!self::does_table_exist_in_database(ZONES_TABLE_NAME)) {
			return new WP_Error('zone-table-missing', "Zones table does not exist in database");
			// return "Error - zones table does not exist in database";
		}

		if (!self::does_table_exist_in_database(ZONE_TOKENS_TABLE_NAME)) {
			return new WP_Error('token-table-missing', "Zone Tokens table does not exist in database");
			// return "Error - zone tokens table does not exist in database";
		}

		if (!self::does_table_exist_in_database(PLUS_ONE_ZONES_TABLE_NAME)) {
			return new WP_Error('plusone-table-missing', "Plus One Zones table does not exist in database");
			// return "Error - plus one zones table does not exist in database";
		}

		$result = $wpdb->get_results("SELECT * FROM " . ZONES_TABLE_NAME . " WHERE record_id = '" . $zone_id . "'");
		if ($wpdb->num_rows == 0) {
			return new WP_Error('zone-id-missing', "Zone ID " . $zone_id . " does not exist in the zones table");
			// return "Error - zone ID " . $zone_id . " does not exist in the zones table";
		}

		$response = $this->get_user_id_from_zone_token_id($token_id);
		if (is_wp_error($response)) {
			return $response;
		} else {
			$user_id = $response;
		}
		// if (substr($user_id, 0, 5) === "Error") {}

		if ($this->user_already_plus_oned_this_zone_today($user_id, $zone_id)) {
			return new WP_Error('quota-met', "User " . $this->get_user_name_from_user_id($user_id) . " already plus-one'd the " . $this->get_zone_name_from_zone_id($zone_id) . " today");
			// return "Error - user " . $this->get_user_name_from_user_id($user_id) . " already plus-one'd the " . $this->get_zone_name_from_zone_id($zone_id) . " today";
		}

		// TODO get local time instead of forcing California time
		date_default_timezone_set("America/Los_Angeles");
		$date = date("Y-m-d H:i:s");
		// Or enter a date like this for testing with total vs current month
		// $date = "2019-11-02";

		$wpdb->insert(
			PLUS_ONE_ZONES_TABLE_NAME,
			array(
				'user_id' => $user_id,
				'zone_id' => $zone_id,
				'date' => $date,
			)
		);
		$result = $wpdb->get_results("SELECT * FROM " . PLUS_ONE_ZONES_TABLE_NAME . " WHERE user_id = '" . $user_id . "' AND zone_id = '" . $zone_id . "'");
		if ($wpdb->num_rows != 0) {
			return $this->get_zone_name_from_zone_id($zone_id) . " plus one for user " . $this->get_user_name_from_user_id($user_id) . " successfully added to the plus one zones table";
		} else {
			return new WP_Error('unknown-error', "Error adding Plus One to the Plus One Zones table");
			// return "Error adding plus one to the plus one zones table";
		}
	}

	public function edit_zone_name_in_zones_table($zone_id, $edited_zone_name) {
		// This function is used by the Manage Zone Names submenu page, and should probably not be used by an API
		global $wpdb;
		$edited_zone_name = trim($edited_zone_name);
		if ($edited_zone_name == "") {
			return "Error - empty zone name";
		}
		// TODO figure out a good way to handle not letting people enter multiple versions of existing zone names --
		if (!self::does_table_exist_in_database(ZONES_TABLE_NAME)) {
			return "Error - zones table does not exist in database";
		}
		$result = $wpdb->get_results("SELECT * FROM " . ZONES_TABLE_NAME . " WHERE zone_name = '" . $edited_zone_name . "' COLLATE utf8mb4_bin");
		if ($wpdb->num_rows != 0) {
			return "Error - a zone with the name " . $edited_zone_name . " already exists in the zones table";
		}
		$result = $wpdb->get_results("SELECT * FROM " . ZONES_TABLE_NAME . " WHERE record_id = '" . $zone_id . "'");
		if ($wpdb->num_rows == 0) {
			return "Error - zone ID " . $zone_id . " does not exist in the zones table";
		}
		$result = $wpdb->update(
			ZONES_TABLE_NAME,
			array(
				'zone_name' => $edited_zone_name
			),
			array('record_id' => $zone_id)
		);
		if ($result === false) {
			return "Error updating zone name";
		} else {
			return "Zone name successfully updated to " . $edited_zone_name;
		}
	}

	public static function is_user_id_in_database($user_id) {
		return get_user_by("ID", $user_id) != null;
	}

	public static function get_user_id_from_zone_token_id($token_id) {
		// if the token ID is in the tokens table, returns associated user ID as string,
		// otherwise returns an error message
		global $wpdb;
		if (!self::does_table_exist_in_database(ZONE_TOKENS_TABLE_NAME)) {
			return new WP_Error('token-table-missing', "Zone tokens table does not exist in database");
			// return "Error - zone tokens table does not exist in database";

		}
		$result = $wpdb->get_results("SELECT user_id FROM " . ZONE_TOKENS_TABLE_NAME . " WHERE token_id = '" . $token_id . "'");
		if ($wpdb->num_rows == 0) {
			return new WP_Error('unknown-token', "Zone token ID " . $token_id . " not found in database, you need to register it to a user ID");
			// return "Error - zone token id " . $token_id . " not found in database, you need to register it to a user ID";
		} else {
			return $result[0]->user_id;
		}
	}

	public function get_user_name_from_user_id($user_id) {
		global $wpdb;
		if (!self::is_user_id_in_database($user_id)) {
			return "Error - user ID " . $user_id . " is not a registered user";
		}
		$user = get_userdata($user_id);
		return $user->display_name;
	}

	public function get_zone_name_from_zone_id($zone_id) {
		global $wpdb;
		$result = $wpdb->get_results("SELECT * FROM " . ZONES_TABLE_NAME . " WHERE record_id = '" . $zone_id . "'");
		if ($wpdb->num_rows == 0) {
			return "Error - zone id " . $zone_id . " doesn't exist in the zones table";
		}
		return $result[0]->zone_name;
	}

	public static function get_zone_token_ids_by_user_id($user_id) {
		// if the user ID is in the tokens table, returns associated token ID(s) as ", "-separated string,
		// otherwise returns an error message
		global $wpdb;
		if (!self::is_user_id_in_database($user_id)) {
			return "Error - user ID " . $user_id . " is not a registered user";
		}

		if (!self::does_table_exist_in_database(ZONE_TOKENS_TABLE_NAME)) {
			return "Error - tokens table does not exist in database";
		}

		$result = $wpdb->get_results("SELECT token_id FROM " . ZONE_TOKENS_TABLE_NAME . " WHERE user_id = '" . $user_id . "'");
		if ($wpdb->num_rows == 0) {
			return "Error - no zone tokens found for user ID " . $user_id;
		} else {
			// return $result;
			// pr($result);
			return array_map(function ($token) {
				return $token->token_id;
			}, $result);
			
			// return join(", ", array_map(function ($token) {
			//     return $token->token_id;
			// }, $result));
		}
	}

//    public function list_users_with_ids() {
//        $users = get_users("orderby=ID");
//        foreach ($users as $key => $user) {
//
//            echo "User ID " . $user->ID . " " . $user->display_name . "<br>";
//        }
//    }

	public function test_zone_tokens_table_stuff() {
		global $wpdb;
		echo "<br>";
		if ($this->does_table_exist_in_database(ZONE_TOKENS_TABLE_NAME)) {
			echo "Zone tokens table exists<br>";
			$rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . ZONE_TOKENS_TABLE_NAME);
			echo "Zone tokens table contains " . $rows[0]->num_rows . " records.<br>";
		} else {
			echo "Zone tokens table does not exist, creating zone tokens table<br>";
			$this->create_zone_tokens_table();
		}
	}

	public function test_zones_table_stuff() {
		global $wpdb;
		echo "<br>";
		if ($this->does_table_exist_in_database(ZONES_TABLE_NAME)) {
			echo "Zones table exists<br>";
			$rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . ZONES_TABLE_NAME);
			echo "Zones table contains " . $rows[0]->num_rows . " records.<br>";
		} else {
			echo "Zones table does not exist, creating zones table<br>";
			$this->create_zones_table();
		}
	}

	public function test_plus_one_zones_table_stuff() {
		global $wpdb;
		echo "<br>";
		// $this->drop_plus_one_zones_table();
		if ($this->does_table_exist_in_database(PLUS_ONE_ZONES_TABLE_NAME)) {
			echo "Plus one zones table exists<br>";
			$rows = $wpdb->get_results("SELECT COUNT(*) as num_rows FROM " . PLUS_ONE_ZONES_TABLE_NAME);
			echo "Plus one zones table contains " . $rows[0]->num_rows . " records.<br>";
			echo "Plus one total for zone 1: " . $this->get_total_plus_one_count_by_zone_id(1) . "<br>";
			echo "Plus one total for zone 8: " . $this->get_total_plus_one_count_by_zone_id(8) . "<br>";
			$zpo_array = $this->get_zone_plus_ones_array_for_dashboard();
			echo "<br>Zones plus one dashboard array length " . sizeof($zpo_array) . "<br>";
			foreach ($zpo_array as $entry) {
				echo $entry["zone_name"] . " total plus-ones count: " . $entry["total_plus_one_count"] . ", and plus-ones count for this month: " . $entry["this_month_plus_one_count"] . "<br>";
			}
			echo "Or for the JSON version, " . json_encode($zpo_array) . "<br>";
		} else {
			echo "Plus one zones table does not exist, creating plus one zones table<br>";
			$this->create_plus_one_zones_table();
		}
		//Add a plus-one
		$response = $this->add_plus_one_to_plus_one_zones_table(3, 1);
		if (is_wp_error($response)) {
			$response->get_error_messages();
		} else {
			echo $response . '<br/>';
		}
	}

	public function get_total_plus_one_count_by_zone_id($zone_id) {
		global $wpdb;
		$wpdb->get_results("SELECT * FROM " . PLUS_ONE_ZONES_TABLE_NAME . " WHERE zone_id = '" . $zone_id . "'");
		return $wpdb->num_rows;
	}

	public function get_this_months_plus_one_count_by_zone_id($zone_id) {
		global $wpdb;
		$results = $wpdb->get_results("SELECT * FROM " . PLUS_ONE_ZONES_TABLE_NAME . " WHERE zone_id = '" . $zone_id . "' AND date >=  DATE_FORMAT(NOW() ,'%Y-%m-01')");
		return $wpdb->num_rows;
	}

	public function get_zone_plus_ones_array_for_dashboard() {
		// This returns an array for the dashboard to use -- a row for each zone, ordered by zone name, with the fields "zone_name", "total_plus_one_count", and "this_month_plus_one_count".
		
		// The API function calling this should use json_encode() to send the array as a JSOB string
		$zones_plus_one_array = array();
		global $wpdb;
		if (self::does_table_exist_in_database(ZONES_TABLE_NAME) && self::does_table_exist_in_database(PLUS_ONE_ZONES_TABLE_NAME)) {
			$zone_names_result = $wpdb->get_results("SELECT * FROM " . ZONES_TABLE_NAME . " ORDER BY zone_name");
			foreach ($zone_names_result as $key => $zone) {
				$zone_row = array();
				$zone_row["zone_name"] = $zone->zone_name;
				$id = $zone->record_id;
				$zone_row["zone_id"] = $id;
				$zone_row["total_plus_one_count"] = $this->get_total_plus_one_count_by_zone_id($id);
				$zone_row["this_month_plus_one_count"] = $this->get_this_months_plus_one_count_by_zone_id($id);
				array_push($zones_plus_one_array, $zone_row);
			}
		}
		
		return $zones_plus_one_array;
	}

	public function user_already_plus_oned_this_zone_today($user_id, $zone_id) {
		global $wpdb;
		$results = $wpdb->get_results("SELECT * FROM " . PLUS_ONE_ZONES_TABLE_NAME . " WHERE zone_id = '" . $zone_id . "' AND user_id = '" . $user_id . "' AND date =  DATE_FORMAT(NOW() ,'%Y-%m-%d')");
		return $wpdb->num_rows != 0;
	}

	public static function log_action($item, $echo = 0) {
		
		/// consider adding source to the arguments to determine where the action originated.
		
		if (!self::check_log_file_exists()) return false;

		date_default_timezone_set("America/Los_Angeles");
		$date = date("Y-m-d H:i:s");

		if (is_array($item)) {
			if (is_wp_error($item)) {
				$message = $item->get_error_message();
			} else {
				$message = implode(", ", $item);
			}
		} else {
			$message = $item;
		}

		error_log($date . ", " . $message. "\n", 3, IFLZPO_LOGFILE);
		if ($echo) echo $message;
	}

	public static function check_log_file_exists() {

		// Permissions?
		if (!file_exists(IFLZPO_LOGFOLDER)) {
			try {
				mkdir(IFLZPO_LOGFOLDER);
			} catch (Exception $e) {
				error_log($e->getMessage(), "\n");
				return false;
			}
		}
		if (!file_exists(IFLZPO_LOGFILE)) {
			try {
				$logfile = file_put_contents(IFLZPO_LOGFILE, "\xEF\xBB\xBF",0);				
			} catch (Exception $e) {
				error_log($e->getMessage(), "\n");
				return false;
			}
		}
		return true;
	}

	public function is_plugin_active($plugin_path) {
		$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));

		$case = false;

		foreach ($active_plugins as $plugin) {
			if ($plugin == $plugin_path) {
				$case = true;
			}
		}

		return $case;
	}

	/**
	 * Register plugin styles and scripts
	 */
	public function register_iflzpo_scripts() {
		wp_register_script('iflzpo-script', plugins_url('js/iflzpo.js', __FILE__), array('jquery'), null, true);
		wp_register_style('iflzpo-style', plugins_url('css/iflzpo.css', __FILE__));
	}

	/**
	 * Enqueues plugin-specific scripts.
	 */
	public function enqueue_iflzpo_scripts() {
		wp_enqueue_script('iflzpo-script');
		
		// AJAX URL Localization
		wp_localize_script('iflzpo-script', 'iflzpo_ajax', array(
			'ajaxurl' => admin_url('admin-ajax.php'), 
			'check_nonce' => wp_create_nonce('iflzpo-nonce'))
		);
	}

	/**
	 * Enqueues plugin-specific styles.
	 */
	public function enqueue_iflzpo_styles() {
		wp_enqueue_style('iflzpo-style');        
	}

	public function whenIsSunsetToday() {
		// See https://catalog.data.gov/dataset/tsa-sunrise-sunset-times-api
		$curl = curl_init();
		// 36.974117, -122.030792
		curl_setopt($curl, CURLOPT_URL, "https://api.sunrise-sunset.org/json?lat=36.974117&lng=-122.030792&formatted=0&date=today");
		// curl_setopt($curl, CURLOPT_URL, "http://apps.tsa.dhs.gov/MyTSAWebService/GetEventInfo.ashx?eventtype=sunset&airportcode=SJC&output=json");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$result = json_decode(curl_exec($curl), true)["results"]["sunset"];

		curl_close($curl);
		return $result;
	}

	function install_plugin() {
		global $wpdb;
		
		self::log_action("Installing IFLZPO...");
		
		if (!self::does_table_exist_in_database(ZONES_TABLE_NAME)) {
			self::create_zones_table();				
			
		} else {
			self::log_action("Zones Table already exists.");
		}
		if (!self::does_table_exist_in_database(ZONE_TOKENS_TABLE_NAME)) {
			self::create_zone_tokens_table();
		} else {
			self::log_action("Zones Tokens Table already exists.");
		}
		if (!self::does_table_exist_in_database(PLUS_ONE_ZONES_TABLE_NAME)) {
			self::create_plus_one_zones_table();
		} else {
			self::log_action("Zone Plus One Table already exists.");
		}

	}

}

if (!function_exists("pr")) {
	function pr($input) {
		echo '<pre>';
		print_r($input);
		echo '</pre>';
	}	
}


function errout($errors) {
	if (is_wp_error($errors)) {
		echo '<ul class="errors">';
		foreach ($errors as $error) {
			echo '<li class="error-item">' . $error . '</li>';
		}
		echo '</ul>';
	} else {
		return;
	}

}

?>