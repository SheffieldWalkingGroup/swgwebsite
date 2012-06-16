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
  protected $startDate;
  protected $description;
  protected $okToPublish;
  
  
  
  /**
   * Gets the next few events of this type as an array
   * @param int $numToGet Maximum number of events to fetch. Default is no limit.
   * @return array Array of Events
   */
  abstract static function getNext($numToGet = 0);
  
  /**
   * Gets a single event by its ID
   * @param int $id Event ID to fetch
   * @return Event object
   */
  abstract static function getSingle($id);
  
  public function getEventType() {
    return strtolower(get_class($this));
  }
  
  
  
  

}