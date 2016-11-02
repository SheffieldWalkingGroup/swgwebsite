<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

$nextProtocolReminder = 0;
$event = $this->event;

include JPATH_SITE."/components/com_swg_events/helpers/tmpl/eventinfo.php";

?>
<!-- TODO: Must have at least 1 attendee - the organiser. Backmarker as well if set -->
<h3><?php if ($event->numAttendees == 0):?>No-one<?php elseif ($event->numAttendees == 1):?>1 Person<?php else: echo $event->numAttendees;?>People<?php endif; ?> logged this in their diary</h3>
<?php if ($event->numAttendees > 0):?>
<ul class="event-attendance">
	
	<?php if ($event->wasAttendedBy(JFactory::getUser()->id)): // Always show current user first ?>
		<li><?php echo JFactory::getUser()->name; ?></li>
	<?php endif; ?>
	
	<?php if ($event->getOrganiser() != null && $event->getOrganiser()->id != JFactory::getUser()->id): //Organiser goes next ?>
		<li><?php echo $event->getOrganiser()->name . " (".$event->getOrganiserWord().")"; ?></li>
	<?php endif; ?>
	
	<?php foreach ($event->attendedBy as $attendee): // Then all the users, excluding the current user and the organiser ?>
		<?php if ($attendee->id == JFactory::getUser()->id || $event->isOrganiser($attendee)) continue; ?>
		<li><?php echo $attendee->name; ?></li>
	<?php endforeach ?>
</ul>
<?php endif; ?>