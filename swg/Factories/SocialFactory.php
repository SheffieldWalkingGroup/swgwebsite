<?php
require_once(JPATH_BASE."/swg/Models/Social.php");
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
class SocialFactory extends EventFactory 
{
	/**
	 * Include normal socials in the results
	 * @var bool
	 */
	public $getNormal = true;
	/**
	 * Include new member socials in the results
	 * @var bool
	 */
	public $getNewMember = true;
	
	/**
	 * The main table to read for this event
	 * @var string
	 */
	protected $table = "socialsdetails";
	
	protected $idField = "SequenceID";
	/**
	 * Field containing the start date
	 * @var string
	 */
	protected $startDateField = "on_date";
	/**
	 * Field containing the start time
	 * @var string
	 */
	protected $startTimeField = "starttime";
	/**
	 * Field marking if the event is ready to publish
	 * @var string
	 */
	protected $readyToPublishField = "readytopublish";
	
	protected function newEvent()
	{
		return new Social();
	}
	
	protected function modifyQuery(JDatabaseQuery &$query)
	{
		$showWhat = array();
		if ($this->getNormal)
			$showWhat[] = "shownormal";
		if ($this->getNewMember)
			$showWhat[] = "shownewmember";
		$query->where(implode(" OR ", $showWhat));
	}
}