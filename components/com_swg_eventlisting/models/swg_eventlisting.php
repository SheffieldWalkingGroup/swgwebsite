<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla modelitem library
jimport('joomla.application.component.modelitem');
require_once JPATH_BASE."/swg/swg.php";
JLoader::register('WalkInstance', JPATH_BASE."/swg/Models/WalkInstance.php");
JLoader::register('Social', JPATH_BASE."/swg/Models/Social.php");
JLoader::register('Weekend', JPATH_BASE."/swg/Models/Weekend.php");
 
/**
 * HelloWorld Model
 */
class SWG_EventListingModelSWG_EventListing extends JModelItem
{
	/**
	 * @var string msg
	 */
	protected $msg;
	
	private $loadedEvents = false;
	private $walks;
	private $socials;
	private $weekends;
	
	function __construct()
	{
	  $walks = array();
	  $socials = array();
	  $weekends = array();
	  
	  parent::__construct();
	}
 
	/**
	 * Get the message
	 * @return string The message to be displayed to the user
	 */
	public function getMsg() 
	{
		if (!isset($this->msg)) 
		{
			$this->msg = 'Hello World!! Woo!!';
		}
		return $this->msg;
	}
	
	/**
	 * Gets all the events we want to display:
	 * the main events we're listing and the others we want to mix in.
	 * @return array of events
	 */
	public function getEvents()
	{
	  // If we haven't already loaded the events, do so
	  if (!$this->loadedEvents) {
	    if (JRequest::getBool("includeWalks"))
	      $this->loadEvents(SWG::EventType_Walk);
	    if (JRequest::getBool("includeSocials"))
	      $this->loadEvents(SWG::EventType_Social);
	    if (JRequest::getBool("includeWeekends"))
	      $this->loadEvents(SWG::EventType_Weekend);
	    $this->loadedEvents = true;
	  }
	  
	  /* 
	   * Go through all lists in date order (oldest first).
	   * If there are multiple events on a given day,
	   * the order is Weekend, walk, social.
	   * This is based on the likely order that events will occur
	   * in reality.
	   * We maintain a set of dates of the next event seen,
	   * so can skip dates with no events.
	   */
	  if (count($this->walks) == 0 && count($this->socials) == 0 && count($this->weekends) == 0)
	    // no point in continuing
	    return array();
	  
	  // The pointers are needed anyway to simplify loops below
	  $walkPointer = 0;
	  $socialPointer = 0;
	  $weekendPointer = 0;
	  
	  $nextEvent = $this->nextEventAfterIndex();
	  
	  $events = array();
	  
	  do {
	    
	    // Get events happening on this date
	    while (isset($this->weekends[$weekendPointer]) && $this->weekends[$weekendPointer]->startDate == $nextEvent) {
	      $events[] = $this->weekends[$weekendPointer];
	      $weekendPointer++;
	    }
	    while (isset($this->walks[$walkPointer]) && $this->walks[$walkPointer]->startDate == $nextEvent) {
	      $events[] = $this->walks[$walkPointer];
	      $walkPointer++;
	    }
	    while (isset($this->socials[$socialPointer]) && $this->socials[$socialPointer]->startDate == $nextEvent) {
	      $events[] = $this->socials[$socialPointer];
	      $socialPointer++;
	    }
	    
	    // Get the dates of each next event
	    $nextEvent = $this->nextEventAfterIndex($walkPointer, $socialPointer, $weekendPointer);
	     
	  } while ((count($this->walks) > $walkPointer+1) || (count($this->socials) > $socialPointer+1) || (count($this->weekends) > $weekendPointer+1));
	  return $events;
	}
	
	/**
	 * Returns the date of the next event after the given date
	 * @param int $walkIndex Index to start at in the walks array 
	 * @param int $socialIndex Index to start at in the socials array
	 * @param int $weekendIndex Index to start at in the weekends array
	 * @return int Timestamp of next date with an event
	 */
	private function nextEventAfterIndex($walkIndex = 0, $socialIndex = 0, $weekendIndex = 0)
	{
	  $nextEvent = false;
	  if (isset($this->walks[$walkIndex]))
	  {
	    $nextEvent = $this->walks[$walkIndex]->startDate;
	  }
	  if (isset($this->socials[$socialIndex]))
	  {
	    if (isset($nextEvent))
	      $nextEvent = min($nextEvent, $this->socials[$socialIndex]->startDate);
	    else
	      $nextEvent = $this->socials[$socialIndex]->startDate;
	  }
	  if (isset($this->weekends[$weekendIndex]))
	  {
	    if (isset($nextEvent))
	      $nextEvent = min($nextEvent, $this->weekends[$weekendIndex]->startDate);
	    else
	      $nextEvent = $this->$weekendIndex[0]->startDate;
	  }
	  return $nextEvent;
	}
	
	
	/**
	 * Loads and caches events of a specified type
	 * @param int $eventType Event type - see SWG constants
	 */
	private function loadEvents($eventType)
	{
	  switch ($eventType) {
	    case SWG::EventType_Walk:
	      $this->walks = WalkInstance::get();
	      break;
	    case SWG::EventType_Social:
	      $this->socials = Social::get();
	      break;
	    case SWG::EventType_Weekend:
	      $this->weekends = Weekend::get();
	      break;
	  }
	}
	
	
}