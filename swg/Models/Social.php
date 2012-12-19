<?php
/**
 * A social
 */
require_once("Event.php");
class Social extends Event {
  
  protected $bookingsInfo;
  protected $clipartFilename;
  
  protected $start;
  protected $end;
  
  // Start and end times for the new members' part (if applicable)
  protected $showNormal;
  protected $showNewMember;
  protected $newMemberStart;
  protected $newMemberEnd;
  
  protected $location;
  protected $postcode;
  protected $latLng;
  
  protected $cost;
  
  public $dbmappings = array(
    'name'		=> 'title',
    'description'	=> 'fulldescription',
    'okToPublish'	=> 'readytopublish',
    'bookingsInfo'	=> 'bookingsinfo',
    'clipartFilename'	=> 'clipartfilename',
    
    'location'		=> 'location',
    'postcode'		=> 'postcode',
    'cost'		=> 'cost',
    
    'showNormal'	=> 'shownormal',
    'showNewMember'	=> 'shownewmember',
    'newMemberStart'	=> 'newmemberstart',
  );
  
  public function fromDatabase(array $dbArr)
  {
    $this->id = $dbArr['SequenceID'];
    
    parent::fromDatabase($dbArr);
    
    $this->start = strtotime($dbArr['on_date']." ".$dbArr['starttime']);
    
    // TODO: Shouldn't make up an end time here - do that in the UI if/where needed
    if (!empty($dbArr['endtime']))
      $this->end = strtotime($dbArr['on_date']." ".$dbArr['endtime']);
    else
      $this->end = $this->start + 120*60;
    
    // If end is the next day...
    if ($this->end < $this->start)
    {
      $this->end += 86400;
    }
      
    if (!empty($dbArr['latitude']) && !empty($dbArr['longitude']))
      $this->latLng = new LatLng($dbArr['latitude'], $dbArr['longitude']);
    
    $this->alterations->setVersion($dbArr['version']);
    $this->alterations->setLastModified(strtotime($dbArr['lastmodified']));
  }
  
  public function toDatabase(JDatabaseQuery &$query)
  {
    parent::toDatabase($query);
    
    $query->set('on_date', strftime("%Y-%m-%d",$this->start));
    $query->set('starttime', strftime("%H-%M",$this->start));
    
    if (!empty($this->end))
      $query->set('endtime', strftime("%H-%M", $this->end));
    
    $query->set('version', $this->alterations->version);
    $query->set('lastmodified', $this->alterations->lastModified);
  }
  
  public function __get($name)
  {
    return $this->$name; // TODO: What params should be exposed?
  }
  
  /**
   * Gets the next few scheduled socials
   * @param int $iNumToGet Maximum number of events to fetch. Default is no limit.
   * @return array Array of Socials
   */
  public static function get($startDate=self::DateToday, $endDate=self::DateEnd, $numToGet = -1, $getNormal = true, $getNewMember = true) {
    
    // Build a query to get future socials
    $db = JFactory::getDBO();
    $query = $db->getQuery(true);
    $query->select("*");
    $query->from("socialsdetails");
    // TODO: This is a stored proc currently - can we use this?
    $where = array(
        "on_date >= '".self::timeToDate($startDate)."'",
        "on_date <= '".self::timeToDate($endDate)."'",
        "readytopublish",
    );
    // Hide normal/new member events if we're not interested
    if (!$getNormal || !$getNewMember)
    {
      if ($getNormal)
        $where[] = "shownormal";
      else if ($getNewMember)
        $where[] = "shownewmember";
      else
        $where[] = "false";
    }
    $query->where($where);
    $query->order(array("on_date ASC", "title ASC"));
    $db->setQuery($query);
    $socialData = $db->loadAssocList();
      
    // Build an array of Socials
    // TODO: Set actual SQL limit
    $socials = array();
    while (count($socialData) > 0 && count($socials) != $numToGet) {
      $social = new Social();
      $social->fromDatabase(array_shift($socialData));
      $socials[] = $social;
    }
  
    return $socials;
  }
  
  /**
   * Gets a limited number of events, starting today and going forwards
   * Partly for backwards-compatibility, but also to improve readability
   * @param int $numEvents Maximum number of events to get
   */
  public static function getNext($numEvents, $getNormal = true, $getNewMember = true) {
    return self::get(self::DateToday, self::DateEnd, $numEvents, $getNormal, $getNewMember);
  }
  
  public static function getSingle($id) {
    $db = JFactory::getDBO();
    $query = $db->getQuery(true);
    $query->select("*");
    $query->from("socialsdetails");
    
    $query->where(array("SequenceID = ".intval($id)));
    $db->setQuery($query);
    $res = $db->query();
    if ($db->getNumRows($res) == 1)
    {
      $soc = new Social();
      $soc->fromDatabase($db->loadAssoc());
      return $soc;
    }
    else
      return null;
    
  }
  
  public function hasMap() {
    return (!empty($this->latLng) || !empty($this->postcode));
  }
  
}