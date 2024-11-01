<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // disable direct access
}

if (!class_exists('EPCR_List_Table')) {
	require_once('epcr-list-table.php');
}

class EPCR_Table extends EPCR_List_Table
{
	private $_args;
	
	function __construct()
	{
		global $page;

		global $wpdb;
		$this->table = $wpdb->prefix . "epcr_reminders";

		//Set parent defaults
 		$args = array(
			'plural'   => 'reminds',
			'singular' => 'remind',
			'ajax'     => false,
		);
		
		$this->_args = $args;
	}

	function EPCR_column_default($item, $column_name)
	{
		return $item[ $column_name ];
	}

	function EPCR_column_reminder_email($item)
	{
		return '<a href="mailto:' . $item['reminder_email'] . '">' . $item['reminder_email'] . '</a>';
	}

	function EPCR_column_post_id($item)
	{
		$post = get_post($item['post_id']);

		if (is_a($post, 'WP_Post'))
			return '<a href="' . get_edit_post_link($post->ID) . '#EPCR-reminds">' . $post->post_title . '</a>';

		return 'Post Not Found';
	}

	function EPCR_column_cb($item)
	{
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/
			$this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
			/*$2%s*/
			$item['id']                //The value of the checkbox should be the record's id
		);
	}

	function EPCR_get_bulk_actions()
	{
		$permission_options = get_option('EPCR_permissions_settings');
		$bulk_action_permission = (isset($permission_options['minimum_role_change'])) ? $permission_options['minimum_role_change'] : 'activate_plugins';
		if (!current_user_can($bulk_action_permission))
			return array();

		$actions = array(
			'delete'        => 'Delete'
		);
		return $actions;
	}

	function EPCR_prepare_items()
	{
		global $wpdb; //This is used only if making any database queries
		$per_page = 50;

		$columns = $this->EPCR_get_columns();
		$hidden = array();
		$sortable = $this->EPCR_get_sortable_columns();

		$this->_column_headers = array($columns, $hidden, $sortable);

		$this->EPCR_process_bulk_action();

		$orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'created'; //If no sort, default to title
		$order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'desc'; //If no order, default to asc
		$query = "SELECT * FROM $this->table ORDER BY $orderby $order";
		$data = $wpdb->get_results($query, ARRAY_A);

		$current_page = $this->EPCR_get_pagenum();

		$total_items = count($data);

		$data = array_slice($data, (($current_page - 1) * $per_page), $per_page);

		$this->items = $data;

		$this->EPCR_set_pagination_args(array(
			'total_items' => $total_items,                  //WE have to calculate the total number of items
			'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
			'total_pages' => ceil($total_items / $per_page)   //WE have to calculate the total number of pages
		));
	}

	function EPCR_get_columns()
	{
		$columns = array(
			'cb'       => '<input type="checkbox" />', //Render a checkbox instead of text
			'post_id'     => 'Post',
			'reminder_name'  => 'Name',
			'reminder_email'  => 'Email',
			'comment'  => 'User Comment',
			'created'     => 'Time',
			
		);
		return $columns;
	}

	function EPCR_get_sortable_columns()
	{
		$sortable_columns = array(
			'created' => array('created', true),     //true means it's already sorted
		);
		return $sortable_columns;
	}

	function EPCR_process_bulk_action()
	{
		global $wpdb;
		//Detect when a bulk action is being triggered...
		if ('delete' === $this->EPCR_current_action()) {
			$id_string = join(',', $_GET['remind']);
			$query = "DELETE FROM $this->table WHERE id IN ($id_string)";
			$wpdb->query($query);
		}
	}
}
