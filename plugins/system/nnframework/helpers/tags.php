<?php
/**
 * NoNumber Framework Helper File: Tags
 *
 * @package         NoNumber Framework
 * @version         14.2.6
 *
 * @author          Peter van Westen <peter@nonumber.nl>
 * @link            http://www.nonumber.nl
 * @copyright       Copyright © 2014 NoNumber All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

/**
 * Functions
 */
class NNTags
{
	public static function getTagValues($str = '', $keys = array('title'), $separator = '|', $equal = ':', $limit = 0)
	{
		$s = '[[S]]';
		$e = '[[E]]';
		$t1 = '[[T]]';
		$t2 = '[[/T]]';

		// protect all html tags
		if (preg_match_all('#</?[a-z][^>]*>#si', $str, $matches, PREG_SET_ORDER) > 0)
		{
			foreach ($matches as $match)
			{
				$str = str_replace($match['0'], $t1 . base64_encode($match['0']) . $t2, $str);
			}
		}

		// replace separators and equal signs with special markup
		$str = str_replace(array($separator, $equal), array($s, $e), $str);
		// replace protected separators and equal signs back to original
		$str = str_replace(array('\\' . $s, '\\' . $e), array($separator, $equal), $str);

		// split string into array
		if ($limit)
		{
			$vals = explode($s, $str, (int) $limit);
		}
		else
		{
			$vals = explode($s, $str);
		}

		// initialize return vars
		$t = new stdClass;
		$t->params = array();

		// loop through splits
		foreach ($vals as $i => $keyval)
		{
			// spit part into key and val by equal sign
			$keyval = explode($e, $keyval, 2);
			if (isset($keyval['1']))
			{
				$keyval['1'] = str_replace(array($s, $e), array($separator, $equal), $keyval['1']);
			}

			// unprotect tags in key and val
			foreach ($keyval as $k => $v)
			{
				if (preg_match_all('#' . preg_quote($t1, '#') . '(.*?)' . preg_quote($t2, '#') . '#si', $v, $matches, PREG_SET_ORDER) > 0)
				{
					foreach ($matches as $match)
					{
						$v = str_replace($match['0'], base64_decode($match['1']), $v);
					}
					$keyval[trim($k)] = $v;
				}
			}

			if (isset($keys[$i]))
			{
				$key = trim($keys[$i]);
				// if value is in the keys array add as defined in keys array
				// ignore equal sign
				$val = implode($equal, $keyval);
				if (substr($val, 0, strlen($key) + 1) == $key . '=')
				{
					$val = substr($val, strlen($key) + 1);
				}
				$t->{$key} = $val;
				unset($keys[$i]);
			}
			else
			{
				// else add as defined in the string
				if (isset($keyval['1']))
				{
					$t->{$keyval['0']} = $keyval['1'];
				}
				else
				{
					$t->params[] = implode($equal, $keyval);
				}
			}
		}

		return $t;
	}

	public static function setSurroundingTags($pre, $post, $tags = 0)
	{
		if ($tags == 0)
		{
			$tags = array(
				'div', 'p', 'span', 'pre', 'a',
				'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
				'strong', 'b', 'em', 'i', 'u', 'big', 'small', 'font'
			);
		}
		$a = explode('<', $pre);
		$b = explode('</', $post);
		if (count($b) > 1 && count($a) > 1)
		{
			$a = array_reverse($a);
			$a_pre = array_pop($a);
			$b_pre = array_shift($b);
			$a_tags = $a;
			foreach ($a_tags as $i => $a_tag)
			{
				$a[$i] = '<' . trim($a_tag);
				$a_tags[$i] = preg_replace('#^([a-z0-9]+).*$#', '\1', trim($a_tag));
			}
			$b_tags = $b;
			foreach ($b_tags as $i => $b_tag)
			{
				$b[$i] = '</' . trim($b_tag);
				$b_tags[$i] = preg_replace('#^([a-z0-9]+).*$#', '\1', trim($b_tag));
			}
			foreach ($b_tags as $i => $b_tag)
			{
				if ($b_tag && in_array($b_tag, $tags))
				{
					foreach ($a_tags as $j => $a_tag)
					{
						if ($b_tag == $a_tag)
						{
							$a_tags[$i] = '';
							$b[$i] = trim(preg_replace('#^</' . $b_tag . '.*?>#', '', $b[$i]));
							$a[$j] = trim(preg_replace('#^<' . $a_tag . '.*?>#', '', $a[$j]));
							break;
						}
					}
				}
			}
			foreach ($a_tags as $i => $tag)
			{
				if ($tag && in_array($tag, $tags))
				{
					array_unshift($b, trim($a[$i]));
					$a[$i] = '';
				}
			}
			$a = array_reverse($a);
			list($pre, $post) = array(implode('', $a), implode('', $b));
		}
		return array(trim($pre), trim($post));
	}
}
