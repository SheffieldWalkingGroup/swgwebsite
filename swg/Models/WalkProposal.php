<?php

require_once("SWGBaseModel.php");

class WalkProposal extends SWGBaseModel {
    
    const NOT_AVAILABLE = 0;
    
    const AVAILABLE = 1;
    
    const PREFERRED_DATE = 2;
    
    /**
     * Walk proposal ID
     * @var int 
     */
    private $id;
    
    /**
     * @var Leader
     */
    private $leader;
    
    /**
     * @var Leader
     */
    private $backmarker;
    
    /**
     * @var WalkProgramme
     */
    private $programme;
    
    /**
     * @var Walk
     */
    private $walk;
    
    /**
     * Proposed walk timing and transport information
     * @var string
     */
    private $timingAndTransport;
    
    /**
     * General comments from the leader
     * @var string
     */
    private $comments;
    
    /**
     * The walk instance once the walk has been added to the programme
     * @var WalkInstance
     */
    private $walkInstance;
    
    /**
     * The last date the leader updated their proposal
     * @var DateTime // TODO: Should be DateTimeImmutable but we're stuck on PHP 5.4
     * @todo Maybe also record when the vice chair last checked it
     */
    private $lastUpdated;
    
    /**
     * Array of dates available in this programme
     * 'yyyy-mm-dd' => [availability]
     *
     * Note that this is never directly set by untrusted sources: the array keys are generated and we copy matching values when setting it
     * So it should be safe to write to DB/display
     * @var int[]
     */
    private $dates;
    
    protected $dbmappings = array(
        'id' => 'proposal_id',
        'timingAndTransport' => 'timing_transport',
        'comments' => 'comments'
    );
    
    public function fromDatabase(array $dbArr)
    {
        parent::fromDatabase($dbArr);
        
        $this->leader = Leader::getLeader($dbArr['leader_id']);
        if (!empty($dbArr['backmarker_id']))
            $this->backmarker = Leader::getLeader($dbArr['backmarker_id']);
        
        $this->programme = WalkProgramme::get($dbArr['programme_id']);
        $this->walk = Walk::getSingle($dbArr['walk_id']);
        
        if (!empty($dbArr['walkinstance_id'])) {
            $wiFactory = new WalkInstanceFactory();
            $this->walkInstance = $wiFactory->getSingle($dbArr['walkinstance_id']);
        }
        
        // Load the dates
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $query->select("walk_date, availability");
        $query->from("walkproposaldate");
        $query->where("proposal_id = ".$this->id);
        $db->setQuery($query);
        $data = $db->loadAssocList('walk_date', 'availability');
        
        $this->populateDatesFromArray($data);
    }
    
    public function toDatabase(JDatabaseQuery &$query)
    {
        $query->set("leader_id = ".$this->leader->id);
        if (isset($this->backmarker))
            $query->set("backmarker_id = ".$this->backmarker->id);
        else
            $query->set("backmarker_id = NULL");
        $query->set("programme_id = ".$this->programme->id);
        $query->set("walk_id = ".$this->walk->id);
        $query->set("timing_transport = '".$query->escape($this->timingAndTransport)."'");
        $query->set("comments = '".$query->escape($this->comments)."'");
        if (isset($this->walkInstance))
            $query->set("walkinstance_id = ".$this->walkInstance->id);
        else
            $query->set("walkinstance_id = NULL");
    }
    
    public function save()
    {
        if (!isset($this->leader))
            throw new BadMethodCallException("No leader specified");
        if (!isset($this->programme))
            throw new BadMethodCallException("No programme selected");
        if (!isset($this->walk))
            throw new BadMethodCallException("No walk selected");
        
        $db =& JFactory::getDBO();
		// Commit proposal & dates as one transaction
		$db->transactionStart();
		
		$query = $db->getQuery(true);
		$this->toDatabase($query);
		
		if (!isset($this->id))
            $query->insert('walkproposal');
        else {
            $query->where('proposal_id = '.(int)$this->id);
            $query->update('walkproposal');
        }
        
        $db->setQuery($query);
        $db->query();
        
        if (!isset($this->id))
		{
			// Get the proposal ID from the database
			$this->id = $db->insertid();
		}
		
		// Delete any existing dates for this proposal
		$query = $db->getQuery(true);
		$query->delete("walkproposaldate")->where("proposal_id = ".$this->id);
		$db->setQuery($query);
		$db->query();
		
		// Now store the dates
		foreach ($this->dates as $date => $availability)
		{
            $query = $db->getQuery(true);
            $query->insert('walkproposaldate');
            $query->set('proposal_id = '.(int)$this->id);
            $query->set("walk_date = '".$date."'");
            $query->set("availability = ".(int)$availability);
            $db->setQuery($query);
            $db->query();
		}
		
		$db->transactionCommit();
    }
    
    /**
     * Populate the leader's availability from an array
     * 
     * Array must be in the format:
     * yyyy-mm-dd => (availability)
     *
     * Where availability is one of the constants in this class
     *
     * @param int[] dates Availability array, as described in method doc
     */
    public function populateDatesFromArray(array $dates)
    {
        $date = DateTime::createFromFormat('U', $this->programme->startDate);
        $endDate = DateTime::createFromFormat('U', $this->programme->endDate);
        $this->dates = array();
        
        do {
            $dateString = $date->format('Y-m-d'); 
            $availability = (int)$dates[$dateString]; // TODO: Should default to 0 if not set
            $this->dates[$dateString] = $availability;
            $date->add(new DateInterval('P1D'));
        } while ($date <= $endDate);
    }
    
    public function __set($name, $value)
    {
        switch ($name) {
            case 'id':
                $this->id = (int)$value;
                break;
            case 'leader':
            case 'leaderId':
                if ($value instanceof Leader) 
                    $this->leader = $value;
                else
                    $this->leader = Leader::getLeader($value);
                break;
            case 'backmarker':
            case 'backmarkerId':
                if ($value instanceof Leader) 
                    $this->backmarker = $value;
                else
                    $this->backmarker = Leader::getLeader($value);
                break;
            case 'programme':
            case 'programmeId':
                if ($value instanceof WalkProgramme)
                    $this->programme = $value;
                else
                    $this->programme = WalkProgramme::get($value);
                break;
            case 'walk':
            case 'walkId':
                if ($value instanceof Walk)
                    $this->walk = $value;
                else 
                    $this->walk = Walk::getSingle($value);
                break;
            case 'walkInstance':
            case 'walkInstanceId':
                if ($value instanceof WalkInstance)
                    $this->walkInstance = $value;
                else {
                    $wiFactory = new WalkInstanceFactory();
                    $this->walkInstance = $wiFactory->getSingle($value);
                }
                break;
            case 'comments':
            case 'timingAndTransport':
                $this->$name = $value;
                break;
        }
    }
    
    public function __get($name)
    {
        return $this->$name;
    }
    
    public function updateTimestamp()
    {
    // TODO: Should be DateTimeImmutable but we're stuck on PHP 5.4
        $this->lastUpdated = new DateTime();
    }
    
    /**
     * Get a string summary of the dates available in this proposal
     * 
     * Doesn't mention preferred dates (yet)
     *
     * * Display up to 3 dates in full
     * * Otherwise just say how many dates were selected
     *
     * @return string
     */
    public function getDateSummary()
    {
        $dates = $this->getAvailableDates();
        $output = "";
        
        if (count($dates) <= 3) {
            for ($i=0; $i<count($dates); $i++) {
                $output .= $dates[$i]->format("l d/m");
                if ($i+2 < count($dates))
                    $output .= ', ';
                elseif ($i+1 < count($dates))
                    $output .= ' or ';
            }
        } else {
            $output = sprintf('%d possible dates');
        }
        
        return $output;
    }
    
    /**
     * Get an array of all dates available for this proposal
     */
    private function getAvailableDates()
    {
        $available = array();
        
        foreach ($this->dates as $date => $availability) {
            if ($availability > 0) {
                $available[] = new DateTime($date);
            }
        }
        
        return $available;
    }
    
    public function getAvailabilityForDate(DateTime $date)
    {
        return $this->dates[$date->format('Y-m-d')];
    }
    
    
}
