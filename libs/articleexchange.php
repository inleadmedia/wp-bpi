<?php
namespace WordpressAe;

class ArticleExchange extends \Fruitframe\Pattern_Singleton
{
	/**
	 * @var \Bpi
	 */
	protected $_ae;
	protected $_amountPerPage = 10;

	protected function __construct()
	{
		if (intval(\wpJediOptions::get_option('ae_options', 'content_per_page')) > 0)
		{
			$this->_amountPerPage = intval(\wpJediOptions::get_option('ae_options', 'content_per_page'));
		}
		$this->_ae = new \Bpi(
			\wpJediOptions::get_option('ae_options', 'url'),
			\wpJediOptions::get_option('ae_options', 'agency_id'),
			\wpJediOptions::get_option('ae_options', 'public_key'),
			\wpJediOptions::get_option('ae_options', 'secret_key')
		);
	}

	/**
	 * @todo: add try-catch
	 *
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
	public function search($page = 1, $sort_by = 'pushed', $sort = 'desc', $search = '', $audience = '', $category = '', $agency_id = '', $author = '')
	{
		$offset  = ($page - 1) * $this->_amountPerPage;
		$filters = array(
			'category'  => $category,
			'audience'  => $audience,
			'agency_id' => $agency_id,
			'author'    => $author,
		);

		return $this->_ae->searchNodes(array(
			'amount' => $this->_amountPerPage,
			'offset' => $offset,
			'filter' => $filters,
			'sort'   => array(
				$sort_by => $sort,
			),
			'search' => $search,
		));
	}

	public function getDictionaries()
	{
		return $this->_ae->getDictionaries();
	}

	public function getAmountPerPage()
	{
		return $this->_amountPerPage;
	}

	public function getNode($aeNodeId)
	{
		try
		{
			return $this->_ae->getNode($aeNodeId);
		} catch ( \Exception $exception )
		{
			//Somehow save errors
			return null;
		}
	}

	public function pushNode($aeNode)
	{
		$push_result = $this->_ae->push($aeNode)->getProperties();

		if (empty($push_result['id']))
		{
			return false;
		}

		return $push_result;
	}

	public function deleteNode($aeNodeId)
	{
		try
		{
			return $this->_ae->deleteNode($aeNodeId);
		} catch ( \Exception $exception )
		{
			return null;
		}
	}
}