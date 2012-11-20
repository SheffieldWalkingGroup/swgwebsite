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
		$this->protocolReminders = $this->get('ProtocolReminders');
		 
		// Check for errors.
		if (count($errors = $this->get('Errors'))) 
		{
			JError::raiseError(500, implode('<br />', $errors));
			return false;
		}
		
		// Add map interface Javascript
		$document = JFactory::getDocument();
		JHtml::_('behavior.framework', true);
		$document->addScript('/libraries/openlayers/OpenLayers.js');
		$document->addScript('/swg/js/maps.js');
		$document->addScript('/components/com_swg_events/views/eventlisting/script/eventlisting.js');
		$document->addScriptDeclaration(<<<MAP
window.addEvent('domready', function()
{
    registerMapLinks();
});
		    
MAP
);		
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
	
	/**
	 * True if time is not midnight. 
	 * We're making an assumption here that no event will start at midnight,
	 * but if that's wrong all that happens is the start time doesn't appear in the event info
	 * @param  $timestamp
	 */
	function isTimeSet($timestamp)
	{
	  return (date("His", $timestamp) != 0);
	}
}