<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla modelitem library
jimport('joomla.application.component.modelitem');
require_once JPATH_BASE."/swg/swg.php";
JLoader::register('WalkInstance', JPATH_BASE."/swg/Models/WalkInstance.php");
JLoader::register('Social', JPATH_BASE."/swg/Models/Social.php");
JLoader::register('Weekend', JPATH_BASE."/swg/Models/Weekend.php");
JLoader::register('Event', JPATH_BASE."/swg/Models/Event.php");
 
/**
 * HelloWorld Model
 */
class SWG_EventsModelEventlisting extends JModelItem
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
	  
	  // Order 0 = ascending, 1 = descending
	  if (JRequest::getInt("order") == 1) {
	    $this->walks = array_reverse($this->walks);
	    $this->socials = array_reverse($this->socials);
	    $this->weekends = array_reverse($this->weekends);
	  }
	  $nextEvent = null;
	  $events = array();
	  do {
	    $nextEvent = $this->nextEvent($nextEvent, $walkPointer, $socialPointer, $weekendPointer, (JRequest::getInt("order") == 1));
	    $events[] = $nextEvent;
	    // Increment the pointer for this event type
	    if ($nextEvent instanceof WalkInstance)
	      $walkPointer++;
	    else if ($nextEvent instanceof Social)
	      $socialPointer++;
	    else if ($nextEvent instanceof Weekend)
	      $weekendPointer++;
	     
	  } while (
          (count($this->walks) > $walkPointer+1) || 
          (count($this->socials) > $socialPointer+1) || 
          (count($this->weekends) > $weekendPointer+1)
      );
	  return $events;
	}
	
	/**
	 * Returns the next event after the one given
	 * @param Event $event Event to search from. Set to null if starting at the beginning
	 * @param int $minWalk Earliest possible walk to consider (pointer to class-level array). Not required, but makes this function much faster
	 * @param int $minSocial Earliest possible social to consider (pointer to class-level array). Not required, but makes this function much faster
	 * @param int $minWeekend Earliest possible weekend to consider (pointer to class-level array). Not required, but makes this function much faster
	 */
	private function nextEvent(Event $event=null, $minWalk=0, $minSocial=0, $minWeekend=0)
	{
	  // Get the first possible event of each type. Start at $minXX, ignoring any events that are the same as the one passed in, or with an earlier start time
	  do {
	    $nextWalk = $this->walks[$minWalk];
	    $minWalk++;
	  } while (isset($event) && (($event instanceof Walk && $nextWalk->id == $event->id) || $nextWalk->start < $event->start));
	  
	  do {
	    $nextSocial = $this->socials[$minSocial];
	    $minSocial++;
	  } while (isset($event) && (($event instanceof Social && $nextSocial->id == $event->id) || $nextSocial->start < $event->start));
	  
	  do {
	    $nextWeekend = $this->weekends[$minWeekend];
	    $minWeekend++;
	  } while (isset($event) && (($event instanceof Weekend && $nextWeekend->id == $event->id) || $nextWeekend->start < $event->start));
	  
	  // Now find whether a walk, a social or a weekend is the next event
	  // If two are equal, put walks first, then socials, then weekends.
	  // Two events of the same type will go in the order they are in the database
	  $start = min($nextWalk->start, $nextSocial->start, $nextWeekend->start);
	  if ($nextWalk->start == $start)
	    return $nextWalk;
	  else if ($nextSocial->start == $start)
	    return $nextSocial;
	  else
	    return $nextWeekend;
	}
	
	/**
	 * Loads and caches events of a specified type
	 * @param int $eventType Event type - see SWG constants
	 */
	private function loadEvents($eventType)
	{
	  // Get the parameters set
	  $startDate = $this->paramDateToValue(JRequest::getInt("startDateType"), JRequest::getString("startDateSpecify"));
	  $endDate = $this->paramDateToValue(JRequest::getInt("endDateType"), JRequest::getString("endDateSpecify"));
	  
	  switch ($eventType) {
	    case SWG::EventType_Walk:
	      $this->walks = WalkInstance::get($startDate, $endDate);
	      break;
	    case SWG::EventType_Social:
	      $this->socials = Social::get($startDate, $endDate);
	      break;
	    case SWG::EventType_Weekend:
	      $this->weekends = Weekend::get($startDate, $endDate);
	      break;
	  }
	}
	
	/**
	 * Parses a date selected by Joomla component parameters into a known constant or Unix time
	 * @param int $dateType Type of date (0=beginning, 1=yesterday, 2=today, 3=tomorrow, 4=end, 5=specify
	 * @param String $specifiedDate Specified date (pass 5 for dateType to use this)
	 */
	private function paramDateToValue($dateType, $specifiedDate="") {
	  switch ($dateType) {
	    case 0: // The beginning
	      return 0;
	    case 1: // Yesterday
	      return Event::DateYesterday;
	    case 2: // Today
	    default:
	      return Event::DateToday;
	    case 3: // Tomorrow
	      return Event::DateTomorrow;
	    case 4: // The end
	      return Event::DateEnd;
	    case 5:
	      return strtotime($specifiedDate);
	  }
	}
	
	
}