<?php

class WalkProgramme extends SWGBaseModel
{
	private $id;
	/**
	 * The start date of the programme. For a normal programme, this will be the first day of the first month,
	 * not necessarily a day with an event planned.
	 * @var int
	 */
	private $startDate;
	/**
	 * The end date of the programme. For a normal programme, this will be the last day of the last month,
	 * not necessarily a day with an event planned.
	 * @var int
	 */
	private $endDate;
	/**
	 * Dates on which walks are planned for this programme
	 * @var int[]
	 */
	private $dates;
	private $description;
	private $special;
	
	/**
	* Array of variable => dbfieldname
	* Only includes variables that can be represented directly in the database
	* (i.e. no arrays or objects)
	* Does not include ID as this may interfere with database updates
	* @var array
	*/
	protected $dbmappings = array(
		'id'			=> "SequenceID",
		'startDate'		=> "StartDate",
		'endDate'		=> "EndDate",
		'description'	=> "Description",
		'special'		=> "special",
	);
	
	public function save($incrementVersion = true) {
		$db = JFactory::getDbo();
		
		// Commit everything as one transaction
		$db->transactionStart();
		$query = $db->getQuery(true);
		
		$this->toDatabase($query);
		
		// Update or insert?
		if (!isset($this->id))
		{
			$query->insert("walksprogramme");
		}
		else 
		{
			$query->where("SequenceID = ".(int)$this->id);
			$query->update("walksprogramme");
		}
		$db->setQuery($query);
		$db->query();
		
		if (!isset($this->id))
		{
			// Get the ID from the database
			$this->id = $db->insertid();
		}
		// TODO: Handle walkprogrammedates
		
		// TODO: Handle failure
		
		// Commit the transaction
		$db->transactionCommit();
	}
	
	public static function getCurrentProgrammeID()
	{
		return self::getProgrammeID(false);
	}
	
	public static function getNextProgrammeId()
	{
		return self::getProgrammeID(true);
	}
	
	private static function getProgrammeID($next)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select("val");
		$query->from("oneoffs");
		$query->where("name = '".($next ? "next" : "publ")."prog'");
		$query->limit(1);
		$db->setQuery($query);
		return $db->loadResult();
	}
}