<?php
namespace WordpressBpi;

class SyndicationTable extends \WP_List_Table
{
	protected $_column_headers;

	public function __construct()
	{

		parent::__construct(array(
			'singular' => 'wp_list_text_link_control', //Singular label
			'plural'   => 'wp_list_test_links_control', //plural label, also this well be one of the table css class
			'ajax'     => false //We won't support Ajax for this table
		));
		$sortable = array();
		foreach ($this->get_sortable_columns() as $id => $data) {
			if (empty($data)) {
				continue;
			}

			$data = (array) $data;
			if ( ! isset($data[1])) {
				$data[1] = false;
			}

			$sortable[$id] = $data;
		}
		$this->_column_headers = array($this->get_columns(), array(), $sortable);
	}

	public function extra_tablenav($which)
	{
		if ('top' == $which) {
			$filterDateStart = @$_REQUEST['stat_date_start'];
			$filterDateEnd   = @$_REQUEST['stat_date_end'];
			echo '<h2>BPI Library</h2>';
			/*echo '<div class="statistics" style="text-align:left;padding-bottom: 10px">
				<table>
					<tr><th colspan="2"><div class="h3">Статистика по публикациям</div></th></tr>
					<tr>
						<td colspan="2">
							<div class="alignleft actions">
								<form action="http://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . '"  method="get">
								<input type="hidden" name="paged" value="' . max(1, @$_GET['paged']) . '"/>
								<input type="hidden" name="page" value="printweek_control"/>
								Даты от: <input type="text" name="stat_date_start" class="datepicker-init" value="' . $filterDateStart . '"/>
								до: <input type="text" name="stat_date_end" class="datepicker-init" value="' . $filterDateEnd . '"/>
								<input type="submit" name="" id="post-query-submit" class="button-secondary" value="Фильтр">
								</form>
							</div>
						</td>
					</tr>
				</table>
			</div>';*/
		} elseif ($which == 'bottom') {
			//			echo '<a href="' . admin_url('admin.php?page=printweek_control&section=xls') . '" id="generate-xsl" style="float: left" class="button-secondary">Сгенерировать XSL</a>';
		}
		return;
		?>
		<div class="alignleft actions">
			<?php
			$dropdown_options = array(
				'selected'        => $cat_id,
				'name'            => 'cat_id',
				'taxonomy'        => 'link_category',
				'show_option_all' => __('View all categories'),
				'hide_empty'      => true,
				'hierarchical'    => 1,
				'show_count'      => 0,
				'orderby'         => 'name',
			);
			wp_dropdown_categories($dropdown_options);
			submit_button(__('Filter'), 'secondary', false, false, array('id' => 'post-query-submit'));
			?>
		</div>
	<?php
	}

	public function get_columns()
	{
		return array(
			'title'     => 'Title',
			'teaser'    => 'Teaser',
			'author'    => 'Author',
			'agency_id' => 'Agency',
			'category'  => 'Category',
			'audience'  => 'Audience',
			'_actions'  => 'Actions'
		);
	}

	public function get_sortable_columns()
	{
		return array();
		$columns = $this->get_columns();
		array_pop($columns);
		foreach ($columns as $key => $column) {
			$columns[$key] = $key;
		}
		//		var_dump();die;
		return $columns;
	}

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 */
	public function prepare_items()
	{
		global $_wp_column_headers;
		global $wpdb;
		$screen = get_current_screen();

		/*		$filterDateStart = @$_REQUEST['stat_date_start'];
				$filterDateEnd   = @$_REQUEST['stat_date_end'];
				$dateWhere       = '';
				if ($filterDateStart) {
					$dateWhere .= " AND  DATE(post_date) >= '" . date('Y-m-d', strtotime($filterDateStart)) . "'";
				}
				if ($filterDateEnd) {
					$dateWhere .= " AND  DATE(post_date) < '" . date('Y-m-d', strtotime($filterDateEnd)) . "'";
				}*/

		//Parameters that are going to be used to order the result
		$orderby = ! empty($_GET["orderby"]) ? sanitize_text_field($_GET["orderby"]) : 'ID';
		$order   = ! empty($_GET["order"]) ? sanitize_text_field($_GET["order"]) : 'ASC';
		/* -- Pagination parameters -- */
		//Number of elements in your table?
		//		$where      = " WHERE post_type IN ('analytics','blogs','magazine','post','green_bag','news_from_the_field') AND post_status = 'publish' $dateWhere ORDER BY ";
		//		$query      = 'SELECT COUNT(*) FROM ' . $wpdb->posts . $where . $orderby . ' ' . $order;
		$totalitems = 100;/*$wpdb->get_var($query)*/;

		//How many to display per page?
		$perpage = Bpi::init()->getAmountPerPage();
		//Which page is this?
		$paged = max(1, intval(@$_GET["paged"]));

		//Page Number
		if (empty($paged) || ! is_numeric($paged) || $paged <= 0) {
			$paged = 1;
		}
		//How many pages do we have in total?
		$totalpages = ceil($totalitems / $perpage);
		//adjust the query to take pagination into account
		$limit = '';
		if ( ! empty($paged) && ! empty($perpage)) {
			$offset = ($paged - 1) * $perpage;
			$limit  = ' LIMIT ' . (int) $offset . ',' . (int) $perpage;
		}

		/* -- Register the pagination -- */
		$this->set_pagination_args(array(
			"total_items" => $totalitems,
			"total_pages" => $totalpages,
			"per_page"    => $perpage,
		));
		//The pagination links are automatically built according to those parameters

		/* -- Register the Columns -- */
		$columns                         = $this->get_columns();
		$_wp_column_headers[$screen->id] = $columns;

		$orderby = $orderby ? $orderby : 'ID';
		/*		foreach ($bpi_data as $node) {
					var_dump($node);die;
				}*/

		$this->items = Bpi::init()->search($paged);
	}

	/**
	 * Display the rows of records in the table
	 * @return string, echo the markup of the rows
	 */
	public function display_rows()
	{
		//Get the records registered in the prepare_items method
		$nodesList = $this->items;
		//Get the columns registered in the get_columns and get_sortable_columns methods
		list($columns, $hidden) = $this->get_column_info();

		//Loop for each record
		if ( ! empty($nodesList)) {
			foreach ($nodesList as $node) {
				//Open the line
				$properties = $node->getProperties();
				echo '<tr id="record_' . $properties['id'] . '">';
				foreach ($columns as $column_name => $column_display_name) {
					if ( ! $column_name) {
						continue;
					}

					//Style attributes for each col
					$class = "class='$column_name column-$column_name'";
					$style = "";
					if (in_array($column_name, $hidden)) {
						$style = ' style="display:none;"';
					}
					$attributes = $class . $style;
					switch ($column_name) {
						case '_actions':
							if (count($results = get_posts(array(
								'meta_key'   => 'bpi_id',
								'meta_value' => $properties['id'],
								'post_status' => 'any'
							)))) {
								$value = 'Already pulled. <a href="'.get_admin_url(NULL,'post.php?action=edit&post='.$results[0]->ID).'">Check</a>';
							} else {
								$value = '
							<a href="' . get_admin_url(null,
										'admin.php?page=bpi-syndication&action=pull&bpi-node-id=' . $properties['id']) . '">Pull</a>
							';
							}
							break;
						case 'agency_id':
							$value = $properties['agency_name'];
							break;
						default:
							$value = stripslashes($properties[$column_name]);
							break;
					}
					echo '<td ' . $attributes . '>' . $value . '</td>';
				}
				echo '</tr>';
			}
		}
	}
}