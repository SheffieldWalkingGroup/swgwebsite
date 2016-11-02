<?php
/**
 * NoNumber Framework Helper File: Assignments: URLs
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
 * Assignments: URLs
 */
class NNFrameworkAssignmentsURLs
{
	function passURLs(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		$regex = isset($params->regex) ? $params->regex : 0;

		if (!is_array($selection))
		{
			$selection = explode("\n", $selection);
		}

		$url = JURI::getInstance();
		$url = $url->toString();

		$urls = array(
			html_entity_decode(urldecode($url), ENT_COMPAT, 'UTF-8'),
			urldecode($url),
			html_entity_decode($url, ENT_COMPAT, 'UTF-8'),
			$url
		);
		$urls = array_unique($urls);

		$pass = 0;
		foreach ($urls as $url)
		{
			foreach ($selection as $s)
			{
				$s = trim($s);
				if ($s != '')
				{
					if ($regex)
					{
						$url_part = str_replace(array('#', '&amp;'), array('\#', '(&amp;|&)'), $s);
						$s = '#' . $url_part . '#si';
						if (@preg_match($s . 'u', $url) || @preg_match($s, $url))
						{
							$pass = 1;
							break;
						}
					}
					else
					{
						if (strpos($url, $s) !== false)
						{
							$pass = 1;
							break;
						}
					}
				}
			}
			if ($pass)
			{
				break;
			}
		}

		return $parent->pass($pass, $assignment);
	}
}
