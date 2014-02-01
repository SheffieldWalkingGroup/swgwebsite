<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla modelitem library
jimport('joomla.application.component.modelitem');
require_once JPATH_BASE."/swg/swg.php";

JLoader::register('Social', JPATH_BASE."/swg/Models/Social.php");
JLoader::register('Weekend', JPATH_BASE."/swg/Models/Weekend.php");
JLoader::register('Event', JPATH_BASE."/swg/Models/Event.php");
JLoader::register('WalkInstance', JPATH_BASE."/swg/Models/WalkInstance.php");
JLoader::register('WalkProgramme', JPATH_BASE."/swg/Models/WalkProgramme.php");

/**
 * Event listing model
 */
class SWG_EventsModelEventDetails extends JModelItem
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
	
	/**
	 * Get the requested event
	 * This comes from the URL parameters ?type=<type>&id=<id>,
	 * or from the URL extension /<type>-<id>
	 */
	public function getEvent()
	{
		$input = JFactory::getApplication()->input;
		if ($input->get("type") && $input->get("id"))
		{
			$type = strtolower($input->get("type"));
			$id = (int)($input->get("id"));
			
			switch($type)
			{
				case "walk":
					$factory = SWG::walkInstanceFactory();
					break;
				case "social":
					$factory = SWG::socialFactory();
					break;
				case "weekend":
					$factory = SWG::weekendFactory();
					break;
				default:
					jexit("Invalid event type");
			}
			
			$event = $factory->getSingle($id);
			if ($event == null)
			{
				jexit("Invalid event");
			}
			return $event;
		}
		else
		{
		    jexit("TODO: Redirect");
		}
		

		
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
	  	  
	  $nextEvent = null;
	  $events = array();
	  do {
	    $nextEvent = $this->nextEvent($nextEvent, $walkPointer, $socialPointer, $weekendPointer, (JRequest::getBool("order")));
	    
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
		$factory = SWG::eventFactory($eventType);
		$startDate = $this->paramDateToValue(JRequest::getInt("startDateType"), JRequest::getString("startDateSpecify"));
		$endDate = $this->paramDateToValue(JRequest::getInt("endDateType"), JRequest::getString("endDateSpecify"));
		
		$factory->reset();
		
		// Set specific factory parameters
		if ($eventType == SWG::EventType_Social)
		{
			$factory->getNormal = true;
			$factory->getNewMember = true;
		}
		if ($eventType == SWG::EventType_Walk)
		{
			if (JRequest::getInt("walkProgramme") != 0)
			{
				switch (JRequest::getInt("walkProgramme"))
				{
					case 1:
						// Current published
						$factory->walkProgramme = WalkProgramme::getCurrentProgrammeID();
						break;
					case 2:
						$factory->walkProgramme = WalkProgramme::getNextProgrammeID();
						break;
					case 3:
						// Specify
						$factory->walkProgramme = JRequest::getInt("walkProgrammeSpecify");
						break;
				}
			}
		}
		
		// Set standard/shared factory parameters
		$factory->startDate = $startDate;
		$factory->endDate = $endDate;
		$factory->limit = 100;
		$factory->offset = JRequest::getInt("offset",0);
		$factory->reverse = JRequest::getBool("order");
		$factory->showUnpublished = JRequest::getBool("unpublished",false);
		$factory->includeAttendees = true;
		$factory->includeAttendedBy = Jfactory::getUser()->id;
		if (JRequest::getBool("diaryMode", false))
		{
			// Default is the current logged in user.
			// TODO: Allow view access to other people's diaries
			$factory->addAttendee(JFactory::getUser()->id);
		}
		
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
	      return Event::DateToday;
	    case 3: // Tomorrow
	      return Event::DateTomorrow;
	    case 4: // The end
	      return Event::DateEnd;
	    case 5:
	      return strtotime($specifiedDate);
	    default:
		  return null;
	  }
	}
	
	
}