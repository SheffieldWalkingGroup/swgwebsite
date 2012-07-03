<?php
 
/**
 * @package     SWG
 * @subpackage  mod_swg_nextevents
 * @copyright   Copyright (C) 2012 Peter Copeland. All rights reserved.
 */
 
// No direct access to this file
defined('_JEXEC') or die;

?>
  <div class="box">
    <ul>
<?php
// Get the next events
$events = array();
$numEvents = $params->get("numberOfEvents",3);
switch ($params->get('eventType')) {
  case SWG::EventType_Walk:
    $events = WalkInstance::getNext($numEvents);
    break;
}

foreach ($events as $event) {?>
  <li class="<?php if ($event->isCancelled()) echo "cancelled";?>">
    <span class="date"><?php echo date("l jS M", strtotime($event->start)); ?></span>
    <h4>
      <a href="#"><?php echo $event->name; ?>&nbsp;(<?php echo $event->distanceGrade.$event->difficultyGrade;?>)</a>
    </h4>
  </li>
<?php }

?>
</ul></div>