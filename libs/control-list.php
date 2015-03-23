<?php
//Our class extends the WP_List_Table class, so we need to make sure that it's there
if ( ! class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class Control_List_Table extends WP_List_Table
{
	protected $_column_headers;

	function __construct()
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

	function showStatistics()
	{
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
	}

	function extra_tablenav($which)
	{
		/**
		 * @var Wpdb
		 */
		global $wpdb;
		if ('top' == $which) {
			$this->showStatistics();
		} elseif ($which == 'bottom') {
			echo '<a href="' . admin_url('admin.php?page=printweek_control&section=xls') . '" id="generate-xsl" style="float: left" class="button-secondary">Сгенерировать XSL</a>';
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

	function get_columns()
	{
		return array(
			'post_title'  => 'Название материала',
			'post_type'   => 'Тип контента',
			'category'    => 'Рубрика/и',
			'post_author' => 'Автор',
			'symbols'     => 'Количество символов с пробелами',
			'post_date'   => 'Дата создания',
		);
	}

	function get_sortable_columns()
	{
		return array(
			'post_type'   => 'post_type',
			'post_author' => 'post_author',
			'post_title'  => 'post_title',
			'post_date'   => 'post_date',
		);
	}

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 */
	function prepare_items()
	{
		require_once __DIR__.'/../vendor/bpi/sdk/Bpi/Sdk/Bpi.php';
		$bpi = new Bpi(wpJediOptions::get_option('bpi_options', 'url'),wpJediOptions::get_option('bpi_options', 'agency_id'),
				wpJediOptions::get_option('bpi_options', 'public_key'),
				wpJediOptions::get_option('bpi_options', 'secret_key'));

		try {

			$amount = max(1, intval(wpJediOptions::get_option('bpi_options', 'content_per_page')));
			$offset = 1 * $amount;

			$bpi_data = $bpi->searchNodes(
				array(
					'amount' => $amount,
					'offset' => $offset,
					'filter' => array(),
					/*					'sort'   => array(
											'pushed' => $sort,
										),*/
					//					'search' => $query,
				)
			);

//			var_dump($bpi_data);
//			die;
			if ($bpi_data->count() > 0) {
//				return $bpi_data;
			}
		} catch ( Exception $e ) {
			watchdog_exception('bpi', $e);
			drupal_set_message(t('Failed to fetch data. Check reports for more information.'), 'error');
		}


		//		var_dump();


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
		$where      = " WHERE post_type IN ('analytics','blogs','magazine','post','green_bag','news_from_the_field') AND post_status = 'publish' $dateWhere ORDER BY ";
		$query      = 'SELECT COUNT(*) FROM ' . $wpdb->posts . $where . $orderby . ' ' . $order;
		$totalitems = $wpdb->get_var($query);

		//How many to display per page?
		$perpage = 2;
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

		$orderby     = $orderby ? $orderby : 'ID';
		$this->items = $wpdb->get_results('SELECT * FROM ' . $wpdb->posts . $where . $orderby . ' ' . $order . ' ' . $limit);
	}

	/**
	 * Display the rows of records in the table
	 * @return string, echo the markup of the rows
	 */
	function display_rows()
	{
		//Get the records registered in the prepare_items method
		$records = $this->items;
		//Get the columns registered in the get_columns and get_sortable_columns methods
		list($columns, $hidden) = $this->get_column_info();
		$postTypes = array(
			'analytics' => 'Аналитика',
			'blogs'     => 'Статьи блоггеров',
			'magazine'  => 'Номер журнала',
			'post'      => 'Статья'
		);

		//Loop for each record
		if ( ! empty($records)) {
			foreach ($records as $rec) {
				//Open the line
				echo '<tr id="record_' . $rec->ID . '">';
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
						case 'post_title':
							$value = '<a href="' . admin_url('post.php?post=' . $rec->ID . '&action=edit') . '">' . $rec->{$column_name} . '</a>';
							break;
						case 'post_type':
							$value = $postTypes[$rec->post_type];
							break;
						case 'category':
							foreach (get_the_category($rec->ID) as $category) {
								$value .= ($value ? ', ' : '') . $category->name;
							}
							break;
						case 'post_author':
							$value = get_the_author_meta('display_name', $rec->post_author);
							break;
						case 'symbols':
							$value = mb_strlen(strip_tags($rec->post_content), 'UTF-8');
							break;
						default:
							$value = stripslashes($rec->{$column_name});
							break;
					}
					echo '<td ' . $attributes . '>' . $value . '</td>';
				}
				echo '</tr>';
			}
		}
	}
}