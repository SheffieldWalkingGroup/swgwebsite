<?php

class WalkProposalFactory extends SWGFactory
{
    /**
     * @var WalkProgramme|int First programme to get proposals for. Default is the next programme
     * Searches in order of programme ID, not dates
     */
    public $startProgramme;
    
    /**
     * @var WalkProgramme|int Last programme to get proposals for. Default is the same as $startProgramme
     */
    public $endProgramme;
    
    /**
     * @var boolean If true (default), include proposals that have already been added to the programme
     */
    public $includeProposalsInProgramme;
    
    /**
     * @var Leader|int Set this to only get proposals made by a single leader
     */
    public $leader;
    
    public function __construct()
	{
	}
	
	public function reset()
	{
		$this->startProgramme = null;
		$this->endProgramme = null;
		$this->includeProposalsInProgramme = true;
		
		parent::reset();
	}
	
	public function get()
	{
        $db = JFactory::getDBO();
		$query = $db->getQuery(true);
		
		$query->select("walkproposal.*");
		$query->from("walkproposal");
		
		if (isset($this->startProgramme)) {
            $startProgramme = $this->startProgramme;
        } else {
            $startPrograme = WalkProgramme::getNextProgrammeID();
        }
        
		if ($startProgramme instanceof WalkProgramme) {
            $startProgramme = $startPrograme->id;
        } elseif (!is_numeric($startProgramme)) {
            throw new InvalidArgumentException("Start programme must be a WalkProgramme or a programme ID");
        }
        $startPrograme = (int)$startProgramme;
        
        if (isset($this->endProgramme)) {
            $endProgramme = $this->endProgramme;
        } else {
            $endProgramme = $startPrograme;
        }
        
		if ($endProgramme instanceof WalkProgramme) {
            $endProgramme = $endProgramme->id;
        } elseif (!is_numeric($endProgramme)) {
            throw new InvalidArgumentException("End programme must be a WalkProgramme or a programme ID");
        }
        $endProgramme = (int)$endProgramme;
        
        $query->where("programme_id >= $startProgramme AND programme_id <= $endProgramme");
        
        if ($this->includeProposalsInProgramme === false) {
            $query->where("walkinstance_id IS NULL");
        }
        
        if (isset($this->leader))
        {
            if ($this->leader instanceof Leader) {
                $query->where("leader_id = ".$this->leader->id);
            } elseif (is_numeric($this->leader)) {
                $query->where("leader_id = ".(int)($this->leader));
            }
        }
        
        $db->setQuery($query);
        $data = $db->loadAssocList();
        
        // Build an array of proposals
        $proposals = array();
        foreach ($data as $row) {
            $proposal = new WalkProposal();
            $proposal->fromDatabase($row);
            $proposals[] = $proposal;
        }
        
        return $proposals;

	}
}
