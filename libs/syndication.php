<?php
namespace WordpressBpi;

use Fruitframe\Renderer;

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

	protected function _getPaged()
	{
		return max(1, intval(@$_GET["paged"]));
	}

	protected function _buildUrl($param, $value = null, $param2 = null, $value2 = null)
	{
		$params = array(
			'filter-category'  => sanitize_text_field(@$_GET['filter-category']),
			'filter-audience'  => sanitize_text_field(@$_GET['filter-audience']),
			'filter-search'    => sanitize_text_field(@$_GET['filter-search']),
			'filter-agency-id' => sanitize_text_field(@$_GET['filter-agency-id']),
			'filter-agency'    => sanitize_text_field(@$_GET['filter-agency']),
			'filter-author'    => sanitize_text_field(@$_GET['filter-author']),
			'page'             => 'bpi-syndication',
		);
		if ( ! empty($param) && array_key_exists($param, $params)) {
			if ( ! $value) {
				unset($params[$param]);
			} else {
				$params[$param] = $value;
			}
		}
		if ( ! empty($param2) && array_key_exists($param2, $params)) {
			if ( ! $value2) {
				unset($params[$param2]);
			} else {
				$params[$param2] = $value2;
			}
		}

		return admin_url('admin.php?') . build_query($params);
	}

	public
	function extra_tablenav(
		$which
	) {
		if ('top' == $which) {
			echo '<h2>BPI Library</h2>';
			$dictionaries = Bpi::init()->getDictionaries();

			$selectedAuthor     = sanitize_text_field(@$_GET['filter-author']);
			$selectedAgency     = sanitize_text_field(@$_GET['filter-agency-id']);
			$selectedAgencyName = sanitize_text_field(@$_GET['filter-agency']);
			$selectedAudience   = sanitize_text_field(@$_GET['filter-audience']);
			$selectedCategory   = sanitize_text_field(@$_GET['filter-category']);
			$searchText         = sanitize_text_field(@$_GET['filter-search']);

			$links = array();

			if ($selectedAuthor) {
				$links[] = array(
					'link'  => $this->_buildUrl('filter-author'),
					'param' => 'Author',
					'value' => $selectedAuthor,
				);
			}
			if ($selectedAgency) {
				$links[] = array(
					'link'  => $this->_buildUrl('filter-agency-id', null, 'filter-agency', null),
					'param' => 'Agency',
					'value' => $selectedAgencyName,

				);
			}

			echo Renderer::render_template('filters', array(
				'categories'       => $dictionaries['category'],
				'audience'         => $dictionaries['audience'],
				'selectedAudience' => $selectedAudience,
				'selectedCategory' => $selectedCategory,
				'searchText'       => $searchText,
				'links'            => $links,
				'additionalParams' => array(
					'page'             => 'bpi-syndication',
					'filter-agency-id' => $selectedAgency,
					'filter-agency'    => $selectedAgencyName,
					'filter-author'    => $selectedAuthor,
				)

			));
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

		?>
	<?php
	}

	public
	function get_columns()
	{
		return array(
			'title'       => 'Title',
			'pushed'      => 'Date',
			'agency_name' => 'Agency',
			'category'    => 'Category',
			'_details'    => 'Details',
			'_actions'    => 'Actions'
		);
	}

	public
	function get_sortable_columns()
	{
		return array(
			'title'  => 'title',
			'pushed' => 'pushed'
		);
	}

	/**
	 * Prepare the table with different parameters, pagination, columns and table elements
	 */
	public
	function prepare_items()
	{
		global $_wp_column_headers;
		$screen = get_current_screen();

		//Parameters that are going to be used to order the result
		$orderby = ! empty($_GET["orderby"]) ? sanitize_text_field($_GET["orderby"]) : 'pushed';
		$order   = ! empty($_GET["order"]) ? sanitize_text_field($_GET["order"]) : 'desc';

		//Which page is this?
		$paged = $this->_getPaged();


		$selectedAudience = sanitize_text_field(@$_GET['filter-audience']);
		$selectedCategory = sanitize_text_field(@$_GET['filter-category']);
		$searchText       = sanitize_text_field(@$_GET['filter-search']);
		$selectedAuthor   = sanitize_text_field(@$_GET['filter-author']);
		$selectedAgency   = sanitize_text_field(@$_GET['filter-agency-id']);

		$bpiResponse = Bpi::init()->search($paged, $orderby, $order, $searchText, $selectedAudience,
			$selectedCategory,
			$selectedAgency, $selectedAuthor);
		$this->items = $bpiResponse;


		/* -- Pagination parameters -- */
		$totalitems = $bpiResponse->total;

		//How many to display per page?
		$perpage = Bpi::init()->getAmountPerPage();

		//How many pages do we have in total?
		$totalpages = ceil($totalitems / $perpage);
		//adjust the query to take pagination into account

		/* -- Register the pagination -- */
		$this->set_pagination_args(array(
			"total_items" => $totalitems,
			"total_pages" => $totalpages,
			"per_page"    => $perpage,
		));

		/* -- Register the Columns -- */
		$_wp_column_headers[$screen->id] = $this->get_columns();
	}

	/**
	 * Display the rows of records in the table
	 * @return string, echo the markup of the rows
	 */
	public
	function display_rows()
	{
		$nodesList = $this->items;
		list($columns, $hidden) = $this->get_column_info();
		if ($nodesList->count()) {
			foreach ($nodesList as $node) {
				$properties = $node->getProperties();
				$assets     = $node->getAssets();
				//				var_dump(count($assets));
				echo '<tr id="record_' . $properties['id'] . '">';
				foreach ($columns as $column_name => $column_display_name) {
					if ( ! $column_name) {
						continue;
					}

					switch ($column_name) {
						case 'pushed':
							$value = date('Y-m-d H:i', strtotime($properties['pushed']));
							break;
						case 'title':

							$value = '<strong>' . $properties['title'] . '</strong>';
							if ( ! empty($properties['teaser']) && $properties['title'] != $properties['teaser']) {
								$value .= '<br/>' . $properties['teaser'];
							}
							break;
						case '_actions':
							if (count($results = get_posts(array(
								'meta_key'    => 'bpi_id',
								'meta_value'  => $properties['id'],
								'post_status' => 'any'
							)))) {
								$value = 'Already pulled. <a href="' . get_admin_url(null,
										'post.php?action=edit&post=' . $results[0]->ID) . '">Check</a>';
							} else {
								$value = '
							<a href="' . admin_url('admin.php?page=bpi-syndication&action=pull&bpi-node-id=' . $properties['id']) . '">Pull</a>
							';
							}
							break;
						case '_details':
							$value =
								'Author: <a href="' . $this->_buildUrl('filter-author',
									$properties['author']) . '">' . $properties['author'] . '</a><br/>' .
								'Audience: <strong>' . $properties['audience'] . '</strong><br/>' .
								'Editable: <strong>' . ($properties['editable'] ? 'Yes' : 'No') . '</strong><br/>' .
								'With photos: <strong>' . (count($assets) ? 'Yes' : 'No') . '</strong><br/>';
							break;
						case 'agency_name':
							$value = '<a href="' . $this->_buildUrl('filter-agency-id',
									$properties['agency_id'], 'filter-agency',
									$properties['agency_name']) . '">' . $properties['agency_name'] . '</a>';
							break;
						default:
							$value = stripslashes($properties[$column_name]);
							break;
					}
					echo '<td class="' . $column_name . ' column-' . $column_name . '"' . (in_array($column_name,
							$hidden) ? ' style="display:none;" ' : '') . '>' . $value . '</td>';
				}
				echo '</tr>';
			}
		} else {
			echo '<tr class="no-items"><td class="colspanchange" colspan="' . $this->get_column_count() . '">';
			$this->no_items();
			echo '</td></tr>';
		}
	}
}