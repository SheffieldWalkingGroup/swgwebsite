<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

include_once(JPATH_SITE."/components/com_swg_events/helpers/views/eventinfo.html.php");

/**
* HTML Event listing class for the SWG Events component
*/
class SWG_EventsViewEventDetails extends SWG_EventsHelperEventInfo
{
	// Overwriting JView display method
	function display($tpl = null) 
	{
		// Assign data to the view
		$this->event = $this->get('Event');
		$this->forceMapOpen = true;
		
		// Check for errors.
		if (count($errors = $this->get('Errors'))) 
		{
			JError::raiseError(500, implode('<br />', $errors));
			return false;
		}
		
		$document = JFactory::getDocument();
		$this->mapJS($document);
		$containerID = strtolower($this->event->type)."_".$this->event->id;
		$document->addScriptDeclaration(<<<MAP
window.addEvent('domready', function()
{
	setupEventsShared();
	var event= new Event();
	event.populateFromHTML({$containerID});
	event.setupMap();
	event.mapContainer.style.height = "400px";
	event.mapOpen = true;
});
MAP
);	

		// Display the view
		parent::display($tpl);
	}
	
}