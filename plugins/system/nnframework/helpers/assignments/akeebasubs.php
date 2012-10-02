<?php
/**
 * NoNumber Framework Helper File: Assignments: AkeebaSubs
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
 * Assignments: AkeebaSubs
 */
class NNFrameworkAssignmentsAkeebaSubs
{
	function init(&$parent)
	{
		if (!$parent->params->id && $parent->params->view == 'level') {
			$slug = JRequest::getVar('slug', '', 'default', 'string', 0);
			if ($slug) {
				$query = $parent->db->getQuery(true);
				$query->select('l.akeebasubs_level_id');
				$query->from('#__akeebasubs_levels AS l');
				$query->where('l.slug = ' . $parent->db->quote($slug));
				$parent->db->setQuery($query);
				$parent->params->id = $parent->db->loadResult();
			}
		}
	}

	function passPageTypes(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		return $parent->passPageTypes('com_akeebasubs', $selection, $assignment);
	}

	function passLevels(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		if (!$parent->params->id || $parent->params->option != 'com_akeebasubs' || $parent->params->view != 'level') {
			return $parent->pass(0, $assignment);
		}

		return $parent->passSimple($parent->params->id, $selection, $assignment);
	}
}