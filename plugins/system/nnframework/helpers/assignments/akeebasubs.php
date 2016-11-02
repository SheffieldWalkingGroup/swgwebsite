<?php
/**
 * NoNumber Framework Helper File: Assignments: AkeebaSubs
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
 * Assignments: AkeebaSubs
 */
class NNFrameworkAssignmentsAkeebaSubs
{
	function init(&$parent)
	{
		if (!$parent->params->id && $parent->params->view == 'level')
		{
			$slug = JFactory::getApplication()->input->getString('slug', '');
			if ($slug)
			{
				$parent->q->clear()
					->select('l.akeebasubs_level_id')
					->from('#__akeebasubs_levels AS l')
					->where('l.slug = ' . $parent->db->quote($slug));
				$parent->db->setQuery($parent->q);
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
		if (!$parent->params->id || $parent->params->option != 'com_akeebasubs' || $parent->params->view != 'level')
		{
			return $parent->pass(0, $assignment);
		}

		return $parent->passSimple($parent->params->id, $selection, $assignment);
	}
}
