<?php
namespace WordpressAe;

class tagAuthorFunctional
{
	static protected $tagPattern = '/<<([^>]+)>>/';
	static protected $authorPattern = '/{{([^}]+)}}/';

	static protected function parse($text, $pattern)
	{
		preg_match_all($pattern, $text, $matches);
		return $matches[1];
	}

	static public function parseTags($text)
	{
		return self::parse($text, self::$tagPattern);
	}

	static public function parseAuthor($text)
	{
		if (is_array($author = self::parse($text, self::$authorPattern)) && count($author))
		{
			return array_shift($author);
		}
		return '';
	}

	static public function clearMatches($text)
	{
		return trim(preg_replace(array(self::$authorPattern, self::$tagPattern), '', $text));
	}

	static public function includeAuthor($text, $author)
	{
		return $text.' {{'.$author.'}}';
	}

	static public function includeTags($text, array $tags = array())
	{
		return $text .' <<'.implode('>> <<', $tags).'>> ';
	}

	static public function isIndexdataActive()
	{
		$indexDataOption = get_option('widget_indexdata_widget');
		return (is_array($indexDataOption) && count($indexDataOption) > 1);
	}
}