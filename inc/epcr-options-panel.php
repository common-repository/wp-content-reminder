<?php
/*
The settings page
*/
if ( ! defined( 'ABSPATH' ) ) {
    exit; // disable direct access
}

class EPCR_options_panel{
	
	public function __construct(){
		add_action('admin_menu',array($this, 'EPCR_menu_item'));
		add_action('admin_enqueue_scripts',array($this, 'EPCR_scripts_styles'));
		add_action('admin_init',array($this, 'EPCR_create_options'));
		add_action('admin_init',array($this, 'EPCR_add_meta_boxes'));	
	}

	
	function EPCR_menu_item()
	{
		global $EPCR_settings_page_hook;

		$EPCR_settings_page_hook = add_submenu_page(
			'EPCR_reminds_page',
			'Remind Settings',                            // The title to be displayed in the browser window for this page.
			'Settings',                                    // The text to be displayed for this menu item
			'administrator',                            // Which type of users can see this menu item
			'EPCR_settings',                            // The unique ID - that is, the slug - for this menu item
			array($this,'EPCR_render_settings_page')                // The name of the function to call when rendering this menu's page
		);	
	}

	function EPCR_scripts_styles($hook)
	{
		global $EPCR_settings_page_hook;
		global $EPCR_license_page_hook;
		
		if ($EPCR_settings_page_hook == $hook || $EPCR_license_page_hook == $hook )
		{
			wp_enqueue_style("options_panel_stylesheet", plugins_url("static/css/options-panel.css", dirname(__FILE__)), false, "1.0", "all");
			wp_enqueue_script("options_panel_script", plugins_url("static/js/options-panel.js", dirname(__FILE__)), false, "1.0");
			wp_localize_script("EPCR-script", "EPCRajaxhandler", array("ajaxurl" => admin_url('admin-ajax.php')));
			wp_enqueue_script('common');
			wp_enqueue_script('wp-lists');
			wp_enqueue_script('postbox');
		}
		else
		{
			return;
		}
	}


	function EPCR_render_settings_page()
	{
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"></div>
			<h2>Reminders Settings</h2>
			<?php settings_errors(); ?>
			<div class="clearfix paddingtop20">
				<div class="first ninecol">
					<form method="post" action="options.php">
						<?php settings_fields('EPCR_settings'); ?>
						<?php do_meta_boxes('EPCR_metaboxes', 'advanced', null); ?>
						<?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false); ?>
						<?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false); ?>
					</form>
				</div>
				<div class="last threecol">
					<div class="side-block">
						Like the plugin? <br/>
						<a href="https://wordpress.org/support/plugin/wp-content-reminder/reviews/#new-post">Leave a
							review</a>.
					</div>
				</div>
			</div>
		</div>
	<?php 
	}

	function EPCR_create_options()
	{

		add_settings_section('form_settings_section', null, null, 'EPCR_settings');
		add_settings_section('integration_settings_section', null, null, 'EPCR_settings');
		add_settings_section('email_settings_section', null, null, 'EPCR_settings');
		add_settings_section('permissions_settings_section', null, null, 'EPCR_settings');
		add_settings_section('other_settings_section', null, null, 'EPCR_settings');

		add_settings_field(
			'active_fields', '',array($this, 'EPCR_render_settings_field'), 'EPCR_settings', 'form_settings_section',
			array(
				'title' => 'Active Fields',
				'desc'  => 'Fields that will appear on the remind form',
				'id'    => 'active_fields',
				'type'  => 'multicheckbox',
				'items' => array('reminder_name' => 'Name', 'reminder_email' => 'Email', 'reminder_date' => 'Date', 'reminder_time' => 'Time','comment' => 'Comment'),
				'group' => 'EPCR_form_settings'
			)
		);

		add_settings_field(
			'required_fields', '', array($this,'EPCR_render_settings_field'), 'EPCR_settings', 'form_settings_section',
			array(
				'title' => 'Required Fields',
				'desc'  => 'Fields that are required',
				'id'    => 'required_fields',
				'type'  => 'multicheckbox',
				'items' => array('reminder_name' => 'Name', 'reminder_email' => 'Email', 'reminder_date' => 'Date', 'reminder_time' => 'Time','comment' => 'Comment'),
				'group' => 'EPCR_form_settings'
			)
		);

		add_settings_field(
			'slidedown_button_text', '', array($this,'EPCR_render_settings_field'), 'EPCR_settings', 'form_settings_section',
			array(
				'title' => 'Slide Down Button Text',
				'desc'  => '',
				'id'    => 'slidedown_button_text',
				'type'  => 'text',
				'group' => 'EPCR_form_settings'
			)
		);

		add_settings_field(
			'submit_button_text', '',array($this, 'EPCR_render_settings_field'), 'EPCR_settings', 'form_settings_section',
			array(
				'title' => 'Submit Button Text',
				'desc'  => '',
				'id'    => 'submit_button_text',
				'type'  => 'text',
				'group' => 'EPCR_form_settings'
			)
		);

		add_settings_field(
			'color_scheme', '', array($this,'EPCR_render_settings_field'), 'EPCR_settings', 'form_settings_section',
			array(
				'title'   => 'Color Scheme',
				'desc'    => 'Select a scheme for the form',
				'id'      => 'color_scheme',
				'type'    => 'select',
				'options' => array("yellow-colorscheme" => "Yellow", "red-colorscheme" => "Red", "blue-colorscheme" => "Blue", "green-colorscheme" => "Green"),
				'group'   => 'EPCR_form_settings'
			)
		);

		add_settings_field(
			'integration_type', '', array($this,'EPCR_render_settings_field'), 'EPCR_settings', 'integration_settings_section',
			array(
				'title'   => 'Add the remind form',
				'desc'    => 'If you choose manual integration you will have to place <b>&lt;?php EPCR_remind_submission_form(); ?&gt;</b> in your theme files manually.',
				'id'      => 'integration_type',
				'type'    => 'select',
				'options' => array("automatically" => "Automatically", "manually" => "Manually"),
				'group'   => 'EPCR_integration_settings'
			)
		);

		add_settings_field(
			'automatic_form_position', '',array($this, 'EPCR_render_settings_field'), 'EPCR_settings', 'integration_settings_section',
			array(
				'title'   => 'Add the form',
				'desc'    => ' Where do you want the form to be placed? This option will only work if you choose automatic integration',
				'id'      => 'automatic_form_position',
				'type'    => 'select',
				'options' => array("above" => "Above post content", "below" => "Below post content"),
				'group'   => 'EPCR_integration_settings'
			)
		);

		add_settings_field(
			'display_on', '', array($this,'EPCR_render_settings_field'), 'EPCR_settings', 'integration_settings_section',
			array(
				'title'   => 'Display form on',
				'desc'    => ' Select the section of your website where you want this form to appear',
				'id'      => 'display_on',
				'type'    => 'select',
				'options' => array("everywhere" => "The whole site", "single_post" => "Posts", 'single_page' => 'Pages', 'posts_pages' => 'Posts & Pages'),
				'group'   => 'EPCR_integration_settings'
			)
		);
		
		add_settings_field(
			'sender_name', '',array($this, 'EPCR_render_settings_field'), 'EPCR_settings', 'email_settings_section',
			array(
				'title' => 'Sender\'s Name',
				'desc'  => '',
				'id'    => 'sender_name',
				'type'  => 'text',
				'group' => 'EPCR_email_settings'
			)
		);

		add_settings_field(
			'sender_address', '',array($this, 'EPCR_render_settings_field'), 'EPCR_settings', 'email_settings_section',
			array(
				'title' => 'Sender\'s Email Address',
				'desc'  => '',
				'id'    => 'sender_address',
				'type'  => 'text',
				'group' => 'EPCR_email_settings'
			)
		);

		add_settings_field(
			'remind_email_subject', '', array($this,'EPCR_render_settings_field'), 'EPCR_settings', 'email_settings_section',
			array(
				'title' => 'Remind Email Subject',
				'desc'  => 'Subject of the email you want sent to reminded request user. <b>{title}</b> will be replaced by Post title',
				'id'    => 'remind_email_subject',
				'type'  => 'text',
				'group' => 'EPCR_email_settings'
			)
		);

		add_settings_field(
			'remind_email_content', '', array($this,'EPCR_render_settings_field'), 'EPCR_settings', 'email_settings_section',
			array(
				'title' => 'Remind Email Content',
				'desc'  => 'This will be sent to the reminded request user. <br/><b>{uname}</b> will be replaced by remind user name<br/><b>{website}</b> will be replaced with a link and name to website<br/><b>{post}</b> will be replaced with a link and name to post<br/><b>{ucomment}</b> will be replaced with a user comment',
				'id'    => 'remind_email_content',
				'type'  => 'textarea',
				'group' => 'EPCR_email_settings'
			)
		);
		
		add_settings_field(
			'minimum_role_view', '',array($this,'EPCR_render_settings_field'), 'EPCR_settings', 'permissions_settings_section',
			array(
				'title'   => 'Minimum access level required to view the reminders',
				'desc'    => 'What\'s the minimum role that a logged in user needs to have in order to view reminders',
				'id'      => 'minimum_role_view',
				'type'    => 'select',
				'options' => array("install_plugins" => "Administrator", "moderate_comments" => "Editor", "edit_published_posts" => "Author", "edit_posts" => "Contributor", "read" => "Subscriber"),
				'group'   => 'EPCR_permissions_settings'
			)
		);

		add_settings_field(
			'minimum_role_change', '', array($this,'EPCR_render_settings_field'), 'EPCR_settings', 'permissions_settings_section',
			array(
				'title'   => 'Minimum access level required to change status of/delete reminders',
				'desc'    => 'What\'s the minimum role that a logged in user needs to have in order to manipulate reminders',
				'id'      => 'minimum_role_change',
				'type'    => 'select',
				'options' => array("install_plugins" => "Administrator", "moderate_comments" => "Editor", "edit_published_posts" => "Author", "edit_posts" => "Contributor", "read" => "Subscriber"),
				'group'   => 'EPCR_permissions_settings'
			)
		);

		add_settings_field(
			'login_required', '', array($this,'EPCR_render_settings_field'), 'EPCR_settings', 'permissions_settings_section',
			array(
				'title' => 'Users must be logged in to remind content',
				'desc'  => '',
				'id'    => 'login_required',
				'type'  => 'checkbox',
				'group' => 'EPCR_permissions_settings'
			)
		);

		add_settings_field(
			'use_akismet', '', array($this,'EPCR_render_settings_field'), 'EPCR_settings', 'permissions_settings_section',
			array(
				'title' => 'Use Akismet to filter reminders',
				'desc'  => 'Akismet plugin is required for this feature.',
				'id'    => 'use_akismet',
				'type'  => 'checkbox',
				'group' => 'EPCR_permissions_settings'
			)
		);

		add_settings_field(
			'disable_metabox', '',array($this, 'EPCR_render_settings_field'), 'EPCR_settings', 'other_settings_section',
			array(
				'title' => 'Disable metabox?',
				'desc'  => 'Check if you don\' want to display the metabox',
				'id'    => 'disable_metabox',
				'type'  => 'checkbox',
				'group' => 'EPCR_other_settings'
			)
		);

		add_settings_field(
			'disable_db_saving', '',array($this, 'EPCR_render_settings_field'), 'EPCR_settings', 'other_settings_section',
			array(
				'title' => 'Don\'t save reminders in database',
				'desc'  => 'Check if you don\' want to save reminders in database',
				'id'    => 'disable_db_saving',
				'type'  => 'checkbox',
				'group' => 'EPCR_other_settings'
			)
		);

		// Finally, we register the fields with WordPress
		register_setting('EPCR_settings', 'EPCR_form_settings', 'EPCR_settings_validation');
		register_setting('EPCR_settings', 'EPCR_integration_settings', 'EPCR_settings_validation');
		register_setting('EPCR_settings', 'EPCR_email_settings', 'EPCR_settings_validation');
		register_setting('EPCR_settings', 'EPCR_permissions_settings', 'EPCR_settings_validation');
		register_setting('EPCR_settings', 'EPCR_other_settings', 'EPCR_settings_validation');

	} // end sandbox_initialize_theme_options 

	function EPCR_settings_validation($input)
	{
		return $input;
	}

	function EPCR_add_meta_boxes()
	{
		add_meta_box("EPCR_form_settings_metabox", 'Form Settings', array($this,"EPCR_metaboxes_callback"), "EPCR_metaboxes", 'advanced', 'default', array('settings_section' => 'form_settings_section'));
		add_meta_box("EPCR_integration_settings_metabox", 'Integration Settings',array($this, "EPCR_metaboxes_callback"), "EPCR_metaboxes", 'advanced', 'default', array('settings_section' => 'integration_settings_section'));
		add_meta_box("EPCR_email_settings_metabox", 'Email Settings', array($this,"EPCR_metaboxes_callback"), "EPCR_metaboxes", 'advanced', 'default', array('settings_section' => 'email_settings_section'));
		add_meta_box("EPCR_permissions_settings_metabox", 'Security Settings',array($this, "EPCR_metaboxes_callback"), "EPCR_metaboxes", 'advanced', 'default', array('settings_section' => 'permissions_settings_section'));
		add_meta_box("EPCR_other_settings_metabox", 'Other Settings',array($this, "EPCR_metaboxes_callback"), "EPCR_metaboxes", 'advanced', 'default', array('settings_section' => 'other_settings_section'));
	}

	function EPCR_metaboxes_callback($post, $args)
	{
		do_settings_fields("EPCR_settings", $args['args']['settings_section']);
		submit_button('Save Changes', 'secondary');
	}

	function EPCR_render_settings_field($args)
	{
		$option_value = get_option($args['group']);
		?>
		<div class="row clearfix">
			<div class="col colone"><?php echo $args['title']; ?></div>
			<div class="col coltwo">
				<?php if ($args['type'] == 'text'): ?>
					<input type="text" id="<?php echo $args['id'] ?>"
						   name="<?php echo $args['group'] . '[' . $args['id'] . ']'; ?>"
						   value="<?php echo esc_attr($option_value[ $args['id'] ]); ?>">
				<?php elseif ($args['type'] == 'select'): ?>
					<select name="<?php echo $args['group'] . '[' . $args['id'] . ']'; ?>" id="<?php echo $args['id']; ?>">
						<?php foreach ($args['options'] as $key => $option) { ?>
							<option <?php selected($option_value[ $args['id'] ], $key);
							echo 'value="' . $key . '"'; ?>><?php echo $option; ?></option><?php } ?>
					</select>
				<?php elseif ($args['type'] == 'checkbox'): ?>
					<input type="hidden" name="<?php echo $args['group'] . '[' . $args['id'] . ']'; ?>" value="0"/>
					<input type="checkbox" name="<?php echo $args['group'] . '[' . $args['id'] . ']'; ?>"
						   id="<?php echo $args['id']; ?>" value="1" <?php checked($option_value[ $args['id'] ]); ?> />
				<?php elseif ($args['type'] == 'textarea'): ?>
					<textarea name="<?php echo $args['group'] . '[' . $args['id'] . ']'; ?>"
							  type="<?php echo $args['type']; ?>" cols=""
							  rows=""><?php if ($option_value[ $args['id'] ] != "") {
							echo stripslashes(esc_textarea($option_value[ $args['id'] ]));
						} ?></textarea>
				<?php elseif ($args['type'] == 'multicheckbox'):
					foreach ($args['items'] as $key => $checkboxitem):
						?>
						<input type="hidden" name="<?php echo $args['group'] . '[' . $args['id'] . '][' . $key . ']'; ?>"
							   value="0"/>
						<label
							for="<?php echo $args['group'] . '[' . $args['id'] . '][' . $key . ']'; ?>"><?php echo $checkboxitem; ?></label>
						<input type="checkbox" name="<?php echo $args['group'] . '[' . $args['id'] . '][' . $key . ']'; ?>"
							   id="<?php echo $args['group'] . '[' . $args['id'] . '][' . $key . ']'; ?>" value="1"
							   <?php 
							   $disble = array("reminder_email", "reminder_date", "reminder_time");
							   if (in_array($key, $disble))
							   {
							   ?>
								   checked="checked" disabled="disabled" <?php 
							   } 
							   else 
							   { 
								   if (!empty($option_value[ $args['id'] ][ $key ]))	
								   {
										checked($option_value[ $args['id'] ][ $key ]);
								   }
							   } ?> />
					<?php endforeach; ?>
				<?php elseif ($args['type'] == 'multitext'):
					foreach ($args['items'] as $key => $textitem):
						?>
						<label
							for="<?php echo $args['group'] . '[' . $args['id'] . '][' . $key . ']'; ?>"><?php echo $textitem; ?></label>
						<br/>
						<input type="text" id="<?php echo $args['group'] . '[' . $args['id'] . '][' . $key . ']'; ?>"
							   name="<?php echo $args['group'] . '[' . $args['id'] . '][' . $key . ']'; ?>"
							   value="<?php echo esc_attr($option_value[ $args['id'] ][ $key ]); ?>"><br/>
					<?php endforeach; endif; ?>
			</div>
			<div class="col colthree">
				<small><?php echo $args['desc'] ?></small>
			</div>
		</div>
		<?php
	}
}
$EPCR_options_panel=new EPCR_options_panel;
?>
