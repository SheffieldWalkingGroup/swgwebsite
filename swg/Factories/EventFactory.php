<?php

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
	
	public function __construct()
	{
		$this->startDate = Event::DateToday;
		$this->endDate = Event::DateEnd;
		
		$this->limit = -1;
		$this->offset = 0;
		
		$this->reverse = false;
		
		$this->showUnpublished = false;
		
		$this->includeAttendees = false;
	}
	
	public function get()
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select("{$this->table}.*");
		$query->from($this->table);
		
		$query->where(array(
			$this->startDateField." >= '".Event::timeToDate($this->startDate)."'",
			$this->startDateField." <= '".Event::timeToDate($this->endDate)."'",
			"NOT deleted",
		));
		if (!$this->showUnpublished)
			$query->where($this->readyToPublishField);
		
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
		
		// TODO: Allow subclasses to hook in here
		
		$data = $db->loadAssocList();
		
		// Build an array of events
		$events = array();
		while (count($data) > 0 && count($events) != $this->limit)
		{
			$event = $this->newEvent();
			$event->fromDatabase(array_shift($data));
			$events[] = $event;
		}
		return $events;
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
			"NOT deleted",
		));
		if (!$this->showUnpublished)
			$query->where($this->readyToPublishField);
		
		$db->setQuery($query);
		
		return (int)$db->loadResult();
	}
	
	protected abstract function newEvent();
	
	
}