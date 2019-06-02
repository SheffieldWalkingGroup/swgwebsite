<?php
require_once("SWGBaseModel.php");
class WalkProgramme extends SWGBaseModel
{
	protected $id;
	/**
	 * The start date of the programme. For a normal programme, this will be the first day of the first month,
	 * not necessarily a day with an event planned.
	 * @var DateTime
	 */
	protected $startDate;
	/**
	 * The end date of the programme. For a normal programme, this will be the last day of the last month,
	 * not necessarily a day with an event planned.
	 * @var DateTime
	 */
	protected $endDate;
	/**
	 * Dates on which walks are planned for this programme
	 * @var int[]
	 */
	protected $dates;
	protected $title;
	protected $special;
	
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
		'title'	        => "Description",
		'special'		=> "special",
	);
	
	/**
	 * Caches the next programme ID
	 */
	private static $nextProgrammeId = null;
	
	/**
	 * Caches the current programme ID
	 */
	private static $currentProgrammeId = null;
	
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
				if ($value instanceof DateTime)
					$this->$name = clone($value);
				else
					$this->$name = new DateTime($value);
				break;
			case "dates":
				// TODO
				echo ("WalkProgramme.__set('dates') not implemented yet");
				break;
			case "title":
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
		
		// Update or insert?
		if (empty($this->id))
		{
			$query->insert("walksprogramme");
		}
		else 
		{
			$query->where("SequenceID = ".(int)$this->id);
			$query->update("walksprogramme");
		}
		$this->toDatabase($query);
		$db->setQuery($query);
		$db->execute();
		
		if (empty($this->id))
		{
			// Get the ID from the database
			$this->id = $db->insertid();
		}
		// TODO: Handle walkprogrammedates
		$this->updateWalkProgrammeDates();
		
		// TODO: Handle failure
		
		// Commit the transaction
		$db->transactionCommit();
	}
	
	/**
	 * Update the walk programme dates
	 * Runs after saving to the database (needs an ID)
	 */
	private function updateWalkProgrammeDates() {
        $db = JFactory::getDbo();
	
		// Delete old dates
		$query = $db->getQuery(true);
		$query->delete("walkprogrammedates");
		$query->where("ProgrammeID = ".(int)$this->id);
		$db->setQuery($query);
		$db->execute();
		
		// Populate new dates
		$date = clone($this->startDate);
		$endDate = $this->endDate;
		do {
			$query = $db->getQuery(true);
			$query->insert("walkprogrammedates");
			$query->set("ProgrammeID = ".(int)$this->id);
			$query->set("WalkDate = '".$date->format('Y-m-d')."'");
			$db->setQuery($query);
			$db->execute();
			
			$date->add(new DateInterval('P1D'));
		} while ($date <= $endDate);
	}
	
	public function toDatabase(JDatabaseQuery &$query)
    {
        foreach ($this->dbmappings as $var => $dbField)
		{
			if (isset($this->$var)) {
                if ($var == 'startDate' || $var == 'endDate') {
                    $query->set($dbField." = '".$query->escape($this->$var->format('Y-m-d'))."'");
		} elseif ($var == 'id' && empty($this->id)) {
			continue;
                } else {
                    $query->set($dbField." = '".$query->escape($this->$var)."'");
                }
            }
		}
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
        if ($next)
            $cacheVar = 'nextProgrammeId';
        else
            $cacheVar = 'currentProgrammeId';
        
        if (!isset(self::$$cacheVar)) {
            $db = JFactory::getDbo();
            $query = $db->getQuery(true);
            $query->select("val");
            $query->from("oneoffs");
            $query->where("name = '".($next ? "next" : "publ")."prog'");
            $query->limit(1);
            $db->setQuery($query);
            self::$$cacheVar = $db->loadResult();
        }
        
        return self::$$cacheVar;
	}
	
	public static function setCurrentProgrammeID($programmeId)
	{
        self::setProgrammeID(false, $programmeId);
	}
	
	public static function setNextProgrammeID($programmeId)
	{
        self::setProgrammeID(true, $programmeId);
	}
	
	private static function setProgrammeID($next, $programmeId)
	{
        $programmeId = (int)$programmeId;
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->update('oneoffs');
        $query->set("val = '$programmeId'");
        $query->where("name = '".($next ? 'nextprog' : 'publprog')."'");
        $db->setQuery($query);
        $db->execute();
        
        self::$nextProgrammeId = null;
        self::$currentProgrammeId = null;
	}
	
	public static function getProgrammeDates($includeSpecial = false, $start = null, $end = null)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select("WalkDate");
		$query->from("walksprogramme");
		$query->innerJoin("walkprogrammedates ON ProgrammeID = SequenceID");
		if (!$includeSpecial)
			$query->where("special = 0");
		if (!empty($start))
			$query->where("WalkDate >= '".Event::timeToDate($start)."'");
		if (!empty($end))
			$query->where("WalkDate <= '".Event::timeToDate($end)."'");
		$db->setQuery($query);
		$db->query();
		
		return $db->loadColumn(0);
	}
	
	public function valuesToForm()
	{
		$values = array(
            'id'            => $this->id,
            'startDate'     => $this->startDate->format('Y-m-d'),
            'endDate'       => $this->endDate->format('Y-m-d'),
            'title'         => $this->title,
            'special'       => $this->special
        );
        
        return $values;
    }
    
    /**
     * Add a WalkInstance to this programme, if it isn't already there
     *
     * @param WalkInstance $w Walk to add
     *
     * @return void
     */
    public function addWalk(WalkInstance $w)
    {
        // Joomla doesn't have an SQL REPLACE statement, so we have to do it ourselves :-(
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select("COUNT(1)");
        $query->from("walkprogrammewalklinks");
        $query->where("ProgrammeID = ".$this->id." AND WalkProgrammeWalkID = ".$w->id);
        $db->setQuery($query);
        $r = $db->loadResult();
        $db->execute();
        if ($r === '0') {
            // Programme doesn't contain walk, and no errors, so add it
            $query = $db->getQuery(true);
            $query->insert("walkprogrammewalklinks");
            $query->set("ProgrammeID = ".$this->id);
            $query->set("WalkProgrammeWalkID = ".$w->id);
            $db->setQuery($query);
            $db->execute();
        }
    }
    
    /**
     * Remove a WalkInstance from this programme, unless it isn't there
     *
     * @param WalkInstance $w Walk to add
     *
     * @return void
     */
    public function removeWalk(WalkInstance $w)
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->delete("walkprogrammewalklinks");
        $query->where("ProgrammeID = ".$this->id." AND WalkProgrammeWalkID = ".$w->id);
        $db->setQuery($query);
        $db->execute();
    }
}
