<?php
/**
 * NoNumber Framework Helper File: Assignments: Components
 *
 * @package         NoNumber Framework
 * @version         12.9.7
 *
 * @author          Peter van Westen <peter@nonumber.nl>
 * @link            http://www.nonumber.nl
 * @copyright       Copyright © 2012 NoNumber All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
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