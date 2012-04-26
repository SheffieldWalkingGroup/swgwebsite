<?php
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
    
    $this->walk = $dbArr[''];
    $this->distanceGrade = $dbArr[''];
    $this->difficultyGrade = $dbArr[''];
    $this->miles = $dbArr[''];
    $this->location = $dbArr[''];
    $this->startGridRef = $dbArr[''];
    $this->startPlaceName = $dbArr[''];
    $this->endGridRef = $dbArr[''];
    $this->endPlaceName = $dbArr[''];
    
    $this->childFriendly = $dbArr[''];
    $this->dogFriendly = $dbArr[''];
    $this->speedy = $dbArr[''];
    $this->isLinear = $dbArr[''];
    $this->transportByCar = $dbArr[''];
    $this->transportPublic = $dbArr[''];
    
    $this->leaderID = $dbArr[''];
    $this->backmarkerID = $dbArr[''];
    $this->meetPlace = $dbArr[''];
    $this->meetTime = $dbArr[''];
    $this->dateAltered = $dbArr[''];
    
    $this->headCount = $dbArr[''];
    $this->mileometer = $dbArr[''];
    $this->reviewComments = $dbArr[''];
    $this->deleted = $dbArr[''];
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
    $db->setQuery($query, 0, $iNumToGet);
    
    // Build an array of WalkInstances
    $walks = array();
    while ($walkArr = $db->loadAssoc()) {
      $walk = new Walk($walkArr)
    }
  }
}