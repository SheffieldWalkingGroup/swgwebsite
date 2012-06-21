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
            <img src="/images/stories/linearwalk.png" border="0" alt="Linear walks start at one place and finish at another; usually this means we have to use public transport" title="Linear walks start at one place and finish at another; usually this means we have to use public transport" />
          <?php endif; if ($event->dogFriendly):?>
            <img src="/images/stories/dogs.png" border="0" alt="Dog-friendly: the route is suitable for bringing dogs along." title="Dog-friendly: the route is suitable for bringing dogs along." width="19" height="16" />
          <?php endif; if ($event->childFriendly):?>
            <img src="/images/stories/pushchair.png" border="0" alt="Kiddy-friendly: route (and pace) of walk are suitable for bringing infants. Check with the walk leader what kind of prams/buggies can be used." title="Kiddy-friendly: route (and pace) of walk are suitable for bringing infants. Check with the walk leader what kind of prams/buggies can be used." width="30" height="28" />
          <?php endif; if ($event->speedy):?>
            <img src="/images/stories/speedy.png" border="0" alt="Fast-paced walk. This kind of walk will be done faster than usual, aiming for an early finish." title="Fast-paced walk. This kind of walk will be done faster than usual, aiming for an early finish." width="24" height="30" />
          <?php endif; ?>
        </p>
      <?php elseif ($event instanceof Weekend && $event->challenge): ?>
        <p class="icons">
          <img src="/images/stories/challenge.png" border="0" alt="Challenge walk: more than a day-walk, a mini-expedition" title="Challenge walk: more than a day-walk, a mini-expedition" width="19" height="34" />
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