<?php
jimport('joomla.application.component.modelitem');
require_once("SWGBaseModel.php");
include_once(JPATH_BASE."/swg/lib/phpcoord/phpcoord-2.3.php");
/**
 * Any event organised by the group
 * @author peter
 *
 */
abstract class Event extends SWGBaseModel {
  
  // Event properties
  protected $id;
  protected $name;
  protected $start;
  protected $description;
  protected $okToPublish;
  protected $alterations; 
  
  const DateToday = -1;
  const DateYesterday = -2;
  const DateTomorrow = -3;
  const DateEnd = 2147483647; // This is the end of Unix time, 19th January 2038. I expect this system to be replaced by then
  
  
  public function __construct() {
    $this->alterations = new EventAlterations();
  }
  
  /**
   * Returns the number of events in a date range
   */
  public abstract static function numEvents($startDate=self::DateToday, $endDate=self::DateEnd);
  
  /**
   * Gets the next few events of this type as an array
   * @param int $startTime Get events ON OR AFTER this date. Default is today. Accepts Unix time.
   * @param int $endTime Get events ON OR BEFORE this date. Default is the end of time. Accepts Unix time.
   * @param int $numToGet Maximum number of events to fetch. Default is no limit.
   * @param int $offset Number of events to skip before first displayed
   * @param bool $reverse True to return events newest first
   * @return array Array of Events
   */
  public abstract static function get($startDate=self::DateToday, $endDate=self::DateEnd, $numToGet = -1, $offset=null, $reverse=false);
  
  /**
   * Gets a limited number of events, starting today and going forwards
   * Partly for backwards-compatibility, but also to improve readability
   * @param int $numEvents Maximum number of events to get
   */
  public abstract static function getNext($numEvents);
  
  /**
   * Takes a timestamp, and returns that date
   * @param int $time Timestamp. Supports DateToday constant.
   * @param bool $after True to return the day after this timestamp, false (default) to return the day of the timestamp
   */
  public static function timeToDate($time, $after=false) {
    $time = intval($time);
    if ($time == self::DateToday)
      $rawDate = getdate();
    else if ($time == self::DateYesterday)
      $rawDate = getdate(time()-86400);
    else if ($time == self::DateTomorrow)
      $rawDate = getDate(time()+86400);
    else
      $rawDate = getdate($time);
    
    // Add on one day
    if ($after)
      $rawDate += 86400;
    
    $dateString = $rawDate['year']."-".$rawDate['mon']."-".$rawDate['mday'];
    return $dateString;
  }
  
  /**
   * Gets a single event by its ID
   * @param int $id Event ID to fetch
   * @return Event object
   */
  public abstract static function getSingle($id);
  
  public function getEventType() {
    return strtolower(get_class($this));
  }
  
  public function isCancelled() {
    return false;
  }
  
  /**
   * Whether this event can display a map
   * @return boolean
   */
  public abstract function hasMap();
  
  /**
   * Adds fields on this event to a query being prepared to go into the database
   * @param JDatabaseQuery &$query Query being prepared. Modified in place.
   */
  public function toDatabase(JDatabaseQuery &$query)
  {
    foreach ($this->dbmappings as $var => $dbField)
    {
		if (isset($this->$var))
			$query->set($dbField." = '".$query->escape($this->$var)."'");
    }
  }
  
  public function fromDatabase(array $dbArr)
  {
    foreach ($this->dbmappings as $var => $dbField)
    {
      $this->$var = $dbArr[$dbField];
    }
  }
  
  /**
   * Converts the values of this event to an array suitable for outputting to a form
   * @return array
   */
  public abstract function valuesToForm();
  
  /**
   * Determine if this event is valid and suitable for use
   * @return boolean
   */
  public abstract function isValid();
  
  /**
   * Save this event to the database
   * Also handles versioning automatically
   */
  public function save($incrementVersion = true) {
    $db = JFactory::getDbo();
    
    // Handle versioning & last modified
    if ($incrementVersion)
      $this->alterations->incrementVersion();
    $this->alterations->setLastModified(time());
    
    // Commit everything as one transaction
    $db->transactionStart();
    $query = $db->getQuery(true);
    
    $this->toDatabase($query);
    
    // What table?
    if ($this instanceof WalkInstance)
    {
      $table = "walkprogrammewalks";
      $idField = "SequenceID";
    }
    else if ($this instanceof Social)
    {
      $table = "socialsdetails";
      $idField = "SequenceID";
    }
    else if ($this instanceof Weekend)
    {
      $table = "weekendsaway";
      $idField = "ID";
    }
    else
      throw new Exception("Don't know how to save this");
    
    // Update or insert?
    if (!isset($this->id))
    {
      $query->insert($table);
    }
    else 
    {
      $query->where($idField." = ".(int)$this->id);
      $query->update($table);
    }
// echo $query;
    $db->setQuery($query);
    $db->query();
    
    if (!isset($this->id))
    {
      // Get the ID from the database
      $this->id = $db->insertid();
    }
    
    // TODO: Handle failure
    
    // Commit the transaction - the route is not a critical part of the walk
    $db->transactionCommit();
  }
}

/**
 * Keeps track of alterations to an event
 * @author peter
 *
 */
class EventAlterations extends SWGBaseModel {
  
  protected $version = 0;
  protected $lastModified = null;
  
  protected $details = false;
  protected $cancelled = false;
  protected $placeTime = false;
  protected $organiser = false;
  protected $date = false;
  
  public function __construct() {
	$this->version = 1;
  }
  
  public function setVersion($v) {
    $this->version = (int)$v;
  }
  
  public function setLastModified($d) {
    if (is_int($d))
      $this->lastModified = $d;
    else {
        $this->lastModified = strtotime($d);
    }
  }
  
  public function incrementVersion()
  {
    $this->version++;
  }
    

  public function setDetails($d) {
    $this->details = (bool)$d; 
  }
  
  public function setCancelled($c) {
    $this->cancelled = (bool)$c;
  }
  
  public function setPlaceTime($m) {
    $this->placeTime = (bool)$m;
  }
  
  public function setOrganiser($l) {
    $this->organiser = (bool)$l;
  }
  
  public function setDate($d) {
    $this->date = (bool)$d;
  }
  
  public function __get($name)
  {
    return $this->$name; // TODO: What params should be exposed?
  }
  
  public function anyAlterations()
  {
    return ($this->details || $this->cancelled || $this->placeTime || $this->organiser || $this->date);
  }
  
  /**
   * Add in anyAlterations
   * @see SWGBaseModel::sharedProperties()
   */
  protected function sharedProperties() {
    $prop = parent::sharedProperties();
    $prop['any'] = $this->anyAlterations();
    return $prop;
  }
}