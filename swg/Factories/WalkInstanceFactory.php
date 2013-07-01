<?php
require_once(JPATH_BASE."/swg/Models/WalkInstance.php");
require_once("EventFactory.php");
class WalkInstanceFactory extends EventFactory 
{
	/**
	 * Load the route along with the walk instance. Default is false.
	 * @var bool
	 */
	public $includeRoute;
	
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
	
	public function __construct()
	{
		$this->includeRoute = false;
		
		parent::__construct();
	}
	
	protected function newEvent()
	{
		return new WalkInstance();
	}
	
	protected function modifyQuery(JDatabaseQuery &$query)
	{
		$query->where("NOT deleted");
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
	
}