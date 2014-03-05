<?php
/**
 * NoNumber Framework Helper File: Assignments: Geo
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
 * Assignments: Browsers
 */
class NNFrameworkAssignmentsGeo
{
	var $geo = null;

	/**
	 * passContinents
	 */
	function passContinents(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		$selection = $parent->makeArray($selection);

		$geo = self::getGeo();
		if (!$geo)
		{
			return $parent->pass(0, $assignment);
		}
		$continent = $geo->geoplugin_continentCode;

		return $parent->passSimple($continent, $selection, $assignment);
	}

	/**
	 * passCountries
	 */
	function passCountries(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		$selection = $parent->makeArray($selection);

		$geo = self::getGeo();
		if (!$geo)
		{
			return $parent->pass(0, $assignment);
		}
		$country = $geo->geoplugin_countryCode;

		return $parent->passSimple($country, $selection, $assignment);
	}

	/**
	 * passRegions
	 */
	function passRegions(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		$selection = $parent->makeArray($selection);

		$geo = self::getGeo();
		if (!$geo)
		{
			return $parent->pass(0, $assignment);
		}
		$region = $geo->geoplugin_countryCode . '-' . $geo->geoplugin_regionCode;

		return $parent->passSimple($region, $selection, $assignment);
	}

	function getGeo()
	{
		if (!$this->geo)
		{
			require_once JPATH_PLUGINS . '/system/nnframework/helpers/functions.php';
			$func = new NNFrameworkFunctions;
			$this->geo = json_decode($func->getContents('http://www.geoplugin.net/json.gp?ip=' . $_SERVER['REMOTE_ADDR']));
		}
		return $this->geo;
	}
}
