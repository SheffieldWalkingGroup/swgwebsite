<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
?>
BEGIN:VCALENDAR
VERSION:2.0
<?php foreach ($this->events as $event) {?>
BEGIN:VEVENT
UID:<?php echo $event->getEventType();?>No<?php echo $event->id."\n";?>
CATEGORIES:<?php echo ucfirst($event->getEventType())."\n"; ?>
ORGANIZER;CN=Sheffield Walking Group:MAILTO:sheffieldwalkinggroup@hotmail.com
DTSTART:<?php echo strftime("%Y%m%dT%H%M%S", $event->startDate)."\n";?>
SUMMARY:<?php
  echo $event->name;
  if ($event instanceof WalkInstance) {
    echo " (".$event->distanceGrade.$event->difficultyGrade." - ".$event->miles." miles)";
  }
  echo "\n";
?>
DESCRIPTION:<?php echo $this->parseText($event->description)."\n";?>
CONTACT:<?php 
// TODO: Other contact info, e.g. no contact office hours
if ($event instanceof WalkInstance) {
  echo "Leader TODO"; // TODO: Backmarker todo too
}
else if ($event instanceof Social) {
  echo $event->bookingsInfo; // TODO: This will probably need parsing
}
else if ($event instanceof Weekend) {
  echo $event->contact;
}
echo "\n";
echo "DTEND:"; 
  if ($event instanceof WalkInstance) {
    echo strftime("%Y%m%dT%H%M%S", $event->estimateFinishTime())."\n";
  } else if ($event instanceof Weekend) {
    // End time should be midnight of the day after the last one for an all day event
    echo strftime("%Y%m%dT%H%M%S", $event->endDate+86400);
  } else if ($event instanceof Social) {
    echo strftime("%Y%m%dT%H%M%S", $event->startDate);
  }
echo "\n";
// TODO: iCalendar can handle cancelled events & updates. Look up full documentation. ?>
END:VEVENT
<?php } ?>
END:VCALENDAR