<?php
require_once("Event.php");
/**
 * A weekend away
 */
class Weekend extends Event {
  protected $endDate;
  protected $placeName;
  protected $area;
  protected $url;
  protected $places;
  protected $cost;
  protected $contact;
  protected $noContactOfficeHours;
  protected $bookingsOpen;
  protected $challenge;
  protected $swg;
  
  public function __construct($dbArr)
  {
    $this->id = $dbArr['ID'];
    $this->name = $dbArr['name'];
    $this->startDate = strtotime($dbArr['startdate']);
    $this->endDate = strtotime($dbArr['enddate']);
    $this->placeName = $dbArr['placename'];
    $this->area = $dbArr['area'];
    
    $this->description = $dbArr['description'];
    $this->url = $dbArr['url'];
    $this->places = $dbArr['places'];
    $this->cost = $dbArr['cost'];
    
    $this->contact = $dbArr['contact'];
    $this->noContactOfficeHours = (bool)$dbArr['nocontactofficehours'];
    $this->bookingsOpen = $dbArr['bookingsopen'];
    $this->okToPublish = $dbArr['oktopublish'];
    $this->challenge = (bool)$dbArr['challenge'];
    $this->swg = (bool)$dbArr['swg'];
  }
  
  public function __get($name)
  {
    return $this->$name; // TODO: What params should be exposed?
  }
  
  /**
   * Gets the next few scheduled weekends
   * @param int $iNumToGet Maximum number of events to fetch. Default is no limit.
   * @return array Array of Weekends
   */
  public static function getNext($iNumToGet = 7) {
    // Build a query to get future weekends
    $db = JFactory::getDBO();
    $query = $db->getQuery(true);
    $query->select("*");
    $query->from("weekendsaway");
    // TODO: This is a stored proc currently - can we use this?
    $query->where(array(
        "enddate >= CURDATE()",
        "oktopublish",
    ));
    $query->order(array("startdate ASC"));
    $db->setQuery($query);
    $weekendData = $db->loadAssocList();
  
    // Build an array of Weekends
    // TODO: Set actual SQL limit
    $weekends = array();
    while (count($weekendData) > 0 && count($weekends) != $iNumToGet) {
      $weekend = new Weekend(array_shift($weekendData));
      $weekends[] = $weekend;
    }
  
    return $weekends;
  }
  
  public static function getSingle($id) {
    $db = JFactory::getDBO();
    $query = $db->getQuery(true);
    $query->select("*");
    $query->from("weekendsaway");
    
    $query->where(array("ID = ".intval($id)));
    $db->setQuery($query);
    $res = $db->query();
    if ($db->getNumRows($res) == 1)
      return new Weekend($db->loadAssoc());
    else
      return null;
    
  }
}