<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

/**
* HTML Attendance class for the SWG Events component
* After the controller has run, we redirect to the event the user clicked on
*/
class SWG_EventsViewAttendance extends JView
{
	// Overwriting JView display method
	function display($tpl = null) 
	{
		$anchor .= "_".JRequest::getInt("evtid");
		$target = JURI::current() . "#" . $anchor;
		JFactory::getApplication()->redirect($target);
	}
	
}