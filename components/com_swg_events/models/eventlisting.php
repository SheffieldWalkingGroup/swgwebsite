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
	private $dummy;
	private $numDummy;
	
	private $startDate;
	private $endDate;
	private $walkProgramme;
	
	function __construct()
	{
		$this->walks = array();
		$this->socials = array();
		$this->weekends = array();
		
		$this->startDate = null;
		$this->endDate = null;
		$this->walkProgramme = null;
		
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
			$types[] = "eventtype = ".Event::TypeWalk;
		if (JRequest::getBool("includeSocials"))
			$types[] = "eventtype = ".Event::TypeSocial;
		if (JRequest::getBool("includeWeekends"))
			$types[] = "eventtype = ".Event::TypeWeekend;
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
			"includeWalks" => JRequest::getBool("includeWalks") ? 1 : 0,
			"includeSocials" => JRequest::getBool("includeSocials") ? 1 : 0,
			"includeWeekends" => JRequest::getBool("includeWeekends") ? 1 : 0,
			"startDateType" => JRequest::getInt("startDateType"),
			"startDateSpecify" => JRequest::getString("startDateSpecify"),
			"endDateType" => JRequest::getInt("endDateType"),
			"endDateSpecify" => JRequest::getString("endDateSpecify"),
			"order" => JRequest::getBool("order") ? 1 : 0,
			"unpublished" => JRequest::getBool("unpublished",false) ? 1 : 0,
			"diaryMode" => JRequest::getBool("diaryMode", false) ? 1 : 0,
			
		);
	}
	
	public function getNumEvents()
	{
		// If we haven't already loaded the events, do so
		if (!$this->loadedEvents) {
			if (JRequest::getBool("includeWalks"))
				$this->loadEvents(Event::TypeWalk);
			if (JRequest::getBool("includeSocials"))
				$this->loadEvents(Event::TypeSocial);
			if (JRequest::getBool("includeWeekends"))
				$this->loadEvents(Event::TypeWeekend);
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
				$this->loadEvents(Event::TypeWalk);
			if (JRequest::getBool("includeSocials"))
				$this->loadEvents(Event::TypeSocial);
			if (JRequest::getBool("includeWeekends"))
				$this->loadEvents(Event::TypeWeekend);
			if (JRequest::getBool("includeDummy"))
				$this->loadEvents(Event::TypeDummy);
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
		if (count($this->walks) == 0 && count($this->socials) == 0 && count($this->weekends) == 0 && count($this->dummy) == 0)
			// no point in continuing
			return array();
	  
		// The pointers are needed anyway to simplify loops below
		$walkPointer = 0;
		$socialPointer = 0;
		$weekendPointer = 0;
		$dummyPointer = 0;
		
		$nextEvent = null;
		$events = array();
		do {
			$nextEvent = $this->nextEvent($nextEvent, $walkPointer, $socialPointer, $weekendPointer, $dummyPointer, (JRequest::getBool("order")));
			
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
			else if ($nextEvent instanceof DummyEvent)
			{
				$dummyPointer++;
			}
			else 
				// Unknown event - probably run out: stop looping and don't add it to the list
				break;
			
			$events[] = $nextEvent;
			
		} while (
			count($events) < 100 && (
			(count($this->walks) > $walkPointer) || 
			(count($this->socials) > $socialPointer) || 
			(count($this->weekends) > $weekendPointer) ||
			(count($this->dummy) > $dummyPointer)
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
	private function nextEvent(Event $prevEvent=null, $minWalk=0, $minSocial=0, $minWeekend=0, $minDummy=0, $reverse=false)
	{
		// Get the first possible event of each type. Start at $minXX, ignoring any events that are the same as the one passed in, or with an earlier start time
		$nextWalk = $this->nextEventByType($prevEvent, $this->walks, $minWalk,$reverse);
		$nextSocial = $this->nextEventByType($prevEvent, $this->socials, $minSocial,$reverse);
		$nextWeekend = $this->nextEventByType($prevEvent, $this->weekends, $minWeekend,$reverse);
		$nextDummy = $this->nextEventByType($prevEvent, $this->dummy, $minDummy,$reverse);
		
		// Now find whether a walk, a social or a weekend is the next event
		// If two are equal, put walks first, then socials, then weekends.
		// Two events of the same type will go in the order they are in the database
		// First, try to match all three
		if ($reverse)
			$walkStart = $socialStart = $weekendStart = $dummyStart = 0;
		else
			$walkStart = $socialStart = $weekendStart = $dummyStart = Event::DateEnd;
		
		if (isset($nextWalk))
			$walkStart = $nextWalk->start;
		if (isset($nextSocial))
			$socialStart = $nextSocial->start;
		if (isset($nextWeekend))
			$weekendStart = $nextWeekend->start;
		if (isset($nextDummy))
			$dummyStart = $nextDummy->start;
		
		if ($reverse)
			$start = max($walkStart, $socialStart, $weekendStart, $dummyStart);
		else
			$start = min($walkStart, $socialStart, $weekendStart, $dummyStart);
			
		if ($start == Event::DateEnd || $start == 0)
			return null;
		
		// Now return the first matching event. If multiple events, the order is:
		// 1. Walks 2. Socials 3. Weekends 4. Dummy
		if ($walkStart == $start)
			return $nextWalk;
		else if ($socialStart == $start)
			return $nextSocial;
		else if ($weekendStart == $start)
			return $nextWeekend;
		else if ($dummyStart == $start)
			return $nextDummy;
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
		$this->startDate = $this->paramDateToValue(JRequest::getInt("startDateType"), JRequest::getString("startDateSpecify"));
		$this->endDate = $this->paramDateToValue(JRequest::getInt("endDateType"), JRequest::getString("endDateSpecify"));
		
		$factory->reset();
		
		// Set specific factory parameters
		if ($eventType == Event::TypeSocial)
		{
			$factory->getNormal = true;
			$factory->getNewMember = true;
		}
		else if ($eventType == Event::TypeWalk)
		{
			if (JRequest::getInt("walkProgramme") != 0)
			{
				switch (JRequest::getInt("walkProgramme"))
				{
					case 1:
						// Current published
						$this->walkProgramme = WalkProgramme::getCurrentProgrammeID();
						break;
					case 2:
						$this->walkProgramme = WalkProgramme::getNextProgrammeID();
						break;
					case 3:
						// Specify
						$this->walkProgramme = JRequest::getInt("walkProgrammeSpecify");
						break;
				}
			}
		}
		else if ($eventType == Event::TypeDummy)
		{
			$factory->getWithWalks = JRequest::getBool("includeWalks");
			$factory->getWithSocials = JRequest::getBool("includeSocials");
			$factory->getWithWeekends = JRequest::getBool("includeWeekends");
		}
		
		// Set standard/shared factory parameters
		$factory->walkProgramme = $this->walkProgramme;
		$factory->startDate = $this->startDate;
		$factory->endDate = $this->endDate;
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
			case Event::TypeWalk:
				$this->numWalks = $factory->numEvents();
				$this->walks = $factory->get();
				break;
			case Event::TypeSocial:
				$this->numSocials = $factory->numEvents();
				$this->socials = $factory->get();
				break;
			case Event::TypeWeekend:
				$this->numWeekends = $factory->numEvents();
				$this->weekends = $factory->get();
				break;
			case Event::TypeDummy:
				$this->numDummy = $factory->numEvents();
				$this->dummy = $factory->get();
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