<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla view library
jimport('joomla.application.component.view');
 
/**
 * HTML Event listing class for the SWG Events component
 */
class SWG_EventsViewEventListing extends JView
{
	// Overwriting JView display method
	function display($tpl = null) 
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
	 * True if the given date is NOT this year
	 * @param unknown_type $date
	 * @return boolean
	 */
	function notThisYear($date)
	{
	  return (date("Y", $date) != date("Y"));
	}
	
	/**
	 * True if two dates have DIFFERENT months. Ignores year
	 * @param unknown_type $date1
	 * @param unknown_type $date2
	 */
	function notSameMonth($date1, $date2)
	{
	  return (date("m", $date1) != date("m",$date2));
	}
}