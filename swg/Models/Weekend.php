<?php
/**
 * A weekend away
 */
class Weekend extends Event {
  private $endDate;
  private $placeName;
  private $area;
  private $url;
  private $places;
  private $cost;
  private $contact;
  private $noContactOfficeHours;
  private $bookingsOpen;
  private $challenge;
  private $swg;
  
  public function __construct($dbArr)
  {
    $this->name = $dbArr['name'];
    $this->startDate = $dbArr['startdate'];
    $this->endDate = $dbArr['enddate'];
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
  public static function getNext($iNumToGet = 0) {
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
    while (count($weekendData > 0) && count($weekends) < $iNumToGet) {
      $weekend = new Weekend(array_shift($weekendData));
      $weekends[] = $weekend;
    }
  
    return $weekends;
  }
}