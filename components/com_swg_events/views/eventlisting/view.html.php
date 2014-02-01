<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

include_once(JPATH_SITE."/components/com_swg_events/helpers/views/eventinfo.html.php");

/**
* HTML Event listing class for the SWG Events component
*/
class SWG_EventsViewEventListing extends SWG_EventsHelperEventInfo
{
	// Overwriting JView display method
	function display($tpl = null) 
	{
		// Assign data to the view
		$this->events = $this->get('Events');
		$this->protocolReminders = $this->get('ProtocolReminders');
		
		// Check for errors.
		if (count($errors = $this->get('Errors'))) 
		{
			JError::raiseError(500, implode('<br />', $errors));
			return false;
		}
		
		$document = JFactory::getDocument();
		$this->mapJS($document);
		$totalEvents = $this->get('NumEvents');
		$apiParams = json_encode($this->get('ApiParams'));
		$document->addScriptDeclaration(<<<MAP
window.addEvent('domready', function()
{
	registerMapLinks();
	document.addEvent("scroll",scrolled);
	totalEvents = {$totalEvents};
	apiParams = {$apiParams};
});
MAP
);	

		// Display the view
		parent::display($tpl);
	}
	
	function showAnyAddLinks()
	{
		return ($this->showAddWalk() || $this->showAddSocial() || $this->showAddWeekend());
	}
	
	function showAddWalk()
	{
		return (JRequest::getBool("showEditOptions") && $this->addEditWalkURL() && SWG_EventsController::canAddWalk());
	}
	
	function addEditWalkURL()
	{
		$itemid = JRequest::getInt('addEditWalkPage');
		if (empty($itemid))
			return false;
		$item = JFactory::getApplication()->getMenu()->getItem($itemid);
		$link = new JURI($item->route);
		return $link;
	}
	
	function showAddSocial()
	{
		return (JRequest::getBool("showEditOptions") && $this->addEditSocialURL() &&  SWG_EventsController::canAddSocial());
	}
	
	function addEditSocialURL()
	{
		$itemid = JRequest::getInt('addEditSocialPage');
		if (empty($itemid))
			return false;
		$item = JFactory::getApplication()->getMenu()->getItem($itemid);
		$link = new JURI($item->route);
		return $link;
	}
	
	function showAddWeekend()
	{
		return (JRequest::getBool("showEditOptions") && $this->addEditWeekendURL() &&  SWG_EventsController::canAddWeekend());
	}
	
	function addEditWeekendURL()
	{
		$itemid = JRequest::getInt('addEditWeekendPage');
		if (empty($itemid))
			return false;
		$item = JFactory::getApplication()->getMenu()->getItem($itemid);
		$link = new JURI($item->route);
		return $link;
	}
	
}