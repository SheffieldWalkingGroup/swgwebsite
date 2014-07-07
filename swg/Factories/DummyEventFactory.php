<?php
require_once(JPATH_BASE."/swg/Models/DummyEvent.php");
require_once("EventFactory.php");


/**
 * Factory for returning socials
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
class DummyEventFactory extends EventFactory 
{
	/**
	 * Get dummy events that go with walks
	 * @var bool
	 */
	public $getWithWalks = false;
	/**
	 * Get dummy events that go with socials
	 * @var bool
	 */
	public $getWithSocials = false;
	/**
	 * Get dummy events that go with weekends
	 * @var bool
	 */
	public $getWithWeekends = false;
	
	/**
	 * The main table to read for this event
	 * @var string
	 */
	protected $table = "dummyevent";
	
	protected $useEventsTable = true;
	
	protected $eventTypeConst = Event::TypeDummy;
	
	protected function newEvent()
	{
		return new DummyEvent();
	}
	
	protected function modifyQuery(JDatabaseQuery &$query)
	{
		$showWhat = array();
		if ($this->getWithWalks)
			$showWhat[] = "showWithWalks";
		if ($this->getWithSocials)
			$showWhat[] = "showWithSocials";
		if ($this->getWithWeekends)
			$showWhat[] = "showWithWeekends";
		$query->where("(".implode(" OR ", $showWhat).")");
	}
	
	public function cumulativeStats()
	{
		return array(); // Doesn't make sense for dummy events
	}
}