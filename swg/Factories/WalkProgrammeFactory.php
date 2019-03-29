<?php

class WalkProgrammeFactory extends SWGFactory
{
    /**
     * @var DateTime Only include programmes starting on or after this date. Default: none
     */
    public $startDate;
    
    /**
     * @var DateTime Only include programmes ending on or after this date. Default: none
     */
    public $endDate;
    
    /**
     * @var boolean If true (default), include special programmes
     */
    public $includeSpecialProgrammes;
    
    /**
     * @var boolean If true (default), include normal programmes
     */
    public $includeNormalProgrammes;
    
    /**
     * @var int[] If set, only return programmes containing these walk instance IDs (will cast integer to array)
     */
    public $containingWalkInstanceIds;
	
	/**
	 * Return no more than this many events. Default is no limit (-1)
	 * @var int
	 */
	public $limit;
	/**
	 * Skip this many events before returning them. Default is none (0)
	 * @var int
	 */
	public $offset;
	
	/**
	 * Return events in descending date order. Default is ascending order (false)
	 * @var bool
	 */
	public $reverse;
    
    public function __construct()
	{
	}
	
	public function reset()
	{
		$this->startDate = null;
		$this->endDate = null;
		$this->includeSpecialProgrammes = true;
		$this->includeNormalProgrammes = true;
		$this->containingWalkInstanceIds = null;
		
		$this->limit = -1;
		$this->offset = 0;
		$this->reverse = false;
		
		parent::reset();
	}
	
	public function get()
	{
        $db = JFactory::getDBO();
		$query = $db->getQuery(true);
		
		$query->select("walksprogramme.*");
		$query->from("walksprogramme");
		
		if (isset($this->startDate)) {
            $query->where("StartDate >= '".$db->escape($this->startDate->format("Y-m-d")));
        }
        if (isset($this->endDate)) {
            $query->where("EndDate <= '".$db->escape($this->endDate->format("Y-m-d")));
        }
        
        if ($this->includeSpecialProgrammes === false) {
            $query->where("special == 0");
        }
        
        if ($this->includeNormalProgrammes === false) {
            $query->where("special == 1");
        }
        
        if (!empty($this->containingWalkInstanceIds)) {
            if (!is_array($this->containingWalkInstanceIds))
                $this->containingWalkInstanceIds = array($this->containingWalkInstanceIds);
            $query->join('INNER', 'walkprogrammewalklinks ON ProgrammeID = walksprogramme.SequenceID');
            $query->where('WalkProgrammeWalkID IN ('.implode(',', $this->containingWalkInstanceIds).')');
        }
        
        if ($this->reverse)
		{
			$order = array("StartDate DESC");
		}
		else
		{
			$order = array("StartDate ASC");
		}
        $query->order($order);
        $db->setQuery($query, $this->offset, $this->limit);
        $data = $db->loadAssocList();
        
        // Build an array of programmes
        $programmes = array();
        foreach ($data as $row) {
            $programme = new WalkProgramme();
            $programme->fromDatabase($row);
            $programmes[] = $programme;
        }
        
        return $programmes;

	}
}
