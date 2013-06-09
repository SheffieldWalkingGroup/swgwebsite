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
}