<?php
/*
Plugin Name: Easy Post Content Reminder
Plugin URI: https://wtplugins.com/easy-post-content-remider
Description: Inserts a secure form on specified pages so that your readers can remind themselves about what they were looking for on your site incase they leave it in the middle. Turn them into returning visitors or even subscribers by putting a "Remind Me" button on any content.
Version: 1.0.1
Author: WT Plugins
Author URI: https://wtplugins.com/
License: GPL2
*/

/**********************************************
 *
 * Creating the contentremind table on installation
 *
 ***********************************************/

defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );
define('EPCR_TABLE_VERSION', '1.0');
define('EPCR_PLUGIN_DIR_PATH',plugin_dir_path(__FILE__));
define('EPCR_PLUGIN_URL',plugins_url());

class EPCR {
	
	public function __construct(){
		register_activation_hook(__FILE__, array($this,'EPCR_install')); 
		add_filter('manage_posts_custom_column', array($this,'EPCR_add_admin_column_contents'), 10, 2);
		add_action('wp_ajax_EPCR_add_remind', array($this, 'EPCR_add_remind'));
		add_action('wp_ajax_nopriv_EPCR_add_remind',array($this, 'EPCR_add_remind'));
		add_action('delete_post', array($this,'EPCR_on_post_delete'));
		add_filter('get_the_excerpt', array($this, 'EPCR_neutralize_excerpt'), 5);
		add_action('wp_enqueue_scripts', array($this, 'EPCR_enqueue_resources'));
		add_filter('the_content', array($this, 'EPCR_add_remind_button_filter'));
		add_filter( 'query_vars', array($this, 'EPCR_add_query_vars'));
		add_action('template_redirect', array($this,'EPCR_my_cron'));
		register_deactivation_hook(__FILE__,array($this,'EPCR_rollback'));
	}
	
	/**********************************************
	 *
	 * Create new table
	 *
	 ***********************************************/
	 
	function EPCR_install()
	{ 
		global $wpdb;

		$table_name = $this->EPCR_table_name();
		$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
		$table_up_to_date = get_option("EPCR_db_version") == EPCR_TABLE_VERSION;

		if ($table_exists && $table_up_to_date) {
			return;
		}

		$this->EPCR_create_table();
		
		update_option('EPCR_db_version', EPCR_TABLE_VERSION);

		if ($table_exists) {
			return;
		}

		$this->EPCR_initialize_settings();
		
	}
	
	/**********************************************
	 *
	 * Get tabvle name
	 *
	 ***********************************************/
	 
	function EPCR_table_name()
	{
		global $wpdb;
		return $wpdb->prefix . "epcr_reminders";
	}
	
	/**********************************************
	 *
	 * Create table
	 *
	 ***********************************************/
	 
	function EPCR_create_table()
	 {    
		global $wpdb;
		$table_name = $this->EPCR_table_name();

		$charset_collate = $wpdb->get_charset_collate();
		$sql = "
			CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			remind_by bigint(20) NOT NULL,
			created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			comment text DEFAULT '' NULL,
			reminder_name VARCHAR(55) DEFAULT '' NULL,
			reminder_email VARCHAR(55) DEFAULT '' NULL,
			remind_date date DEFAULT NULL,
			remind_time time DEFAULT NULL,
			post_id mediumint(9) NOT NULL,
			UNIQUE KEY id (id) ) $charset_collate;
		";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
	
	/**********************************************
	 *
	 * Initialize basic setting
	 *
	 ***********************************************/
	 
	function EPCR_initialize_settings()
	{
		$EPCR_form_settings = array(
			'active_fields'         => array('reminder_email' => 1, 'reminder_date' => 1, 'reminder_time' => 1),
			'required_fields'       => array('reminder_email' => 1, 'reminder_date' => 1, 'reminder_time' => 1),
			'remind_reasons'        => "Copyright Infringement\nSpam\nInvalid Contents\nBroken Links",
			'slidedown_button_text' => 'Remind Content',
			'submit_button_text'    => 'Submit Remind',
			'color_scheme'          => 'yellow'
		);
		$EPCR_email_settings = array(
			'sender_name'          => get_bloginfo('name'),
			'sender_address'       => get_bloginfo('admin_email'),
			'remind_email_subject' => 'New Reminder - {title}',
			'remind_email_content' => 'Hi {uname},<br><br>Your reminder request from {website} for the {post} has been initiated. Please add this event to your calendar to get a reminder on the desired time.<br><br>Comment:<br>{ucomment}<br><br>Thank you'
		);
		$EPCR_integration_settings = array(
			'integration_type'        => 'automatically',
			'automatic_form_position' => 'above',
			'display_on'              => 'posts_pages'
		);
		$EPCR_permissions_settings = array(
			'minimum_role_view'   => 'install_plugins',
			'minimum_role_change' => 'install_plugins',
			'login_required'      => 0,
			'use_akismet'         => 1
		);
		$EPCR_other_settings = array(
			'disable_metabox'   => 0,
			'disable_db_saving' => 0
		);
		update_option('EPCR_form_settings', $EPCR_form_settings);
		update_option('EPCR_integration_settings', $EPCR_integration_settings);
		update_option('EPCR_email_settings', $EPCR_email_settings);
		update_option('EPCR_permissions_settings', $EPCR_permissions_settings);
		update_option('EPCR_other_settings', $EPCR_other_settings);
	}

	/**********************************************
	 *
	 * Rollback changes
	 *
	 ***********************************************/
	 
	function EPCR_rollback()
	{ 
		delete_option('EPCR_db_version');
		delete_option('EPCR_form_settings');
		delete_option('EPCR_integration_settings');
		delete_option('EPCR_permissions_settings');
		delete_option('EPCR_other_settings');
		global $wpdb;
		$table_name = $wpdb->prefix . "epcr_reminders";
		return $wpdb->query("DROP TABLE $table_name");
	}

	/**********************************************
	 *
	 * Enqueuing scripts and styles
	 *
	 ***********************************************/

	function EPCR_enqueue_resources()
	{
		wp_enqueue_style('EPCR-style', plugins_url('static/css/styles.css', __FILE__));
		wp_enqueue_script('EPCR-script', plugins_url('static/js/scripts.js', __FILE__), array('jquery'));
		
		wp_enqueue_script('EPCR-datetime-script', plugins_url('static/js/jquery.datetimepicker.min.js', __FILE__), array('jquery'));
		wp_enqueue_style('EPCR-datetime-style', plugins_url('static/css/jquery.datetimepicker.min.css', __FILE__));
		
		wp_localize_script('EPCR-script', 'EPCRajaxhandler', array('ajaxurl' => admin_url('admin-ajax.php')));
	}


	/**********************************************
	 *
	 * Automatically insert the remind form in posts
	 *
	 ***********************************************/

	public function EPCR_add_remind_button_filter($content)
	{
		$integration_options = get_option('EPCR_integration_settings');
		if (($integration_options && $integration_options['integration_type'] == 'manually') ||
			($integration_options['display_on'] == 'single_post' && !is_single()) ||
			($integration_options['display_on'] == 'single_page' && !is_page()) ||
			($integration_options['display_on'] == 'posts_pages' && !is_singular())
		)
			return $content;

		ob_start();
		include(plugin_dir_path(__FILE__) . 'inc/epcr-remind-form.php');
		$form_html = ob_get_contents();
		ob_end_clean();

		if ($integration_options && $integration_options['automatic_form_position'] == 'below')
			return $content . $form_html;
		return $form_html . $content;
	}
	
	/**********************************************
	 *
	 * Remind form view 
	 *
	 ***********************************************/

	function EPCR_remind_submission_form()
	{
		include(plugin_dir_path(__FILE__) . 'inc/epcr-remind-form.php');
	}
	
	/**********************************************
	 *
	 * Neutralize excerpt
	 *
	 ***********************************************/
	 
	function EPCR_neutralize_excerpt($content)
	{
		remove_filter('the_content', array($this, 'EPCR_add_remind_button_filter'));
		return $content;
	}


	/**********************************************
	 *
	 * Database functions
	 *
	 ***********************************************/

	function EPCR_insert_data($args)
	{
		$other_options = get_option('EPCR_other_settings');
		if ($other_options['disable_db_saving'])
			return true;
		global $wpdb;
		$table = $wpdb->prefix . "epcr_reminders";
		$result = $wpdb->insert($table, $args);
		if ($result)
			return $wpdb->insert_id;
		return false;
	}
	
	/**********************************************
	 *
	 * Get reminds
	 *
	 ***********************************************/
	 
	function EPCR_get_post_reminds($post_id)
	{ 
		global $wpdb;
		$table = $wpdb->prefix . "epcr_reminders";
		$query = "SELECT * FROM $table WHERE post_id = $post_id ORDER BY created DESC";
		return $wpdb->get_results($query, ARRAY_A);
	}
	
	/**********************************************
	 *
	 * Delete remind 
	 *
	 ***********************************************/
	 
	function EPCR_delete_post_reminds($post_id)
	{
		global $wpdb;
		$table = $wpdb->prefix . "epcr_reminders";
		$query = $wpdb->prepare("DELETE FROM $table WHERE post_id = %d", $post_id);
		return $wpdb->query($query);
	}

	/**********************************************
	 *
	 * Cleanup on post deletion
	 *
	 ***********************************************/

	function EPCR_on_post_delete($post_id)
	{
		$this->EPCR_delete_post_reminds($post_id);
	}


	/**********************************************
	 *
	 * Mailing function
	 *
	 ***********************************************/

	function EPCR_mail($remind)
	{ 
		$post_id = $remind['post_id'];

		$email_options = get_option('EPCR_email_settings');

		$remind_emails_sent = true;
		$headers = array();

		if ($email_options['sender_name'] && $email_options['sender_address'])
			$headers[] = 'From: ' . $email_options['sender_name'] . ' <' . $email_options['sender_address'] . '>';
		
		$headers[] = 'Content-type:text/html;charset=UTF-8';
			
		$post = get_post($post_id);
		
		$replace_post = '<a href="'.get_permalink( $post_id ).'">'.get_the_title( $post_id ).'</a>';
		$replace_website = '<a href="' . get_site_url() . '">'. get_bloginfo('name') . '</a>';

		$email_options['remind_email_subject'] = str_replace('{title}', $post->post_title , $email_options['remind_email_subject']);
		
		$email_options['remind_email_content'] = str_replace('{uname}', $remind['reminder_name'], $email_options['remind_email_content']);
		$email_options['remind_email_content'] = str_replace('{website}', $replace_website , $email_options['remind_email_content']);
		$email_options['remind_email_content'] = str_replace('{post}', $replace_post, $email_options['remind_email_content']); 
		$email_options['remind_email_content'] = str_replace('{ucomment}', $remind['comment'], $email_options['remind_email_content']);

		$remind_emails_sent = wp_mail($remind['reminder_email'], $email_options['remind_email_subject'], $email_options['remind_email_content'], $headers, $attachments);

		return ($remind_emails_sent);
	}

	/**********************************************
	 *
	 * Check for errors, insert into DB and send emails
	 *
	 ***********************************************/

	function EPCR_add_remind()
	 {
		$message['success'] = 0;
		$permissions = get_option('EPCR_permissions_settings');
		if ($permissions['login_required'] && !is_user_logged_in()) {
			$message['message'] = 'To submit a remind you need to <a href="<?php echo wp_login_url(); ?>" title="Login">login</a> first';
			die(json_encode($message));
		}

		$form_options = get_option('EPCR_form_settings');
		$active_fields = $form_options['active_fields'];
		$required_fields = $form_options['required_fields'];
		
		foreach ($required_fields as $key => $field) {
			if ($field && $active_fields[ $key ] && !$_POST[ $key ]) {
				$message['message'] = 'You missed a required field';
				die(json_encode($message));
			}
		}

		if ($active_fields['reminder_email'] && $_POST['reminder_email'] && !is_email($_POST['reminder_email'])) {
			$message['message'] = 'Email address invalid';
			die(json_encode($message));
		}

		$comment = $_POST['comment'];
		$reminder_name = (isset($_POST['reminder_name'])) ? $_POST['reminder_name'] : '';
		$reminder_email = (isset($_POST['reminder_email'])) ? $_POST['reminder_email'] : '';
		
		$remind_date = $_POST['reminder_date'];
		$remind_time = $_POST['reminder_time'];
		
		$current_user = wp_get_current_user();
		
		$new_remind = array(
			'remind_by'		 =>	$current_user->ID,
			'created'        => current_time('mysql'),
			'comment'        => sanitize_text_field($comment),
			'reminder_name'  => sanitize_text_field($reminder_name),
			'reminder_email' => sanitize_email($reminder_email),
			'remind_date'	 => preg_replace("([^0-9/])", "", $_POST['reminder_date']),
			'remind_time'	 =>	sanitize_text_field( $_POST['reminder_time'] ) ,
			'post_id'        => intval($_POST['id']),
		);
		
		if ($this->EPCR_is_spam($new_remind)) {
			$message['message'] = 'Your submission has been marked as spam by our filters';
			die(json_encode($message));
		}
		
		$insert_result = $this->EPCR_insert_data($new_remind);
		if (!$insert_result) {
			$message['message'] = 'An unexpected error occured. Please try again later';
			die(json_encode($message));
		}
		else
		{
			$new_remind['id'] = $insert_result;
		}

		$this->EPCR_mail($new_remind);
		$message['success'] = 1;
		$message['message'] = 'Your reminder has been set! Please check your email.';
		die(json_encode($message));
		
	}
	
	/**********************************************
	 *
	 * Admin column content
	 *
	 ***********************************************/
	 
	function EPCR_add_admin_column_contents($header, $something)
	{
		if ($header == 'EPCR_post_reminds') {
			global $post;
			$EPCR_post_reminds = $this->EPCR_get_post_reminds($post->ID);
			echo '<a href="' . get_edit_post_link($post->ID) . '#EPCR-reminds">' . count($EPCR_post_reminds) . '</a>';
		}
	}

	/**********************************************
	 *
	 * Prepare the remind for akismet and run tests
	 *
	 ***********************************************/

	function EPCR_is_spam($remind)
	{
		$permission_options = get_option('EPCR_permissions_settings');
		if (!$permission_options['use_akismet'] || !function_exists('akismet_init'))
			return false;
		$content['comment_author'] = $remind['reminder_name'];
		$content['comment_author_email'] = $remind['reminder_email'];
		$content['comment_content'] = $remind['comment'];
		if ($this->EPCR_akismet_failed($content))
			return true;
		return false;
	}

	/**********************************************
	 *
	 * Pass the remind through Akismet filters to
	 * make sure it isn't spam
	 *
	 ***********************************************/

	function EPCR_akismet_failed($content)
	{
		$isSpam = FALSE;
		$content = (array)$content;
		if (function_exists('akismet_init')) {
			$wpcom_api_key = get_option('wordpress_api_key');
			if (!empty($wpcom_api_key)) {
				global $akismet_api_host, $akismet_api_port;
				// set remaining required values for akismet api
				$content['user_ip'] = preg_replace('/[^0-9., ]/', '', $_SERVER['REMOTE_ADDR']);
				$content['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
				$content['referrer'] = $_SERVER['HTTP_REFERER'];
				$content['blog'] = get_option('home');

				if (empty($content['referrer'])) {
					$content['referrer'] = get_permalink();
				}

				$queryString = '';

				foreach ($content as $key => $data) {
					if (!empty($data)) {
						$queryString .= $key . '=' . urlencode(stripslashes($data)) . '&';
					}
				}
				$response = akismet_http_post($queryString, $akismet_api_host, '/1.1/comment-check', $akismet_api_port);
				if ($response[1] == 'true') {
					update_option('akismet_spam_count', get_option('akismet_spam_count') + 1);
					$isSpam = TRUE;
				}
			}
		}
		return $isSpam;
	}

	/**********************************************
	 *
	 * Add the 'easy-post-content-remider' query variable
	 *
	 ***********************************************/
	 
	function EPCR_add_query_vars($vars){
		$vars[] = "easy-post-content-remider";
		return $vars;
	}
	
	/**********************************************
	 *
	 * Cron function send remider email on date before 15 min
	 *
	 ***********************************************/
	 
	function EPCR_my_cron($template) { 
		global $wp_query;

		if(!isset( $wp_query->query['easy-post-content-remider'] )) 
			return $template;
		
		if($wp_query->query['easy-post-content-remider'] == 'EPCR_send_remind'){ 
			
			$current_date = current_time( 'Y-m-d', $gmt = 0 );
			$current_time = current_time( 'H:i:s', $gmt = 0 );
			
			$add_time = strtotime($current_time);
			$endTime = date("H:i:s", strtotime('+15 minutes', $add_time));
		
			global $wpdb;
			
			$table = $wpdb->prefix . "epcr_reminders";
			$query = "SELECT * FROM $table WHERE remind_date = '$current_date' AND (remind_time BETWEEN '$current_time' AND '$endTime')";
			
			$data = $wpdb->get_results($query);

			if ($data)
			{
				foreach($data as $remind)
				{
					$new_remind = array(
						'id'			 =>	$remind->id,
						'comment'        => $remind->comment,
						'reminder_name'  => $remind->reminder_name,
						'reminder_email' => $remind->reminder_email,
						'remind_date'	 => $remind->remind_date,
						'remind_time'	 =>	$remind->remind_time ,
						'post_id'        => $remind->post_id,
						'cron'  	     => 1,
					);
					
					$this->EPCR_mail($new_remind);
				}
			}
			
			exit;
		}
		return $template;
	}
}
$EPCR=new EPCR;

include(plugin_dir_path(__FILE__) . 'inc/epcr-reminds-list.php');

include(plugin_dir_path(__FILE__) . 'inc/epcr-options-panel.php');
