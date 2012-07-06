<?php
jimport('joomla.application.component.modelitem');
require_once("SWGBaseModel.php");
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
   * Gets the next few events of this type as an array
   * @param int $startTime Get events ON OR AFTER this date. Default is today. Accepts Unix time.
   * @param int $endTime Get events ON OR BEFORE this date. Default is the end of time. Accepts Unix time.
   * @param int $numToGet Maximum number of events to fetch. Default is no limit.
   * @return array Array of Events
   */
  public abstract static function get($startDate=self::DateToday, $endDate=self::DateEnd, $numToGet = -1);
  
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
}

/**
 * Keeps track of alterations to an event
 * @author peter
 *
 */
class EventAlterations extends SWGBaseModel {
  
  protected $details = false;
  protected $cancelled = false;
  protected $placeTime = false;
  protected $organiser = false;
  protected $date = false;

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