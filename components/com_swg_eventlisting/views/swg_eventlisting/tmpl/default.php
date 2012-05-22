<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// TODO: Classes for events on different days
foreach ($this->events as $event) {?>
  <div class="event published <?php echo ($event->getEventType());?> walksaturday">
    <div class="eventheader">
      <span class="date" id="<?php echo $event->getEventType();?>No<?php echo $event->id?>">
        <?php echo date("l jS F",$event->startDate); // TODO: End date for weekends?>
      </span>
      <h3><?php echo $event->name; ?></h3>
    </div>
    <div class="eventdescription">
      <p><?php echo $event->description; ?></p>
      <!-- Icons for walks -->
    </div>
    <!-- Walk info -->
  </div>
<?php }