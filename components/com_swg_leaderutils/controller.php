<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla controller library
jimport('joomla.application.component.controller');

/**
 * SWG Leader Utilities Component Controller
 */
class SWG_LeaderUtilsController extends JControllerLegacy
{
	public static function canAddOtherProposal()
	{
		return JFactory::getUser()->authorise("walkproposal.addother","com_swg_leaderutils");
	}
	
	public static function canEditOtherProposal()
	{
		return JFactory::getUser()->authorise("walkproposal.editother","com_swg_leaderutils");
	}
}
