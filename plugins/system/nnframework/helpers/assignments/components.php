<?php
/**
 * NoNumber Framework Helper File: Assignments: Components
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
 * Assignments: Components
 */
class NNFrameworkAssignmentsComponents
{
	function passComponents(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		return $parent->passSimple(strtolower($parent->params->option), $selection, $assignment);
	}
}
