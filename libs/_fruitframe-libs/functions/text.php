<?php

/**
 * Get word form for used number. Like 1 comment 2 comments no comments.
 * @param int $number
 * @param array $titles Numerical array of forms [0] - for 0, [1] for 1 and [2] for more then one
 * @return string
 */
function fruitframe_get_num_words($number, array $titles)
{
	$temp = strval($number);
	$temp = $temp[strlen($temp)-1];
	if (intval($number%100)<14 and intval($number%100)>10)
		return $titles[2];
	return ($temp > 1 and $temp < 5) ? $titles[1] : ($temp==1 ? $titles[0] : $titles[2]);
}

/**
 * Returns content part from post_content. Can return "more" or "excerpt".
 * Also it includes image logic. If post has no thumbnail, it will cut first image from post_content before any other operations.
 * @param string $type more | anons
 * @param null $id Will take current global $post if null.
 * @return string
 */
function fruitframe_get_content ($type, $id = NULL)
{
	if (!$id)
	{
		global $post;
		$hasThumbnail = has_post_thumbnail();
		$allPost = $post->post_content;
	}
	else
	{
		$hasThumbnail = has_post_thumbnail($id);
		$allPost = get_post($id)->post_content;
	}
	$allPost = $hasThumbnail || fruitframe_has_annotation($id) ? $allPost : fruitframe_remove_first_image($allPost);
	$explodeResult = explode('<!--more-->', $allPost);
	$annotation = $explodeResult[0];
	$more = empty($explodeResult[1]) ? '' : $explodeResult[1];
	if ($type == 'more')
		return $more;
	return $annotation;
}

/**
 * Alias for fruitframe_get_content with applying the_content filters.
 * Will return anons if there is <!--more--> in the post_content
 * @param null $id Will take current global $post if null.
 * @return string
 */
function fruitframe_get_post_annotation ($id = NULL)
{
	if (fruitframe_get_content('more', $id))
		return apply_filters('the_content', fruitframe_get_content('annotation', $id));
	else
		return;
}

/**
 * Alias for fruitframe_get_content.
 * Will return full post if there is no <!--more--> in the post_content
 * @param null $id Will take current global $post if null.
 * @return string
 */
function fruitframe_get_post_more($id = NULL)
{
	if (fruitframe_get_content('more', $id))
		return fruitframe_get_content('more', $id);
	else
		return fruitframe_get_content('annotation', $id);
}

/**
 * Alias for fruitframe_get_post_annotation.
 * @alias fruitframe_get_post_annotation
 * @param null $id Will take current global $post if null.
 * @return string
 */
function fruitframe_post_annotation($id = NULL)
{
	echo fruitframe_get_post_annotation($id);
}

/**
 * Alias for fruitframe_get_post_more.
 * @alias fruitframe_get_post_more
 * @param null $id Will take current global $post if null.
 * @return string
 */
function fruitframe_post_more ($id = NULL)
{
	echo fruitframe_get_post_more($id);
}

/**
 * Checkes if there is <!--more--> separator in post_content
 * @param null $id Will take current global $post if null.
 * @return bool
 */
function fruitframe_has_annotation($id = NULL)
{
	if (!$id)
		global $post;
	else
		$post = get_post($id);
	return stripos($post->post_content, '<!--more-->');
}

/**
 * Remove two line-breaks one-by-one.
 * @param string $content
 * @return string
 */
function fruitframe_remove_gap($content)
{
	return str_ireplace("\n\n", '\n\n', str_ireplace('&nbsp;', '', $content));
}


/**
 * Truncates string with specified length.
 *
 * @param string $string
 * @param int $length
 * @param string $etc
 * @param bool $break_words
 * @param bool $middle
 * @return string
 */
function fruitframe_truncate($string, $length = 80, $etc = '&#133;', $break_words = false, $middle = false) {
	if ($length == 0)
		return '';

	if (strlen($string) > $length) {
		$length -= min($length, strlen($etc));
		if (!$break_words && !$middle) {
			$string = preg_replace('/\s+?(\S+)?$/', '', substr($string, 0, $length+1));
		}
		if(!$middle) {
			return substr($string, 0, $length) . $etc;
		} else {
			return substr($string, 0, $length/2) . $etc . substr($string, -$length/2);
		}
	} else {
		return $string;
	}
}
if (!function_exists('mb_ucfirst')) {
	function mb_ucfirst($str, $encoding = "UTF-8", $lower_str_end = false) {
		$first_letter = mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding);
		if ($lower_str_end) {
			$str_end = mb_strtolower(mb_substr($str, 1, mb_strlen($str, $encoding), $encoding), $encoding);
		}
		else {
			$str_end = mb_substr($str, 1, mb_strlen($str, $encoding), $encoding);
		}
		$str = $first_letter . $str_end;
		return $str;
	}
}

if (!function_exists('mb_str_replace')) {
	function mb_str_replace($search, $replace, $subject) {
		if (is_array($subject)) {
			foreach ($subject as $key => $val) {
				$subject[$key] = mb_str_replace((string)$search, $replace, $subject[$key]);
			}
			return $subject;
		}
		$pattern = '/(?:'.implode('|', array_map(create_function('$match', 'return preg_quote($match[0], "/");'), (array)$search)).')/u';
		if (is_array($search)) {
			if (is_array($replace)) {
				$len = min(count($search), count($replace));
				$table = array_combine(array_slice($search, 0, $len), array_slice($replace, 0, $len));
				$f = create_function('$match', '$table = '.var_export($table, true).'; return array_key_exists($match[0], $table) ? $table[$match[0]] : $match[0];');
				$subject = preg_replace_callback($pattern, $f, $subject);
				return $subject;
			}
		}
		$subject = preg_replace($pattern, (string)$replace, $subject);
		return $subject;
	}
}

function fruitframe_hide_email($email)
{
	$character_set = '+-.0123456789@ABCDEFGHIJKLMNOPQRSTUVWXYZ_abcdefghijklmnopqrstuvwxyz';
	$key = str_shuffle($character_set); $cipher_text = ''; $id = 'e'.rand(1,999999999);
	for ($i=0;$i<strlen($email);$i+=1) $cipher_text.= $key[strpos($character_set,$email[$i])];
	$script = 'var a="'.$key.'";var b=a.split("").sort().join("");var c="'.$cipher_text.'";var d="";';
	$script.= 'for(var e=0;e<c.length;e++)d+=b.charAt(a.indexOf(c.charAt(e)));';
	$script.= 'document.getElementById("'.$id.'").innerHTML="<a href=\\"mailto:"+d+"\\">"+d+"</a>"';
	$script = "eval(\"".str_replace(array("\\",'"'),array("\\\\",'\"'), $script)."\")";
	$script = '<script type="text/javascript">/*<![CDATA[*/'.$script.'/*]]>*/</script>';
	return '<span id="'.$id.'">[javascript protected email address]</span>'.$script;
}

/**
 * Clean and format float summ values from text
 * @param string $summ
 * @param integer $decimals number of digits after decimal point
 */
function fruitframe_format_summ($summ, $decimals = 2)
{
	return number_format((float)preg_replace('/\D/i', '', $summ), $decimals, ',', ' ');
}