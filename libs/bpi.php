<?php
/**
 * Created by PhpStorm.
 * User: keriat
 * Date: 3/24/15
 * Time: 6:11 PM
 */
namespace WordpressBpi;

class Bpi extends \Fruitframe\Pattern_Singleton
{
	/**
	 * @var \Bpi
	 */
	protected $_bpi;
	protected $_amountPerPage = 10;

	protected function __construct()
	{
		if (intval(\wpJediOptions::get_option('bpi_options', 'content_per_page')) > 0) {
			$this->_amountPerPage = intval(\wpJediOptions::get_option('bpi_options', 'content_per_page'));
		}
		$this->_bpi = new \Bpi
		(
			\wpJediOptions::get_option('bpi_options', 'url'),
			\wpJediOptions::get_option('bpi_options', 'agency_id'),
			\wpJediOptions::get_option('bpi_options', 'public_key'),
			\wpJediOptions::get_option('bpi_options', 'secret_key')
		);
	}

	/**
	 * @todo: add try-catch
	 * @param int    $page
	 * @param string $sort_by
	 * @param string $sort
	 * @param string $search
	 * @param string $audience
	 * @param string $category
	 * @param string $agency_id
	 * @param string $author
	 *
	 * @return \Bpi\Sdk\NodeList
	 */
	public function search(
		$page = 1,
		$sort_by = 'pushed',
		$sort = 'desc',
		$search = '',
		$audience = '',
		$category = '',
		$agency_id = '',
		$author = ''
	) {
		$offset  = ($page-1) * $this->_amountPerPage;
		$filters = array(
			'category'  => $category,
			'audience'  => $audience,
			'agency_id' => $agency_id,
			'author'    => $author,
		);
		return $this->_bpi->searchNodes(
			array(
				'amount' => $this->_amountPerPage,
				'offset' => $offset,
				'filter' => $filters,
				'sort'   => array(
					$sort_by => $sort,
				),
				'search' => $search,
			)
		);
		/**
		 * Parses the BPI search result into more simpler structures.
		 *
		 * @return array
		 *   Array of bpi fetched items, in structure:
		 *   - bpi_id: bpi identifier
		 *   - title: item title
		 *   - date: item creation date in the BPI system
		 *   - teaser: content teaser
		 *   - body: content body
		 *   - author: content author
		 *   - category: content category
		 *   - agency: content agency
		 *   - audience: content audience
		 *   - total_count: overall amount of items in the result
		 *   - assets: array of links representing the images content
		 *
		 * function bpi_search_get_items() {
		 * $params = _bpi_build_query();
		 * $phrase = isset($params[BPI_SEARCH_PHRASE_KEY]) ? $params[BPI_SEARCH_PHRASE_KEY] : '';
		 * $page = pager_find_page();
		 * $sort = isset($_GET['sort']) ? $_GET['sort'] : BPI_SORT_DESCENDING;
		 * $filters = array(
		 * 'category' => isset($params['category']) ? $params['category'] : '',
		 * 'audience' => isset($params['audience']) ? $params['audience'] : '',
		 * 'agency_id' => isset($params['agency']) ? $params['agency'] : '',
		 * 'author' => isset($params['author']) ? $params['author'] : '',
		 * );
		 *
		 * $response = bpi_search_content($phrase, $page, $filters, $sort);
		 * $bpi_nodes = array();
		 *
		 * // Get agency_id=>agency cache.
		 * $agency_cache = array();
		 * $cache = cache_get(BPI_AGENCY_CACHE);
		 * if ($cache) {
		 * $agency_cache = $cache->data;
		 * }
		 *
		 * foreach ($response as $item) {
		 * /* @var $item \Bpi\Sdk\Item\Node *
		 * $current_item = $item->getProperties();
		 * $assets = $item->getAssets();
		 *
		 * $agency = isset($current_item['agency_name']) ? $current_item['agency_name'] : '';
		 * $agency_id = isset($current_item['agency_id']) ? $current_item['agency_id'] : '';
		 *
		 * // Set agency into cache.
		 * if (!empty($agency) && empty($agency_cache[$agency_id])) {
		 * $agency_cache[$agency_id] = $agency;
		 * }
		 *
		 * // Transform \Bpi\Sdk\Document properties items into array.
		 * $bpi_nodes[] = array(
		 * 'bpi_id' => isset($current_item['id']) ? $current_item['id'] : '',
		 * 'title' => isset($current_item['title']) ? $current_item['title'] : '',
		 * 'date' => isset($current_item['pushed']) ? $current_item['pushed'] : '',
		 * 'teaser' => isset($current_item['teaser']) ? $current_item['teaser'] : '',
		 * 'body' => isset($current_item['body']) ? $current_item['body'] : '',
		 * 'author' => isset($current_item['author']) ? $current_item['author'] : '',
		 * 'category' => isset($current_item['category']) ? $current_item['category'] : '',
		 * 'agency' => $agency,
		 * 'agency_id' => $agency_id,
		 * 'audience' => isset($current_item['audience']) ? $current_item['audience'] : '',
		 * 'total_count' => isset($response->total) ? $response->total : 0,
		 * 'assets' => (count($assets) > 0) ? $assets : array(),
		 * 'editable' => !empty($current_item['editable']),
		 * );
		 * }
		 *
		 * // Save changes in agency cache.
		 * if (empty($cache) || (!empty($cache) && $agency_cache != $cache->data)) {
		 * cache_set(BPI_AGENCY_CACHE, $agency_cache);
		 * }
		 *
		 * return $bpi_nodes;
		 * }*/
	}

	public function getDictionaries()
	{
		return $this->_bpi->getDictionaries();
	}

	public function getAmountPerPage()
	{
		return $this->_amountPerPage;
	}

	public function getNode($bpiNodeId)
	{
		try {
			return $this->_bpi->getNode($bpiNodeId);
		} catch ( \Exception $exception ) {
			//Somehow save errors
			return null;
		}
	}

	public function pushNode($bpiNode)
	{
		$push_result = $this->_bpi->push($bpiNode)->getProperties();

		if ( ! empty($push_result['id'])) {
			add_post_meta($bpiNode['local_id'], 'bpi_push_status', 1);
			add_post_meta($bpiNode['local_id'], 'bpi_id', $push_result['id']);
			add_post_meta($bpiNode['local_id'], 'bpi_timestamp', time());
		}
	}
}