<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

$nextProtocolReminder = 0;

if ($this->showAnyAddLinks()):?>
	<p>
		<?php if ($this->showAddWalk()):?><a href="<?php echo $this->addEditWalkURL();?>">Add walks</a><?php endif;?>
		<?php if ($this->showAddSocial()):?><a href="<?php echo $this->addEditSocialURL();?>">Add social</a><?php endif;?>
		<?php if ($this->showAddWeekend()):?><a href="<?php echo $this->addEditWeekendURL();?>">Add weekend</a><?php endif;?>
	</p>
<? endif;
foreach ($this->events as $event):?>
  <div class="event published vevent" id="<?php echo $event->getEventType();?>_<?php echo $event->id?>">
	<?php if ($event->alterations->cancelled): ?>
		<p class="cancelled-message">Cancelled</p>
		<?php if ($this->showEditLinks($event)): // Cancelled message blocks all normal links - this appears above it ?>
			<p class="edit-cancelled">
				<a href="<?php echo $this->editURL($event);?>">Edit <?php echo strtolower($event->type);?></a>
			</p>
		<?php endif;?>
	<?php endif; ?>
    <div class="content <?php echo $event->getEventType(); if ($event instanceof WalkInstance) echo " walk".strtolower($event->getWalkDay()); if ($event->alterations->cancelled) echo " cancelled"; if (!$event->okToPublish) echo " unpublished";?>">
      <div class="eventheader">
        <time datetime="<?php echo date("Y-m-d\TH:iO", $event->start);?>" class="dtstart date<?php if ($event->alterations->date) echo " altered\" title=\"Date altered"; ?>">
          <?php 
            if ($event instanceof Weekend)
              // Display start and end dates for weekends. Only display month for start if the weekend straddles a month boundary
              echo date("l jS".($this->notSameMonth($event->start, $event->endDate)?" F":""), $event->start)." - ".date("l jS F".($this->notThisYear($event->endDate)?" Y":""), $event->endDate); 
            else
              echo date("l jS F".($this->notThisYear($event->start)?" Y":""),$event->start); // Just start date for other things
          ?>
        </time>
        <?php if ($event instanceof WalkInstance):?>
          <span class="rating">
            <?php echo $event->distanceGrade.$event->difficultyGrade." (".$event->miles." miles)"; ?>
          </span>
        <?php endif;?>
        <time datetime="<?php if (!$event instanceof Weekend) echo date("H:iO", ($event instanceof WalkInstance ? $event->estimateFinishTime() : $event->end)); else echo date("Y-m-d", $event->endDate+86400 /* Must end at midnight the next day */);?>" class="dtend"></time>
        <h3 class="summary"><?php echo $event->name; ?></h3>
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
            <?php if (isset($event->meetPoint) && !$this->eventInPast($event)): ?>
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
				// Emergency - meet point is 'other' and we have no description
				if ($event->meetPoint->isOther() && !$event->meetPoint->hasExtraInfo())
					echo "No meeting place set.";
				?>
				</p>
			<?php endif; ?>
			<?php if (isset($event->leader)): ?>
				<p class="leader<?php if ($event->alterations->organiser) echo " altered\" title=\"Leader changed"; ?>">
				<span>Walk Leader:</span>
				<?php
					echo $event->leader->displayName;
					// Hide leader contact details if event has already happened
					if (!$this->eventInPast($event) && $event->leader->telephone != "")
					{
						echo " (".$event->leader->telephone.")"; 
						if ($event->leader->noContactOfficeHours)
							echo " &ndash; don't call during office hours";
					}
				?>
				</p>
			<?php endif; 
			if (isset($event->backmarker)): ?>
				<p class="backmarker">
				<span>Backmarker:</span> <?php echo $event->backmarker->displayName; ?>
				</p>
			<?php endif;?>
          <?php elseif ($event instanceof Social):?>
            <?php if ($this->isTimeSet($event->start)):?>
              <p class="start"><span>Start: </span><?php echo date("H:i", $event->start); ?></p>
              <?php if ($this->isTimeSet($event->end)):?>
              <p class="end"><span>End: </span><?php echo date("H:i", $event->end); ?> (approx)</p>
              <?php endif;
            endif; ?>
            
            <?php if ($event->location != ""): ?>
              <p class="location">
                <span>Location: </span><?php if ($event->hasMap()):?><a href="#" rel="map"><?php endif;
                    echo nl2br($event->location);
                  ?>
                <?php if ($event->hasMap()) echo "</a>";?>
              </p>
            <?php endif; ?>
            
            <?php if (!$this->eventInPast($event)): ?>
            
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
			<?php endif; ?>
          <?php elseif ($event instanceof Weekend):?>
            <?php if ($event->url != ""): // empty() doesn't work for some reason?>
              <p class="moreinfo">
                <span>More info:</span> <a href="<?php echo $event->url?>" target="_blank">Here</a>
              </p>
            <?php endif; ?>
            <?php if (!$this->eventInPast($event)): ?>
				<p class="places">
				<span>Places:</span> <?php echo $event->places." at ".$event->cost?> (remember the <a href="/weekends/bookings-payment-policy">booking and refunds policy</a>)
				</p>
				<?php if ($event->contact != ""): ?>
					<p class="weekendbooking">
						<span>Contact:</span> <?php echo $event->contact; ?>
						<?php if ($event->noContactOfficeHours):?>
							&ndash; don't call during office hours
						<?php endif; ?>

					</p>
				<?php endif; ?>
				<?php if ($event->bookingsOpen): ?>
					<p class="bookingopen">
						<span>Bookings open:</span> <?php echo $event->bookingsOpen; ?>
					</p>
				<?php endif;?>
				<p class="paymentdue">
					<span>Payment due:</span> <?php echo date("l jS F".($this->notThisYear($event->paymentDue)?" Y":""),$event->paymentDue); ?>
				</p>
			<?php endif;endif; ?>
			<?php if (!$event->alterations->cancelled): // Can't click links when cancelled ?>
				<div class="controls">
				<?php if ($event->hasMap()): ?>
					<p>
						<a href="#" rel="toggle-map" title="Show a map of this <?php echo strtolower($event->type);?>">Show map</a>
					</p>
					<?php if ($event instanceof WalkInstance && $event->canDownloadRoute()): ?>
						<p>
							<a href="/api/route?walkinstanceid=<?php echo $event->id;?>&amp;format=gpx" title="Download this walk in GPX format for a computer or GPS device">Download route</a>
						</p>
					<?php endif; ?>
				<?php endif; ?>
				<?php if ($this->eventInPast($event)): ?>
					<?php if ($event->attendees > 0):  // TODO: Potential warnings if value is unset? TODO: Add attendees & tickbox to past events on bottomless page?>
						<p><?php echo $event->attendees;if ($event->attendees == 1):?> person<?php else:?> people<?php endif;?> did this</p>
					<?php endif;?>
					<p>
						<a class="attendance" href="<?php echo JURI::current()?>?<?php echo JURI::buildQuery(array(
							"task" 	  => "attendance.attend",
							"evttype" => $event->getType(),
							"evtid"   => $event->id,
							"set"     => (int)(!$event->attendedby),
						));?>"
							><img src="/images/icons/<?php if ($event->attendedby):?>tick<?php else: ?>tickbox<?php endif;?>.png" width="19" height="16" /
						></a>
						You did this
					</p>
					<?php if ($event->attendedby): ?>
						<p><a href="/whats-on/previous-events/upload-track?wi=<?php echo $event->id;?>">Share GPS track</a></p>
						<p><a href="/photos/upload-photos">Share photos</a></p>
					<?php endif; ?>
				<?php endif; ?>
				<?php if ($this->showEditLinks($event)):?>
					<p>
						<a href="<?php echo $this->editURL($event);?>">Edit <?php echo strtolower($event->type);?></a>
					</p>
				<?php endif;?>
				</div>
			<?php endif; ?>
          
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