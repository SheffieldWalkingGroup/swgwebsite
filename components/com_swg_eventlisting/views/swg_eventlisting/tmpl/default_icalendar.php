<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
?>
BEGIN:VCALENDAR
VERSION:2.0
<?php foreach ($this->events as $event) {?>
BEGIN:VEVENT
UID:<?php echo $event->getEventType();?>No<?php echo $event->id."\r\n";?>
CATEGORIES:<?php echo ucfirst($event->getEventType())."\r\n"; ?>
ORGANIZER;<?php
  // For a walk, the organiser is the leader. For a social or weekend, it's the organiser
  if ($event instanceof WalkInstance) {
    echo "CN=".$event->leader->displayName." (".$event->leader->telephone.") - don't call during :MAILTO:sheffieldwalkinggroup@hotmail.com";
  }
  else if ($event instanceof Social) {
    echo "CN=Sheffield Walking Group:MAILTO:sheffieldwalkinggroup@hotmail.com";
  }
  else if ($event instanceof Weekend) {
    echo "CN=Sheffield Walking Group:MAILTO:sheffieldwalkinggroup@hotmail.com";
  }
  else {
    echo "CN=Sheffield Walking Group:MAILTO:sheffieldwalkinggroup@hotmail.com";
  }
  echo "\r\n";
?>
DTSTART:<?php echo strftime("%Y%m%dT%H%M%S", $event->startDate)."\r\n";?>
SUMMARY:<?php
  echo $event->name;
  if ($event instanceof WalkInstance) {
    echo " (".$event->distanceGrade.$event->difficultyGrade." - ".$event->miles." miles)";
  }
  echo "\r\n";
?>
DESCRIPTION:<?php echo $this->parseText($event->description)."\r\n";?>
<?php 
  // Contacts are specified in different formats for different events
  if ($event instanceof WalkInstance) {
    echo "ATTENDEE;ROLE=CHAIR;CN=".$event->leader->displayName.":MAILTO:sheffieldwalkinggroup@example.com\r\n";
  } 
echo "DTEND:"; 
  if ($event instanceof WalkInstance) {
    echo strftime("%Y%m%dT%H%M%S", $event->estimateFinishTime())."\r\n";
  } else if ($event instanceof Weekend) {
    // End time should be midnight of the day after the last one for an all day event
    echo strftime("%Y%m%dT%H%M%S", $event->endDate+86400);
  } else if ($event instanceof Social) {
    echo strftime("%Y%m%dT%H%M%S", $event->startDate);
  }
echo "\r\n";
// TODO: iCalendar can handle cancelled events & updates. Look up full documentation. ?>
END:VEVENT
<?php } ?>
END:VCALENDAR