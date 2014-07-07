<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

/**
* Methods shared by all event info views
*/
abstract class SWG_EventsHelperEventInfo extends JViewLegacy
{
	protected $forceMapOpen = false;
	
	/**
	 * Adds the map javascript to the document
	 */
	protected function mapJS(JDocument $document)
	{
		// Add map interface Javascript
		JHtml::_('behavior.framework', true);
		$document->addScript('/libraries/openlayers/OpenLayers.debug.js');
		$document->addScript('/swg/js/maps.js');
		$document->addScript('/swg/js/events.js');
	}
	
	/**
	* True if the given date is NOT this year
	* @param int $date
	* @return boolean
	*/
	public function notThisYear($date)
	{
		return (date("Y", $date) != date("Y"));
	}
	
	/**
	* True if two dates have DIFFERENT months. Ignores year
	* @param int $date1
	* @param int $date2
	*/
	public function notSameMonth($date1, $date2)
	{
		return (date("m", $date1) != date("m",$date2));
	}
	
	/**
	* True if time is not midnight. 
	* We're making an assumption here that no event will start at midnight,
	* but if that's wrong all that happens is the start time doesn't appear in the event info
	* @param int $timestamp
	*/
	public function isTimeSet($timestamp)
	{
		return (date("His", $timestamp) != 0);
	}
	
	/**
	 * True if the event is in the past.
	 * In practice, we check if the event's end date (if any) or start date is on a day before today.
	 * @param Event $evt
	 * @return bool
	 */
	public function eventInPast(Event $event)
	{
		if (isset($evt->endDate))
		{
			$evtDate = $event->endDate;
		}
		else
		{
			$evtDate = $event->start;
		}
		
		return (unixtojd($evtDate) < unixtojd(time()));
	}
	
	public function showEditLinks($event)
	{
		return (
			JRequest::getBool("showEditOptions") && 
			SWG_EventsController::canEdit($event) && 
			(
				($event instanceof WalkInstance && $this->addEditWalkURL()) ||
				($event instanceof Social && $this->addEditSocialURL()) ||
				($event instanceof Weekend && $this->addEditWeekendURL())
			)
		);
	}
	
	public function editURL($event)
	{
		if ($event instanceof WalkInstance)
			return $this->addEditWalkURL()."?walkinstanceid=".$event->id;
		else if ($event instanceof Social)
			return $this->addEditSocialURL()."?socialid=".$event->id;
		else if ($event instanceof Weekend)
			return $this->addEditWeekendURL()."?weekendid=".$event->id;
		else
			return "";
	}
}