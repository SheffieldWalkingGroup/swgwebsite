<?php
require_once("Event.php");
include_once("WalkMeetingPoint.php");
include_once("Leader.php");
/**
 * An instance of a walk, i.e. a walk with a date and a leader etc.
 * @author peter
 *
 */
class WalkInstance extends Event {
  protected $walk;
  protected $distanceGrade;
  protected $difficultyGrade;
  protected $miles;
  protected $location;
  protected $startGridRef;
  protected $startPlaceName;
  protected $endGridRef;
  protected $endPlaceName;
  
  protected $childFriendly;
  protected $dogFriendly;
  protected $speedy;
  protected $isLinear;
  protected $transportByCar;
  protected $transportPublic;
  
  protected $leader;
  protected $backmarker;
  protected $meetPoint;
  
  protected $dateAltered;
  
  protected $headCount;
  protected $mileometer;
  protected $reviewComments;
  protected $deleted;
  
  /**
   * Constructs a walk object from an array of database fields
   * @param array $dbArr Associative array from the walkprogramewalks table
   */
  public function __construct($dbArr)
  {
    $this->id = $dbArr['SequenceID'];
    $this->name = $dbArr['name'];
    $this->start = strtotime($dbArr['WalkDate']." ".$dbArr['meettime']);
    $this->description = $dbArr['routedescription'];
    $this->okToPublish = $dbArr['readytopublish'];
    
    $this->walk = $dbArr['walklibraryid']; // TODO: Load it?
    $this->distanceGrade = $dbArr['distancegrade'];
    $this->difficultyGrade = $dbArr['difficultygrade'];
    $this->miles = $dbArr['miles'];
    $this->location = $dbArr['location'];
    $this->startGridRef = $dbArr['startgridref'];
    $this->startPlaceName = $dbArr['startplacename'];
    $this->endGridRef = $dbArr['endgridref'];
    $this->endPlaceName = $dbArr['endplacename'];
    
    $this->childFriendly = $dbArr['childfriendly'];
    $this->dogFriendly = $dbArr['dogfriendly'];
    $this->speedy = $dbArr['speedy'];
    $this->isLinear = $dbArr['islinear'];
//     $this->transportByCar = $dbArr['transport'];
//     $this->transportPublic = $dbArr[''];
    
    $this->meetPoint = new WalkMeetingPoint($dbArr['meetplace'], $this->start, $dbArr['meetplacetime']);
    $this->leader = Leader::getLeader($dbArr['leaderid']);
    $this->backmarker = Leader::getLeader($dbArr['backmarkerid']);
    if (!empty($dbArr['leadername']))
      $this->leader->setDisplayName($dbArr['leadername']);
    if (!empty($dbArr['backmarkername']))
      $this->backmarker->setDisplayName($dbArr['backmarkername']);
//     $this->dateAltered = $dbArr[''];
    
//     $this->headCount = $dbArr[''];
//     $this->mileometer = $dbArr[''];
//     $this->reviewComments = $dbArr[''];
//     $this->deleted = $dbArr[''];
  }
  
  public function __get($name)
  {
    return $this->$name; // TODO: What params should be exposed?
  }
  
  /**
   * Gets a limited number of events, starting today and going forwards
   * Partly for backwards-compatibility, but also to improve readability
   * @param int $numEvents Maximum number of events to get
   */
  public static function getNext($numEvents) {
    return self::get(self::DateToday, self::DateEnd, $numEvents);
  }
  
  /**
   * Gets the next few scheduled walks
   * @param int $iNumToGet Maximum number of events to fetch. Default is no limit.
   * @return array Array of WalkInstances
   */
  public static function get($startDate=self::DateToday, $endDate=self::DateEnd, $numToGet = -1) {
    // Build a query to get future walks that haven't been deleted.
    // We do want cancelled walks - users should be notified about these.
    $db = JFactory::getDBO();
    $query = $db->getQuery(true);
    $query->select("*");
    $query->from("walkprogrammewalks");
    
    // TODO: This is a stored proc currently - can we use this?
    $query->where(array(
        "WalkDate >= '".self::timeToDate($startDate)."'",
        "WalkDate <= '".self::timeToDate($endDate)."'",
        "NOT deleted",
        "readytopublish",
    ));
    $query->order(array("WalkDate ASC", "meettime ASC"));
    $db->setQuery($query);
    $walkData = $db->loadAssocList();
    
    // Build an array of WalkInstances
    // TODO: Set actual SQL limit
    $walks = array();
    while (count($walkData) > 0 && count($walks) != $numToGet) {
      $walk = new WalkInstance(array_shift($walkData));
      $walks[] = $walk;
    }
    
    return $walks;
  }
  
  public function isCancelled() {
    return false;
  }
  
  public function getEventType() {
    return "walk";
  }
  
  public function getWalkDay() {
    if (date("N",$this->start) < 6)
      return "Weekday";
    else
      return date("l",$this->start);
  }
  
  /**
   * Estimate the finish time as:
   *   1 hour after the start time (unless the meet point is the walk start)
   *   + 0.5 hours per mile
   *   TODO: Rounded up to the nearest hour
   */
  public function estimateFinishTime() {
    $finish = $this->start;
    if (!$this->meetPoint->isAtWalkStart())
      $finish += 3600; // Add 1 hour travelling time
    $hoursWalking = 0.5*$this->miles;
    return ($finish + 3600*$hoursWalking);
  }
  
  public static function getSingle($id) {
    $db = JFactory::getDBO();
    $query = $db->getQuery(true);
    $query->select("*");
    $query->from("walkprogrammewalks");
  
    $query->where(array("SequenceID = ".intval($id)));
    $db->setQuery($query);
    $res = $db->query();
    if ($db->getNumRows($res) == 1)
      return new WalkInstance($db->loadAssoc());
    else
      return null;
  
  }
}