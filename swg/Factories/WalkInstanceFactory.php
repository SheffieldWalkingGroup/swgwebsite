<?php
require_once(JPATH_BASE."/swg/Models/WalkInstance.php");
require_once("EventFactory.php");

/**
 * Factory for returning walk instances
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
class WalkInstanceFactory extends EventFactory 
{
	/**
	 * Load the route along with the walk instance. Default is false.
	 * @var bool
	 */
	public $includeRoute;
	
	/**
	 * Filter to walks in this programme. Default is unfiltered
	 * @var int|null Programme ID, or null for any/no programme
	 */
	public $walkProgramme;
	
	/**
	 * Optional filter by walk leader. Leader object or ID
	 * @var Leader|int
	 */
	public $leader = null;
	
	/**
	 * Optional filter by start time - must be before this.
	 * @var int Timestamp in seconds from midnight
	 */
	public $startTimeMax = null;
	
	/**
	 * The main table to read for this event
	 * @var string
	 */
	protected $table = "walkprogrammewalks";
	
	protected $idField = "SequenceID";
	/**
	 * Field containing the start date
	 * @var string
	 */
	protected $startDateField = "WalkDate";
	/**
	 * Field containing the start time
	 * @var string
	 */
	protected $startTimeField = "meettime";
	/**
	 * Field marking if the event is ready to publish
	 * @var string
	 */
	protected $readyToPublishField = "readytopublish";
	
	protected $eventTypeConst = Event::TypeWalk;
	
	public function __construct()
	{
		parent::__construct();
	}
	
	public function reset()
	{
		$this->includeRoute = false;
		$this->walkProgramme = null;
		$this->leader = null;
		$this->startTimeMax = null;
		
		parent::reset();
	}
	
	protected function newEvent()
	{
		return new WalkInstance();
	}
	
	protected function modifyQuery(JDatabaseQuery &$query)
	{
		$query->where("NOT deleted");
		
		if (isset($this->leader))
		{
			if ($this->leader instanceof Leader)
				$query->where("leaderid = ".$this->leader->id);
			else if (is_int($this->leader))
				$query->where("leaderid = ".$this->leader);
			else if (ctype_digit($this->leader))
				$query->where("leaderid = ".(int)$this->leader);
		}
		
		if (isset($this->walkProgramme))
		{
			if ($this->walkProgramme instanceof WalkProgramme)
			{
				$wpID = $this->walkProgramme->id;
			}
			else
			{
			    $wpID = (int)$this->walkProgramme;
			}
			
			$query->join('INNER', 'walkprogrammewalklinks ON WalkProgrammeWalkID = walkprogrammewalks.SequenceID');
			$query->where("walkprogrammewalklinks.ProgrammeID = ".$wpID);
		}
		
		if (isset($this->startTimeMax))
		{
			$query->where("meettime <= '".strftime("%H:%M", $this->startTimeMax)."'");
		}
	}
	
	/**
	 * Creates a new walk instance from a walk, pre-filling fields as
	 * The following fields are used:
	 *   * walkid
	 *   * name
	 *   * description
	 *   * location
	 *   * (start|end)(PlaceName|GridRef)
	 *   * isLinear
	 *   * miles
	 *   * (difficulty|distance)Grade
	 *   * childFriendly
	 * Other values are left blank, 
	 * but dogFriendly will be true if the leader AND walk are dog friendly (false otherwise)
	 * @param Walk $walk The walk we're creating a WalkInstance for
	 * @return WalkInstance the generated walk instance
	 */
	public function createFromWalk(Walk $walk) {
		$wi = new WalkInstance();
		$wi->walkid = $walk->id;
		
		// Most properties have the same names. Copy them over.
		// Because we don't allow full read-write access to the walk's properties, 
		// we get the list of usable properties from dbmappings.
		foreach ($walk->dbmappings as $key => $v)
		{
			$value = $walk->$key;
			if (property_exists($wi,$key))
			{
				$wi->$key = $value;
			}
		}
		
		return $wi;
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
			"SUM(miles) AS sum_miles",
			"AVG(miles) AS mean_miles",
			"SUM(
				CASE 
					WHEN distance IS NOT NULL THEN distance
					ELSE (miles*".UnitConvert::getUnit(UnitConvert::Mile, 'factor').")
				END
			) AS sum_distance",
			"AVG(
				CASE 
					WHEN distance IS NOT NULL THEN distance
					ELSE (miles*".UnitConvert::getUnit(UnitConvert::Mile,'factor').")
				END
			) AS mean_distance",
		));
		
		$this->applyFilters($query);
		$db->setQuery($query);
		return $db->loadAssoc();
	}
	
	
}