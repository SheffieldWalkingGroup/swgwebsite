<?php
/**
 * A collection of general functions and constants
 * @author peter
 *
 */
class SWG {
  const EventType_Walk = 1;
  const EventType_Social = 2;
  const EventType_Weekend = 3;
  const EventType_NewMemberSocial = 21;
  
  public static function printableEventType($typeID)
  {
    switch ($typeID)
    {
      case self::EventType_Walk:
        return "walk";
      case self::EventType_Social:
        return "social";
      case self::EventType_Weekend:
        return "weekend";
      case self::EventType_NewMemberSocial:
        return "new member social";
    }
  }
}