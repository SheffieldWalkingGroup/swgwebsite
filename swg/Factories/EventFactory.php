<?php

/**
 * Factory superclass for returning events.
 *
 * Factories are singletons - get a new factory by calling SWG::socialFactory(), SWG::walkInstanceFactory() or SWG::weekendFactory()
 * Call $factory->reset() on a new factory to clear any previously-set filters
 * Then add any filters or options you want
 * Finally, call a fetch method:
 * get() - gets any number of events matching the current filters
 * numEvents() - list the total number of events matching the current filters
 * getNext($num) - gets the next $num events from today. Ignores start & end date, limit & offset filters ($num is the limit)
 * getSingle($id) - gets the event with ID $id, from the cache if stored. If you pass an actual event in, it will be returned unchanged
 */
abstract class EventFactory
{
	/**
	 * Return only events ON OR AFTER this date. Understands day constants. Default is today.
	 * @var int
	 */
	public $startDate;
	/**
	 * Return only events ON OR BEFORE this date. Understands day constants. Default is the end of time
	 * @var int
	 */
	public $endDate;
	
	/**
	 * Return no more than this many events. Default is no limit (-1)
	 * @var int
	 */
	public $limit;
	/**
	 * Skip this many events before returning them. Default is none (0)
	 * @var int
	 */
	public $offset;
	
	/**
	 * Return events in descending date order. Default is ascending order (false)
	 * @var bool
	 */
	public $reverse;
	
	/**
	 * Show events that haven't been published yet. Default is to only show published events.
	 * @var bool
	 */
	public $showUnpublished;
	
	/**
	 * Include the list of attendees for each event (TODO). Default is not to include them.
	 * @var bool
	 */
	public $includeAttendees;
	
	/**
	 * The main table to read for this event
	 * @var string
	 */
	protected $table;
	
	/**
	 * The ID field on the main table
	 * @var string
	 */
	protected $idField;
	/**
	 * Field containing the start date
	 * @var string
	 */
	protected $startDateField;
	/**
	 * Field containing the start time
	 * @var string
	 */
	protected $startTimeField;
	/**
	 * Field marking if the event is ready to publish
	 * @var string
	 */
	protected $readyToPublishField;
	
	/**
	 * Cache of events we've already loaded, with the ID as the key
	 * @var Event[]
	 */
	protected $cache;
	
	/**
	 * Create a new factory with default settings
	 */
	public function __construct()
	{
		$this->reset();
	}
	
	/**
	 * Resets all filters to their defaults
	 * @return void
	 */
	public function reset()
	{
		$this->startDate = Event::DateToday;
		$this->endDate = Event::DateEnd;
		
		$this->limit = -1;
		$this->offset = 0;
		
		$this->reverse = false;
		
		$this->showUnpublished = false;
		
		$this->includeAttendees = false;
	}
	
	/**
	 * Execute a search with the current settings.
	 * Returns an array of events.
	 * Always gets results from the database and overwrites anything in the cache.
	 * @return Event[]
	 */
	public function get()
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select("{$this->table}.*");
		$query->from($this->table);
		
		$query->where(array(
			$this->startDateField." >= '".Event::timeToDate($this->startDate)."'",
			$this->startDateField." <= '".Event::timeToDate($this->endDate)."'",
		));
		if (!$this->showUnpublished)
			$query->where($this->readyToPublishField);
			
		// This allows subclasses to modify the query. Normally they'll add extra WHERE clauses.
		$this->modifyQuery($query);
		
		if ($this->reverse)
		{
			$order = array($this->startDateField." DESC");
			if (!empty($this->startTimeField))
				$order[] = $this->startTimeField." DESC";
		}
		else
		{
			$order = array($this->startDateField." ASC");
			if (!empty($this->startTimeField))
				$order[] = $this->startTimeField." ASC";
		}
		$query->order($order);
		
		$db->setQuery($query, $this->offset, $this->limit);
		
		$data = $db->loadAssocList();
		
		// Build an array of events
		$events = array();
		while (count($data) > 0 && count($events) != $this->limit)
		{
			$event = $this->newEvent();
			$event->fromDatabase(array_shift($data));
			$events[] = $event;
			$this->cache[$event->id] = $event;
		}
		return $events;
	}
	
	/**
	 * Make any modifications needed to the query.
	 * Query is passed by reference and modified in-place.
	 * To be implemented by subclasses
	 * @param JDatabaseQuery $query
	 */
	protected function modifyQuery(JDatabaseQuery &$query)
	{
		
	}
	
	/**
	 * Return the total number of events matching this factory's settings,
	 * ignoring any limit & offset
	 */
	public function numEvents()
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select("count(1) as count");
		$query->from($this->table);
		
		$query->where(array(
			$this->startDateField." >= '".Event::timeToDate($this->startDate)."'",
			$this->startDateField." <= '".Event::timeToDate($this->endDate)."'",
		));
		if (!$this->showUnpublished)
			$query->where($this->readyToPublishField);
		
		$this->modifyQuery($query);
		
		$db->setQuery($query);
		
		return (int)$db->loadResult();
	}
	
	/**
	 * Get the next few events based on the current factory settings
	 * Start date is always today, end date is always the end.
	 */
	public function getNext($numEvents)
	{
		// Clone the current factory so we can override its settings without affecting other uses
		// We use a clone instead of a new object so we can keep the settings that aren't fixed by getNext.
		$factory = clone($this);
		$factory->startDate = Event::DateToday;
		$factory->endDate = Event::DateEnd;
		$factory->limit = (int)$numEvents;
		$factory->offset = 0;
		$factory->reverse = false;
		
		$res = $factory->get();
		// Put these results into THIS factory's cache
		foreach ($res as $id=>$evt)
			$this->cache[$id] = $evt;
		
		return $res;
	}
	
	/**
	 * Get a single event with a known ID. Uses the cache if we've already loaded it.
	 * @param int|Event $evt Event ID. If an actual event is passed in, the event is returned unchanged
	 * @return Event
	 */
	public function getSingle($evt)
	{
		if ($evt instanceof Event)
			return $evt;
		
		if (isset($this->cache[$evt]))
			return $this->cache[$evt];
		
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select("*");
		
		$query->from($this->table);
		$query->where(array($this->idField." = ".intval($id)));
		$db->setQuery($query);
		$res = $db->query();
		if ($db->getNumRows($res) == 1)
		{
			$evt = $this->newEvent();
			$evt->fromDatabase($db->loadAssoc());
			return $evt;
		}
		else
			return null;
	}
	
	/**
	 * Create a new (blank) event object of this type
	 * @return Event
	 */
	protected abstract function newEvent();
	
	// TODO: Simple methods to check if a given user has permission to do an action (mostly limit events to those the user can view)
	
}