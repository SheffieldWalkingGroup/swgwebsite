<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla modelitem library
jimport('joomla.application.component.modelitem');
require_once JPATH_BASE."/swg/swg.php";

JLoader::register('Social', JPATH_BASE."/swg/Models/Social.php");
JLoader::register('Weekend', JPATH_BASE."/swg/Models/Weekend.php");
JLoader::register('EventAttendance', JPATH_BASE."/swg/Controllers/EventAttendance.php");
JLoader::register('Event', JPATH_BASE."/swg/Models/Event.php");

/**
 * Event listing model
 */
class SWG_EventsModelEventlisting extends JModelItem
{
	/**
	 * @var string msg
	 */
	protected $msg;
	
	private $loadedEvents = false;
	private $walks;
	private $numWalks;
	private $socials;
	private $numSocials;
	private $weekends;
	private $numWeekends;
	
	function __construct()
	{
	  $walks = array();
	  $socials = array();
	  $weekends = array();
	  
	  parent::__construct();
	}
	
	public function getProtocolReminders()
	{
	  $pr = array();
	  
	  $db = JFactory::getDBO();
	  $query = $db->getQuery(true);
	  $query->select("*");
	  $query->from("#__swg_events_protocolreminders");
	  
	  // Filter reminders to those for events on this page, e.g. only show walk reminders if there are walks on the page
	  $types = array();
	  if (JRequest::getBool("includeWalks"))
	    $types[] = "eventtype = ".SWG::EventType_Walk;
	  if (JRequest::getBool("includeSocials"))
	    $types[] = "eventtype = ".SWG::EventType_Social;
	  if (JRequest::getBool("includeWeekends"))
	    $types[] = "eventtype = ".SWG::EventType_Weekend;
	  $query->where($types, "OR");
	  
	  $query->order(array("Ordering ASC", "RAND()"));
	  $db->setQuery($query);
	  $protocolData = $db->loadAssocList();
	  
	  // Build an array of reminders
	  while (count($protocolData)) {
	    $thisProtocol = array_shift($protocolData);
	    $pr[] = $thisProtocol;
	  }
	  
	  return $pr;
	}
	
	public function getApiParams()
	{
		return array(
			"includeWalks=" . JRequest::getBool("includeWalks"),
			"includeSocials=" . JRequest::getBool("includeSocials"),
			"includeWeekends=" . JRequest::getBool("includeWeekends"),
			"startDateType=" . JRequest::getInt("startDateType"),
			"startDateSpecify=" . JRequest::getString("startDateSpecify"),
			"endDateType=" . JRequest::getInt("endDateType"),
			"endDateSpecify=" . JRequest::getString("endDateSpecify"),
			"order=" . JRequest::getBool("order"),
			"unpublished=" . JRequest::getBool("unpublished",false),
			
		);
	}
	
	public function getNumEvents()
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
		
		return ($this->numWalks + $this->numSocials + $this->numWeekends);
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
	  
	  // Get events that the current user has attended
	  // TODO: Only get this if showing events in the past?
	  $attended = EventAttendance::eventsAttendedBy(JFactory::getUser()->get('id'));
	  
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
	  	  
	  $nextEvent = null;
	  $events = array();
	  do {
	    $nextEvent = $this->nextEvent($nextEvent, $walkPointer, $socialPointer, $weekendPointer, (JRequest::getInt("order") == 1));
	    // Increment the pointer for this event type
	    // TODO: Pointer could be an array with event type as keys
	    if ($nextEvent instanceof WalkInstance)
	    {
			$walkPointer++;
		}
	    else if ($nextEvent instanceof Social)
	    {
			$socialPointer++;
		}
	    else if ($nextEvent instanceof Weekend)
	    {
			$weekendPointer++;
		}
	    else 
			// Unknown event - probably run out: stop looping and don't add it to the list
			break;
			
		if (in_array(array("type"=>$nextEvent->getType(), "id"=>$nextEvent->id), $attended))
		{
			$nextEvent->attended = true;
		}
	    
	    $events[] = $nextEvent;
	     
	  } while (
	    count($events) < 100 && (
          (count($this->walks) > $walkPointer) || 
          (count($this->socials) > $socialPointer) || 
          (count($this->weekends) > $weekendPointer)
      ));
	  
	  return $events;
	}
	
	/**
	 * Returns the next event after the one given
	 * @param Event $prevEvent Event to search from. Set to null if starting at the beginning
	 * @param int $minWalk Earliest possible walk to consider (pointer to class-level array). Not required, but makes this function much faster
	 * @param int $minSocial Earliest possible social to consider (pointer to class-level array). Not required, but makes this function much faster
	 * @param int $minWeekend Earliest possible weekend to consider (pointer to class-level array). Not required, but makes this function much faster
	 */
	private function nextEvent(Event $prevEvent=null, $minWalk=0, $minSocial=0, $minWeekend=0, $reverse=false)
	{
		// Get the first possible event of each type. Start at $minXX, ignoring any events that are the same as the one passed in, or with an earlier start time
		$nextWalk = $this->nextEventByType($prevEvent, $this->walks, $minWalk,$reverse);
		$nextSocial = $this->nextEventByType($prevEvent, $this->socials, $minSocial,$reverse);
		$nextWeekend = $this->nextEventByType($prevEvent, $this->weekends, $minWeekend,$reverse);
		
	  // Now find whether a walk, a social or a weekend is the next event
	  // If two are equal, put walks first, then socials, then weekends.
	  // Two events of the same type will go in the order they are in the database
	  // First, try to match all three
	  if (isset($nextWalk,$nextSocial,$nextWeekend))
	  {
		if ($reverse)
			$start = max($nextWalk->start, $nextSocial->start, $nextWeekend->start);
		else
			$start = min($nextWalk->start, $nextSocial->start, $nextWeekend->start);
	  }
	  // Now, match any two
	  elseif (isset($nextWalk, $nextSocial))
	  {
		if ($reverse)
			$start = max($nextWalk->start, $nextSocial->start);
		else
			$start = min($nextWalk->start, $nextSocial->start);
	  }
	  else if (isset($nextWalk, $nextWeekend))
	  {
		if ($reverse)
			$start = max($nextWalk->start, $nextWeekend->start);
		else
			$start = min($nextWalk->start, $nextWeekend->start);
	  }
	  else if (isset($nextSocial, $nextWeekend))
	  {
		if ($reverse)
			$start = max($nextSocial->start, $nextWeekend->start);
		else
			$start = min($nextSocial->start, $nextWeekend->start);
	  }
	  // We only have one - return that straight away without bothering to go to the next step
	  else if (isset($nextWalk))
	    return $nextWalk;
	  else if (isset($nextSocial))
	    return $nextSocial;
	  else if (isset($nextWeekend))
	    return $nextWeekend;
	  // Nothing...
	  else
	    return null;
	  
	  // If we get here, we found multiple possible events. Check which one it was and return it
	  if (isset($nextWalk) && $nextWalk->start == $start)
	    return $nextWalk;
	  else if (isset($nextSocial) && $nextSocial->start == $start)
	    return $nextSocial;
	  else if (isset($nextWeekend))
	    return $nextWeekend;
	  else
	    return null;
	}
	
	/**
	 * Finds the next event of a particular type
	 * 
	 * If there are any more events of this type, take the first event.
	 * Continue taking events until we reach the end, or we find the first event that isn't the previous one, or we find the first event that starts simultaneously or after the previous event.
	 *
	 * @param Event $prevEvent Previous event found - get the next event after this
	 * @param array $events Events to search through. Expected to be sorted.
	 * @param int $startIndex Start the search from this index in $events
	 * @param bool $reverse If true, get events BEFORE the last one
	 */
	private function nextEventByType($prevEvent, $events, $startIndex, $reverse=false)
	{
		$nextEvent = null;
		
		if (isset($events[$startIndex]))
		{
			// Take walks until we find one that isn't the previous event, and starts after or at the same time as the previous event
			do
			{
				$nextEvent = $events[$startIndex];
				$startIndex++;
				$class = get_class($nextEvent);
			} while (
				isset($events[$startIndex]) && 
				isset($prevEvent) && (
					($prevEvent instanceof $class && $nextEvent->id == $prevEvent->id) ||
					(!$reverse && $nextEvent->start < $prevEvent->start) ||
					($reverse && $nextEvent->start > $prevEvent->start)
				)
			);
		}
		
		return $nextEvent;
	}
	
	/**
	 * Loads and caches events of a specified type
	 * Can only load 100 events at a time to avoid slowdowns - more will be loaded as a 'bottomless page' type thing
	 * @param int $eventType Event type - see SWG constants
	 */
	private function loadEvents($eventType)
	{
		// Get the parameters set
		$startDate = $this->paramDateToValue(JRequest::getInt("startDateType"), JRequest::getString("startDateSpecify"));
		$endDate = $this->paramDateToValue(JRequest::getInt("endDateType"), JRequest::getString("endDateSpecify"));
		
		// Set specific factory parameters
		switch ($eventType) {
			case SWG::EventType_Walk:
				$factory = SWG::walkInstanceFactory();
				break;
			case SWG::EventType_Social:
				$factory = SWG::socialFactory();
				$factory->getNormal = true;
				$factory->getNewMember = true;
				break;
			case SWG::EventType_Weekend:
				$factory = SWG::weekendFactory();
				break;
		}
		
		// Set standard/shared factory parameters
		$factory->reset();
		$factory->startDate = $startDate;
		$factory->endDate = $endDate;
		$factory->limit = 100;
		$factory->offset = JRequest::getInt("offset",0);
		$factory->reverse = JRequest::getBool("order");
		$factory->showUnpublished = JRequest::getBool("unpublished",false);
		
		// Get events from factories
		switch ($eventType)
		{
			case SWG::EventType_Walk:
				$this->numWalks = $factory->numEvents();
				$this->walks = $factory->get();
				break;
			case SWG::EventType_Social:
				$this->numSocials = $factory->numEvents();
				$this->socials = $factory->get();
				break;
			case SWG::EventType_Weekend:
				$this->numWeekends = $factory->numEvents();
				$this->weekends = $factory->get();
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