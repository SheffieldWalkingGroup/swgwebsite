<?php
require_once("Event.php");
/**
 * An instance of a walk, i.e. a walk with a date and a leader etc.
 * @author peter
 *
 */
class WalkInstance extends Event {
  private $walk;
  private $distanceGrade;
  private $difficultyGrade;
  private $miles;
  private $location;
  private $startGridRef;
  private $startPlaceName;
  private $endGridRef;
  private $endPlaceName;
  
  private $childFriendly;
  private $dogFriendly;
  private $speedy;
  private $isLinear;
  private $transportByCar;
  private $transportPublic;
  
  private $leaderID;
  private $backmarkerID;
  private $meetPlace;
  private $meetTime;
  private $dateAltered;
  
  private $headCount;
  private $mileometer;
  private $reviewComments;
  private $deleted;
  
  /**
   * Constructs a walk object from an array of database fields
   * @param array $dbArr Associative array from the walkprogramewalks table
   */
  public function __construct($dbArr)
  {
    $this->name = $dbArr['name'];
    $this->startDate = $dbArr['WalkDate'];
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
    
    $this->leaderID = $dbArr['leaderid'];
    $this->backmarkerID = $dbArr['backmarkerid'];
//     $this->meetPlace = $dbArr[''];
//     $this->meetTime = $dbArr[''];
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
   * Gets the next few scheduled walks
   * @param int $iNumToGet Maximum number of events to fetch. Default is no limit.
   * @return array Array of WalkInstances
   */
  public static function getNext($iNumToGet = 0) {
    // Build a query to get future walks that haven't been deleted.
    // We do want cancelled walks - users should be notified about these.
    $db = JFactory::getDBO();
    $query = $db->getQuery(true);
    $query->select("*");
    $query->from("walkprogrammewalks");
    // TODO: This is a stored proc currently - can we use this?
    $query->where(array(
        "WalkDate >= CURDATE()",
        "NOT deleted",
        "readytopublish",
    ));
    $query->order(array("WalkDate ASC", "meettime ASC"));
    $db->setQuery($query);
    $walkData = $db->loadAssocList();
    
    // Build an array of WalkInstances
    // TODO: Set actual SQL limit
    $walks = array();
    while (count($walkData > 0) && count($walks) < $iNumToGet) {
      $walk = new WalkInstance(array_shift($walkData));
      $walks[] = $walk;
    }
    
    return $walks;
  }
  
  public function isCancelled() {
    return false;
  }
}