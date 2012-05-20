<?php
/**
 * A social
 */
class Social extends Event {
  
  private $bookingsInfo;
  private $clipartFilename;
  
  public function __construct($dbArr)
  {
    $this->name = $dbArr['title'];
    $this->startDate = $dbArr['on_date'];
    $this->description = $dbArr['fulldescription'];
    $this->okToPublish = $dbArr['readytopublish'];
    
    $this->bookingsInfo = $dbArr['bookingsinfo'];
    $this->clipartFilename = $dbArr['clipartfilename'];
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
  public static function getNext($iNumToGet = 0) {
    // Build a query to get future socials
    $db = JFactory::getDBO();
    $query = $db->getQuery(true);
    $query->select("*");
    $query->from("socialsdetails");
    // TODO: This is a stored proc currently - can we use this?
    $query->where(array(
        "on_date >= CURDATE()",
        "readytopublish",
    ));
    $query->order(array("on_date ASC", "title ASC"));
    $db->setQuery($query);
    $socialData = $db->loadAssocList();
  
    // Build an array of Socials
    // TODO: Set actual SQL limit
    $socials = array();
    while (count($socialData > 0) && count($socials) < $iNumToGet) {
      $social = new Social(array_shift($socialData));
      $socials[] = $social;
    }
  
    return $socials;
  }
  
}