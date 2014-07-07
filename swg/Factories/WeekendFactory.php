<?php
require_once(JPATH_BASE."/swg/Models/Weekend.php");
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
class WeekendFactory extends EventFactory 
{
	/**
	 * The main table to read for this event
	 * @var string
	 */
	protected $table = "weekendsaway";
	
	protected $idField = "ID";
	/**
	 * Field containing the start date
	 * @var string
	 */
	protected $startDateField = "startdate";
	/**
	 * Field containing the start time
	 * @var string
	 */
	protected $startTimeField = null;
	/**
	 * Field marking if the event is ready to publish
	 * @var string
	 */
	protected $readyToPublishField = "oktopublish";
	
	protected $eventTypeConst = Event::TypeWeekend;
	
	protected function newEvent()
	{
		return new Weekend();
	}
	
	/**
	 * Return some cumulative stats for events matching the current filters
	 * Stats returned depend on the specific factory
	 * @return array[]
	 */
	public function cumulativeStats()
	{
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select(array(
			"COUNT(1) AS count",
		));
		
		$this->applyFilters($query);
		$db->setQuery($query);
		return $db->loadAssoc();
	}
}