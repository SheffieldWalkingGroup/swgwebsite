<?php
require_once("SWGBaseModel.php");
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
	 * Array of leader availability.
	 * Stored in the format:
	 * $availability = array(date => array(leaderid => boolean))
	 * @var array
	 */
	private $availability;
	
	/**
	 * Leaders we have loaded availability for.
	 * keys and values are leader IDs.
	 * allAvailabilityLoaded takes precedence: this array is not updated if all data has been loaded.
	 * @var int[]
	 */
	private $availabilityLoaded;
	
	/**
	 * True if availability has been loaded for all leaders
	 * @var boolean
	 */
	private $allAvailabilityLoaded;
	
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
	
	public function __get($name)
	{
		if ($name == "dates")
		{
			return $this->loadDates();
		}
		else {
		    return $this->$name;
		}
	}
	
	public function __set($name, $value)
	{
		switch ($name)
		{
			case "id":
				$this->$name = (int)$value;
				break;
			case "startDate":
			case "endDate":
				if (is_numeric($value))
					$this->$name = (int)$value;
				else
					$this->$name = strtotime($value);
				break;
			case "dates":
				// TODO
				echo ("WalkProgramme.__set('dates') not implemented yet");
				break;
			case "description":
				$this->$name = $value;
				break;
			case "special":
				$this->$name = (bool)$value;
				break;
		}
	}
	
	private function loadDates()
	{
		if (!isset($this->dates))
		{
			// Load the dates for this programme
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select("WalkDate"); // We get WalkLeaderID here for consistency, even though we know it
			$query->from("walkprogrammedates");
			$query->where("ProgrammeID = ".(int)$this->id);
			$db->setQuery($query);
			
			foreach ($db->loadColumn(0) as $date)
			{
				$this->dates[] = strtotime($date);
			}
		}
		return $this->dates;
	}
	
	public function getLeaderAvailability($leaderOrID)
	{
		if ($leaderOrID instanceof Leader)
			$leaderid = $leaderOrID->id;
		else
		    $leaderid = (int)$leaderOrID;
		
		$this->loadAvailability($leaderid);
		$this->loadDates();
		
		$leaderAvailability = array();
		// $date = date in unix time; $leaders = array of leader ids to their availability
		foreach ($this->dates as $date)
		{
			$leaders = $this->availability[$date];
			$leaderAvailability[$date] = (!empty($leaders[$leaderid])); // Leader is available if their availability is set and is not false-y
		}
		return $leaderAvailability;
	}
	
	/**
	 * Sets a leader's availability. Availability should be passed as an array with the same format as returned by getLeaderAvailability.
	 * Only dates that exist in this programme will be set.
	 * Any dates not in the availability array will be set to not available.
	 * @param int $leaderid
	 * @param array $availability
	 */
	public function setLeaderAvailability($leaderid, array $availability)
	{
		$this->loadDates();
		$oldAvailability = $this->getLeaderAvailability($leaderid);
		$db = JFactory::getDbo();
		
		// Delete any existing availability for this leader in this programme
		$query = $db->getQuery(true);
		$query->delete("walkleaderavailability");
		$query->where("ProgrammeID = ".(int)$this->id);
		$query->where("WalkLeaderID = ".(int)$leaderid);
		$db->setQuery($query);
		$db->query();
		
		foreach ($availability as $date => $available)
		{
			// Check if this date is in the programme
			if (in_array($date, $this->dates))
			{
				$query = $db->getQuery(true);
				$query->insert("walkleaderavailability");
				$query->set("ProgrammeID = ".(int)$this->id);
				$query->set("WalkLeaderID = ".(int)$leaderid);
				$query->set("WalkDate = '".$db->escape(strftime("%Y-%m-%d", $date))."'");
				$query->set("Availability = ".($available ? 1 : 0));
				
				$db->setQuery($query);
				$db->query();
			}
		}
	}
	
	/**
	 * Loads availability for one or all walk leaders, unless it's already been loaded
	 * @param int $leaderid Leader ID to load for, or null to load all leaders
	 */
	private function loadAvailability($leaderid = null)
	{
		$loading = false;
		if (isset($leaderid) && !$this->allAvailabilityLoaded && !isset($this->availabilityLoaded[$leaderid]))
		{
			// We need to load a specific leader
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select("WalkLeaderID, WalkDate, Availability"); // We get WalkLeaderID here for consistency, even though we know it
			$query->from("walkleaderavailability");
			$query->where("ProgrammeID = ".(int)$this->id." AND WalkLeaderID = ".(int)$leaderid);
			$db->setQuery($query);
			$loading = true;
		}
		else if (!isset($leaderid) && !$this->allAvailabilityLoaded)
		{
			// We need to load all leaders
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select("WalkLeaderID, WalkDate, Availability");
			$query->from("walkleaderavailability");
			$db->setQuery($query);
			$loading = true;
		}
		
		if ($loading)
		{
			// We've just got some data, so parse it into out availability array
			$data = $db->loadAssocList();
			foreach ($data as $row)
			{
				$date = strtotime($row['WalkDate']);
				$walkLeaderID = $row['WalkLeaderID'];
				$availability = ($row['Availability'] == 1 ? true : false);
				
				if (!isset($this->availability[$date]))
					$this->availability[$date] = array();
				$this->availability[$date][$walkLeaderID] = $availability;
			}
		}
	}
	
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
	
	public static function get($programmeID)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select("*");
		$query->from("walksprogramme");
		$query->where("SequenceID = ".(int)$programmeID);
		$db->setQuery($query);
		$db->query();
		if ($db->getNumRows())
		{
			$programme = new WalkProgramme();
			$programme->fromDatabase($db->loadAssoc());
			return $programme;
		}
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