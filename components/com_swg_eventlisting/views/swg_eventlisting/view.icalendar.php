<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla view library
jimport('joomla.application.component.view');
 
/**
 * HTML View class for the SWG_EventListing Component
 */
class SWG_EventListingViewSWG_EventListing extends JView
{
	// Overwriting JView display method
	function display($tpl = "icalendar") 
	{
		// Assign data to the view
		$this->events = $this->get('Events');
 
		// Check for errors.
		if (count($errors = $this->get('Errors'))) 
		{
			JError::raiseError(500, implode('<br />', $errors));
			return false;
		}
		// Display the view
		parent::display($tpl);
	}
	
	/**
	 * Parse a text field (e.g. description) into valid iCalendar format
	 * 
	 * @param unknown_type $input
	 */
	function parseText($text) {
	  $text = str_replace(array("\n","\r","\r\n"), "\\n", $text);
	  return $text;
	}
}