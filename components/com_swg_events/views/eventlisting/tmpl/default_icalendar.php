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
DTSTART:<?php echo strftime("%Y%m%dT%H%M%S", $event->start)."\r\n";?>
SUMMARY:<?php
	echo $event->name;
	if ($event instanceof WalkInstance) {
		echo " (".$event->distanceGrade.$event->difficultyGrade." - ".$event->miles." miles)";
	}
	echo "\r\n";
  
// Build the description, then parse it in one go
$description = $event->description."\n\n";

// Contacts are specified in different formats for different events
if ($event instanceof WalkInstance) {
	$description .= "Leader: ".$event->leader->displayName." (".$event->leader->telephone.")";
	if ($event->leader->noContactOfficeHours)
		$description .= " - don't call during office hours";
	$description .= "\\nBackmarker: ".$event->backmarker->displayName;
} else if ($event instanceof Social) {
	$description .= "Contact: ".$event->bookingsInfo;
} else if ($event instanceof Weekend) {
	$description .= "Contact: ".$event->contact;
}
echo "DESCRIPTION: ". $this->parseText($description)."\r\n";
   
echo "DTEND:"; 
if ($event instanceof WalkInstance) {
	echo strftime("%Y%m%dT%H%M%SZ", swg::localToUTC($event->estimateFinishTime())."\r\n";
} else if ($event instanceof Weekend) {
	// End time should be midnight of the day after the last one for an all day event
	echo strftime("%Y%m%dT%H%M%SZ", swg::localToUTC($event->endDate+86400));
} else if ($event instanceof Social) {
	if (isset($event->end))
		echo strftime("%Y%m%dT%H%M%SZ", swg::localToUTC($event->end));
	else
		echo strftime("%Y%m%dT%H%M%SZ", swg::localToUTC($event->start + 3600*2)); // Default end time - 2 hours after start
}
echo "\r\n";

echo "STATUS:";
	if ($event->okToPublish && ! $event->isCancelled())
		echo "CONFIRMED";
	else if ($event->isCancelled())
		echo "CANCELLED";
	else
		echo "TENTATIVE";
echo "\r\n";
// TODO: iCalendar can handle cancelled events & updates. Look up full documentation. ?>
END:VEVENT
<?php } ?>
END:VCALENDAR