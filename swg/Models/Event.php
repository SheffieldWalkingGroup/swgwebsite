<?php
jimport('joomla.application.component.modelitem');
/**
 * Any event organised by the group
 * @author peter
 *
 */
abstract class Event extends JModelItem {
  protected $name;
  protected $startDate;
  protected $description;
  protected $okToPublish;
  
  public $id = 1; // FIXME: Make dynamic
  
  /**
   * Gets the next few events of this type as an array
   * @param int $iNumToGet Maximum number of events to fetch. Default is no limit.
   * @return array Array of Events
   */
  abstract static function getNext($iNumToGet = 0);
  
  public function getEventType() {
    return strtolower(get_class($this));
  }
  
  

}