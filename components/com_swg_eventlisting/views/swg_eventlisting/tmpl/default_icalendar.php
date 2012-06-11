<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
?>
BEGIN:VCALENDAR
VERSION:2.0
PRODID:TODO
<?php foreach ($this->events as $event) {?>
BEGIN:VEVENT
UID:<?php echo $event->getEventType();?>No<?php echo $event->id."\n";?>
DTSTAMP:TODO
ORGANIZER:CN=Sheffield Walking Group:MAILTO:sheffieldwalkinggroup@hotmail.com
DTSTART:<?php echo strftime("%Y%m%dT%H%M%SZ", $event->startDate)."\n";?>
DTEND:TODO
SUMMARY:<?php echo $event->name."\n"; ?>
<?php // TODO: iCalendar can handle cancelled events & updates. Look up full documentation. ?>
END:VEVENT
<?php } ?>
END:VCALENDAR