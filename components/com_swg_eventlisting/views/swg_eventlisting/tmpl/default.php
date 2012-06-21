<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

foreach ($this->events as $event) {?>
  <div class="event published <?php echo $event->getEventType(); if ($event instanceof WalkInstance) echo " walk".strtolower($event->getWalkDay());?>">
    <div class="eventheader">
      <span class="date" id="<?php echo $event->getEventType();?>No<?php echo $event->id?>">
        <?php echo date("l jS F".($this->notThisYear($event->startDate)?" Y":""),$event->startDate); // TODO: End date for weekends?>
      </span>
      <?php if ($event instanceof WalkInstance):?>
        <span class="rating">
          <?php echo $event->distanceGrade.$event->difficultyGrade." (".$event->miles." miles)"; ?>
        </span>
      <?php endif;?>
      <h3><?php echo $event->name; ?></h3>
    </div>
    <div class="description">
      <p><?php echo $event->description; ?></p>
      <?php if ($event instanceof WalkInstance):?>
        <p class="icons">
          <?php if ($event->isLinear):?>
            <img src="images/stories/linearwalk.png" border="0" alt="Linear walks start at one place and finish at another; usually this means we have to use public transport" title="Linear walks start at one place and finish at another; usually this means we have to use public transport" />
          <?php endif;
          // TODO: More icons?>
        </p>
      <?php endif;?>
    </div>
    <div class="eventinfo">
      <?php if ($event instanceof WalkInstance):?>
        <p class="start">
          <span>Start:</span>
          <a href="http://www.streetmap.com/loc/<?php echo $event->startGridRef?>" title="Streetmap view of approximate location">
            <?php echo $event->startGridRef.", ".$event->startPlaceName?>
          </a>
        </p>
        <?php if ($event->isLinear):?>
          <p class="end"><span>End:</span> <?php echo $event->endGridRef.", ".$event->endPlaceName;?></p>
        <?php endif; ?>
        <p class="transport">
          <span>Transport:</span>
          <?php if (!$event->meetPoint->isOther()) {
            echo "Meet at ".strftime("%H:%M", $event->meetPoint->meetTime)." at ".$event->meetPoint->longDesc.". ";
          }
          if ($event->meetPoint->hasExtraInfo()) {
            echo $event->meetPoint->extra;
          }
          ?>
        </p>
        <p class="leader">
          <span>Walk Leader:</span>
          <?php
            echo $event->leader->displayName.(!empty($event->leader->telephone)?" (".$event->leader->telephone.")":""); 
            if ($event->leader->noContactOfficeHours)
              echo " &ndash; don't call during office hours";
          ?>
        </p>
        <p class="backmarker">
          <span>Backmarker:</span> <?php echo $event->backmarker->displayName; ?>
        </p>
      <?php elseif ($event instanceof Social):?>
        <p class="socialbooking">
          <span>Contact:</span> <?php echo $event->bookingsInfo; ?>
        </p>
      <?php elseif ($event instanceof Weekend):?>
        <p class="moreinfo">
          <span>More info:</span> <?php echo $event->url?>
        </p>
        <p class="places">
          <!-- TODO: Link to booking policy -->
          <span>Places:</span> <?php echo $event->places." at ".$event->cost?> (remember the booking and refunds policy)
        </p>
        <p class="weekendbooking">
          <!-- TODO: No contact office hours -->
          <span>Contact:</span> <?php echo $event->contact; ?>
        </p>
        <p class="bookingopen">
          <span>Bookings open:</span> <?php echo $event->bookingsOpen; ?>
        </p>
      <?php endif; ?>
    </div>
    <div style="clear:right;">&nbsp;</div>
  </div>
<?php }