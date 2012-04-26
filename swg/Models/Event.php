<?php
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
  
  /**
   * Gets the next few events of this type as an array
   * @param int $iNumToGet Maximum number of events to fetch. Default is no limit.
   * @return array Array of Events
   */
  abstract static function getNext($iNumToGet = 0);
}