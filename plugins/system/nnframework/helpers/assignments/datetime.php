<?php
/**
 * NoNumber Framework Helper File: Assignments: DateTime
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
 * Assignments: DateTime
 */
class NNFrameworkAssignmentsDateTime
{
	function passDate(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		if ((int) $params->publish_up || (int) $params->publish_down)
		{
			$now = strtotime($parent->date->format('Y-m-d H:i:s', 1));
			if (isset($params->recurring) && $params->recurring)
			{
				if (!(int) $params->publish_up || !(int) $params->publish_down)
				{
					// no date range set
					return ($assignment == 'include');
				}

				$up = strtotime(date('Y') . JFactory::getDate($params->publish_up)->format('-m-d H:i:s'));
				$down = strtotime(date('Y') . JFactory::getDate($params->publish_down)->format('-m-d H:i:s'));

				// pass:
				// 1) now is between up and down
				// 2) up is later in year than down and:
				// 2a) now is after up
				// 2b) now is before down
				if (
					($up < $now && $down > $now)
					|| ($up > $down
						&& (
							$up < $now
							|| $down > $now
						)
					)
				)
				{
					return ($assignment == 'include');
				}

				// outside date range
				return $parent->pass(0, $assignment);
			}
			if ((int) $params->publish_up)
			{
				if (strtotime($params->publish_up) > $now)
				{
					// outside date range
					return $parent->pass(0, $assignment);
				}
			}
			if ((int) $params->publish_down)
			{
				if (strtotime($params->publish_down) < $now)
				{
					// outside date range
					return $parent->pass(0, $assignment);
				}
			}
		}

		// pass or no date range set
		return ($assignment == 'include');
	}

	function passSeasons(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		$season = self::getSeason($parent->date, $params->hemisphere);
		return $parent->passSimple($season, $selection, $assignment);
	}

	function passMonths(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		$month = $parent->date->format('m', 1); // 01 (for January) through 12 (for December)
		return $parent->passSimple((int) $month, $selection, $assignment);
	}

	function passDays(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		$day = $parent->date->format('N', 1); // 1 (for Monday) though 7 (for Sunday )
		return $parent->passSimple($day, $selection, $assignment);
	}

	function passTime(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		$date = strtotime($parent->date->format('Y-m-d H:i:s', 1));

		$publish_up = strtotime($params->publish_up);
		$publish_down = strtotime($params->publish_down);

		$pass = 0;
		if ($publish_up > $publish_down)
		{
			// publish up is after publish down (spans midnight)
			// current time should be:
			// - after publish up
			// - OR before publish down
			if ($date >= $publish_up || $date < $publish_down)
			{
				$pass = 1;
			}
		}
		else
		{
			// publish down is after publish up (simple time span)
			// current time should be:
			// - after publish up
			// - AND before publish down
			if ($date >= $publish_up && $date < $publish_down)
			{
				$pass = 1;
			}
		}

		return $parent->pass($pass, $assignment);
	}

	function getSeason(&$d, $hemisphere = 'northern')
	{
		// Set $date to today
		$date = strtotime($d->format('Y-m-d H:i:s', 1));

		// Get year of date specified
		$date_year = $d->format('Y', 1); // Four digit representation for the year

		// Specify the season names
		$season_names = array('winter', 'spring', 'summer', 'fall');

		// Declare season date ranges
		switch (strtolower($hemisphere))
		{
			case 'southern':
				if (
					$date < strtotime($date_year . '-03-21')
					|| $date >= strtotime($date_year . '-12-21')
				)
				{
					return $season_names['2']; // Must be in Summer
				}
				else if ($date >= strtotime($date_year . '-09-23'))
				{
					return $season_names['1']; // Must be in Spring
				}
				else if ($date >= strtotime($date_year . '-06-21'))
				{
					return $season_names['0']; // Must be in Winter
				}
				else if ($date >= strtotime($date_year . '-03-21'))
				{
					return $season_names['3']; // Must be in Fall
				}
				break;
			case 'australia':
				if (
					$date < strtotime($date_year . '-03-01')
					|| $date >= strtotime($date_year . '-12-01')
				)
				{
					return $season_names['2']; // Must be in Summer
				}
				else if ($date >= strtotime($date_year . '-09-01'))
				{
					return $season_names['1']; // Must be in Spring
				}
				else if ($date >= strtotime($date_year . '-06-01'))
				{
					return $season_names['0']; // Must be in Winter
				}
				else if ($date >= strtotime($date_year . '-03-01'))
				{
					return $season_names['3']; // Must be in Fall
				}
				break;
			default: // northern
				if (
					$date < strtotime($date_year . '-03-21')
					|| $date >= strtotime($date_year . '-12-21')
				)
				{
					return $season_names['0']; // Must be in Winter
				}
				else if ($date >= strtotime($date_year . '-09-23'))
				{
					return $season_names['3']; // Must be in Fall
				}
				else if ($date >= strtotime($date_year . '-06-21'))
				{
					return $season_names['2']; // Must be in Summer
				}
				else if ($date >= strtotime($date_year . '-03-21'))
				{
					return $season_names['1']; // Must be in Spring
				}
				break;
		}
		return 0;
	}
}
