<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // disable direct access
}

if (!class_exists('EPCR_Table')) {
	require_once('epcr-table.php');
}

class EPCR_reminds_list{
	
	public function __construct(){
		add_action('admin_menu', array($this, 'EPCR_add_menu_items'));
		add_action('admin_notices', array($this, 'EPCR_db_change_admin_notice'));
	}

	function EPCR_add_menu_items()
	{
		$reminders_icon = EPCR_PLUGIN_URL. '/wp-content-reminder/static/img/reminders.png'; 
		$permission_options = get_option('EPCR_permissions_settings');
		$menu_page_permission = (isset($permission_options['minimum_role_view'])) ? $permission_options['minimum_role_view'] : 'activate_plugins';
		add_menu_page('Reminds', 'Reminders', $menu_page_permission, 'EPCR_reminds_page', array($this,'EPCR_render_list_page'), $reminders_icon);
	}

	function EPCR_db_change_admin_notice()
	{
		$message = '';
		if (!isset($_GET['remind']) || !isset($_GET['action']))
			return;
		if ($_GET['action'] === 'delete')
			$message = count($_GET['remind']) . " record(s) deleted from database";
		?>
		<div class="updated">
			<p><?php echo $message; ?></p>
		</div>
		<?php
	}

	function EPCR_render_list_page()
	{
		$reportsTable = new EPCR_Table();
		$reportsTable->EPCR_prepare_items();
		?>
		<div class="wrap">
			<div id="icon-users" class="icon32"><br/></div>
			<h2>Reminders</h2>
			<form id="reports-filter" method="get">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
				<?php $reportsTable->EPCR_display() ?>
			</form>
		</div>
		<?php
	}
}
$EPCR_reminds_list=new EPCR_reminds_list;
