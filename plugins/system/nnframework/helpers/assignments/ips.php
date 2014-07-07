<?php
/**
 * NoNumber Framework Helper File: Assignments: IPs
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
 * Assignments: IPs
 */
class NNFrameworkAssignmentsIPs
{
	function passIPs(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		$selection = explode(',', str_replace(array(' ', "\r", "\n"), array('', '', ','), $selection));
		$ip = $_SERVER['REMOTE_ADDR'];
		$parts = explode('.', $_SERVER['REMOTE_ADDR']);

		$pass = 0;
		foreach ($selection as $range)
		{
			if (empty($range))
			{
				continue;
			}
			if (!(strpos($range, '-') === false))
			{
				// Selection is a range
				// check if ip is between or equal to the from and to ip range
				list($from, $to) = explode('-', trim($range), 2);
				// make the to value the maximum full ip it can be
				$to .= str_repeat('.255', 4 - count(explode('.', $to)));
				if ($ip >= trim($from) && $ip <= trim($to))
				{
					$pass = 1;
				}
			}
			else
			{
				// Selection is a single ip (part)
				// check if the parts of the ip match those of the selection
				$range = explode('.', trim($range));
				$pass = 1;
				foreach ($range as $i => $part)
				{
					if ($part != $parts[$i])
					{
						$pass = 0;
						break;
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
