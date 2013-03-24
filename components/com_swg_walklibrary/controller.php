<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla controller library
jimport('joomla.application.component.controller');
/**
 * SWG_Walks Component Controller
 */
class SWG_WalkLibraryController extends JController
{
	/* Permissions checks */
	function canAdd()
	{
		return JFactory::getUser()->authorise("walk.add","com_swg_walklibrary");
	}

	function canEdit($walkOrID)
	{
		if (JFactory::getUser()->authorise("walk.editall","com_swg_walklibrary"))
			return true;
		else if (!JFactory::getUser()->authorise("walk.editown","com_swg_walklibrary"))
			return false;
		else
		{
			if (is_numeric($walkOrID))
				$walk = Walk::getSingle($walkOrID);
			else if ($walkOrID instanceof Walk)
				$walk = $walkOrID;
			
			if (empty($walk))
				throw new InvalidArgumentException("Invalid walk or ID");
			
			return ($walk->suggestedBy == Leader::getJoomlaUser(JFactory::getUser()->id));
		}
	}
}