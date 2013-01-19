<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

$nextProtocolReminder = 0;

foreach ($this->events as $event):?>
  <div class="event published" id="<?php echo $event->getEventType();?>_<?php echo $event->id?>">
  <?php if ($event->alterations->cancelled): ?><p class="cancelled-message">Cancelled</p><?php endif; ?>
    <div class="content <?php echo $event->getEventType(); if ($event instanceof WalkInstance) echo " walk".strtolower($event->getWalkDay()); if ($event->alterations->cancelled) echo " cancelled"; if (!$event->okToPublish) echo " unpublished";?>">
      <div class="eventheader">
        <span class="date<?php if ($event->alterations->date) echo " altered\" title=\"Date altered"; ?>">
          <?php 
            if ($event instanceof Weekend)
              // Display start and end dates for weekends. Only display month for start if the weekend straddles a month boundary
              echo date("l jS".($this->notSameMonth($event->start, $event->endDate)?" F":""), $event->start)." - ".date("l jS F".($this->notThisYear($event->endDate)?" Y":""), $event->endDate); 
            else
              echo date("l jS F".($this->notThisYear($event->start)?" Y":""),$event->start); // Just start date for other things
          ?>
        </span>
        <?php if ($event instanceof WalkInstance):?>
          <span class="rating">
            <?php echo $event->distanceGrade.$event->difficultyGrade." (".$event->miles." miles)"; ?>
          </span>
        <?php endif;?>
        <h3><?php echo $event->name; ?></h3>
      </div>
      <div class="eventbody">
        <div class="description<?php if ($event->alterations->details) echo " altered\" title=\"Details altered"; ?>">
          <p><?php echo nl2br($event->description); ?></p>
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
              <a href="http://www.streetmap.com/loc/<?php echo $event->startGridRef?>" title="Map of approximate location" rel="map-start" target="_blank">
                <?php echo $event->startGridRef.", ".$event->startPlaceName?>
              </a>
            </p>
            <?php if ($event->isLinear):?>
              <p class="end">
                <span>End:</span>
                <a href="http://www.streetmap.com/loc/<?php echo $event->endGridRef?>" title="Map of approximate location" rel="map-end" target="_blank">
                  <?php echo $event->endGridRef.", ".$event->endPlaceName;?>
                </a>
              </p>
            <?php endif; ?>
            <p class="transport<?php if ($event->alterations->placeTime) echo " altered\" title=\"Place & time altered"; ?>">
              <span>Transport:</span>
              <?php if (!$event->meetPoint->isOther()) {
                echo "Meet at ".strftime("%H:%M", $event->meetPoint->meetTime)." at ";
                if ($event->meetPoint->location)
                {
                  echo "<a href='http://www.streetmap.com/loc/N{$event->meetPoint->location->lat},E{$event->meetPoint->location->lng}' rel='map-transport' target='_blank'>";
                }
                echo $event->meetPoint->longDesc;
                if ($event->meetPoint->location)
                {
                  echo "</a>";
                }
                echo ". ";
              }
              if ($event->meetPoint->hasExtraInfo()) {
                echo $event->meetPoint->extra;
              }
              ?>
            </p>
            <p class="leader<?php if ($event->alterations->organiser) echo " altered\" title=\"Leader changed"; ?>">
              <span>Walk Leader:</span>
              <?php
                echo $event->leader->displayName.(($event->leader->telephone != "")?" (".$event->leader->telephone.")":""); 
                if ($event->leader->noContactOfficeHours)
                  echo " &ndash; don't call during office hours";
              ?>
            </p>
            <p class="backmarker">
              <span>Backmarker:</span> <?php echo $event->backmarker->displayName; ?>
            </p>
          <?php elseif ($event instanceof Social):?>
            <?php if ($this->isTimeSet($event->start)):?>
              <p class="start"><span>Start: </span><?php echo date("H:i", $event->start); ?></p>
              <?php if ($this->isTimeSet($event->end)):?>
              <p class="end"><span>End: </span><?php echo date("H:i", $event->end); ?> (approx)</p>
              <?php endif;
            endif; ?>
            
            <?php if ($event->location != "" || $event->hasMap()): ?>
              <p class="location">
                <span>Location: </span><?php if ($event->hasMap()):?><a href="#" rel="map"><?php endif;
                  if ($event->location != "")
                    echo nl2br($event->location);
                  else 
                    echo "Show map";
                  ?>
                <?php if ($event->hasMap()) echo "</a>";?>
              </p>
            <?php endif; ?>
            
            <?php if ($event->cost != ""):?>
              <p class="cost">
                <span>Cost: </span><?php echo nl2br($event->cost);?>
              </p>
            <?php endif;?>
            
            <?php if ($event->bookingsInfo != ""): ?>
				<p class="socialbooking">
					<span>Contact:</span> <?php echo $event->bookingsInfo; ?>
				</p>
			<?php endif; ?>
          <?php elseif ($event instanceof Weekend):?>
            <?php if ($event->url != ""): // empty() doesn't work for some reason?>
              <p class="moreinfo">
                <span>More info:</span> <a href="<?php echo $event->url?>" target="_blank">Here</a>
              </p>
            <?php endif; ?>
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
          <p class="controls">
			<?php if ($event->hasMap()): ?>
              <a href="#" rel="toggle-map" title="Show a map of this <?php echo strtolower($event->type);?>">Show map</a>
              <?php if ($event instanceof WalkInstance && $event->canDownloadRoute()): ?>
				<a href="/api/route?walkinstanceid=<?php echo $event->id;?>&amp;format=gpx" title="Download this walk in GPX format for a computer or GPS device">Download route</a>
			  <?php endif; ?>
            <?php endif; ?>  
	  </p>
          
        </div>
        <div style="clear:right;">&nbsp;</div>
      </div>
    </div>
  </div>
<?php
  // Display any relevant protocol reminders after each event
  if (!empty($this->protocolReminders) && is_array($this->protocolReminders))
  {
    if (count($this->protocolReminders) < $nextProtocolReminder+1)
      $nextProtocolReminder = 0;
    ?><p class="protocolreminder"><?php echo $this->protocolReminders[$nextProtocolReminder++]['text']; ?></p><?php 
  }
  
  endforeach;