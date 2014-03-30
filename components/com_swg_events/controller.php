<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla controller library
jimport('joomla.application.component.controller');
/**
 * SWG_Events Component Controller
 */
class SWG_EventsController extends JControllerLegacy
{
	public static function canAddWalk()
	{
		return JFactory::getUser()->authorise("walk.add", "com_swg_events");
	}
	public static function canEditWalk($walkOrID)
	{
		if (JFactory::getUser()->authorise("walk.editall", "com_swg_events"))
			return true;
		
		$f = SWG::walkInstanceFactory();
		$walk = $f->getSingle($walkOrID);
		
		return (
			isset($walk) && 
			JFactory::getUser()->authorise("walk.edit", "com_swg_events") && 
			$walk->leader->joomlaUserID == JFactory::getUser()->id
		);
	}
	
	public static function canAddSocial()
	{
		return JFactory::getUser()->authorise("social.add","com_swg_events");
	}
	public static function canEditSocial($socialOrID)
	{
		// TODO: Some should be able to edit own socials, e.g. publicity officers
		if ( JFactory::getUser()->authorise("social.editall","com_swg_events"))
			return true;
		
		return false;
	}
	
	public static function canAddWeekend()
	{
		return JFactory::getUser()->authorise("weekend.add","com_swg_events");
	}
	public static function canEditWeekend($weekendOrID)
	{
		// TODO: Some should be able to edit own weekends
		return JFactory::getUser()->authorise("weekend.editall","com_swg_events");
	}
	
	public static function canEdit($event)
	{
		if ($event instanceof WalkInstance)
			return self::canEditWalk($event);
		else if ($event instanceof Social)
			return self::canEditSocial($event);
		else if ($event instanceof Weekend)
			return self::canEditWeekend($event);
		else
			return false;
	}
}