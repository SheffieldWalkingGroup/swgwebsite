<?php
require_once(JPATH_BASE."/swg/Models/Social.php");
require_once("EventFactory.php");
class SocialFactory extends EventFactory 
{
	 // TODO: Add support for these
	public $getNormal = true;
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