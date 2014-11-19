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
				echo date("l jS".($this->notSameMonth($event->start, $event->endDate)?" F":""), $event->start); 
			else
				echo date("l jS F".($this->notThisYear($event->start)?" Y":""),$event->start); // Just start date for other things
		?>
		</time>
		<?php if ($event instanceof Weekend):?>
			<span class="date">&nbsp;-&nbsp;</span>
			<time datetime="<?php echo date("Y-m-d", $event->endDate+86399 /* End at 23:59 */);?>" class="dtend date"><?php echo date("l jS F".($this->notThisYear($event->endDate)?" Y":""), $event->endDate);?></time>
		<?php elseif ($event instanceof WalkInstance):?>
			<p class="headerextra">
				<span class="rating">
					<?php echo $event->distanceGrade.$event->difficultyGrade;?>
				</span>
				<span class="distance">
				(<?php // No space after bracket
					if (empty($event->distance))
					{
						$inDist = $event->miles;
						$inUnit = UnitConvert::Mile;
					}
					else
					{
						$inDist = $event->distance;
						$inUnit = UnitConvert::Metre;
					}
					echo str_replace(".0","",UnitConvert::displayDistance($inDist,$inUnit, UnitConvert::Mile))."<span class='unit2'>, ".UnitConvert::displayDistance($inDist, $inUnit, UnitConvert::Kilometre)."</span>";
					?>)
			</p>
			<time datetime="<?php echo date("H:iO", $event->estimateFinishTime());?>" class="dtend date"></time>
		<?php elseif ($event instanceof Social): ?>
			<time datetime="<?php echo date("H:iO", $event->end);?>" class="dtend date"></time>
		<?php endif;?>
		<h3 class="summary"><?php echo (!empty($detailLink) ? "<a href='{$detailLink}'>{$event->name}</a>" : $event->name); ?></h3>
	</div>
	<div class="eventbody">
		<?php if (!$event instanceof DummyEvent): /* No info for dummy events */ ?>
			<div class="eventinfo">
			<?php if ($event instanceof WalkInstance):?>
				<p class="start">
				<span class='lbl'>Start:</span>
				<a class='mobile app-link' href='geo:<?php echo $event->startLatLng->lat.",".$event->startLatLng->lng.";u=100?q=".$event->startLatLng->lat.",".$event->startLatLng->lng."(".urlencode($event->startPlaceName).")";?>'>Open in map app</a>
				<a href="http://www.streetmap.com/loc/<?php echo $event->startGridRef?>" title="Map of approximate location" rel="map-start" target="_blank">
					<?php echo $event->startGridRef.", ".$event->startPlaceName?>
				</a>
				</p>
				<?php if ($event->isLinear):?>
				<p class="end">
					<span class='lbl'>End:</span>
					<a class='mobile app-link' href='maps:<?php echo $event->endLatLng->lat.",".$event->endLatLng->lng.";u=100?q=".$event->endLatLng->lat.",".$event->endLatLng->lng."(".urlencode($event->endPlaceName).")";?>'>Open in map app</a>
					<a href="http://www.streetmap.com/loc/<?php echo $event->endGridRef?>" title="Map of approximate location" rel="map-end" target="_blank">
						<?php echo $event->endGridRef.", ".$event->endPlaceName;?>
					</a>
				</p>
				<?php endif; ?>
				<?php if (isset($event->meetPoint) && !$this->eventInPast($event)): ?>
					<p class="transport<?php if ($event->alterations->placeTime) echo " altered\" title=\"Place & time altered"; ?>">
					<span class='lbl'>Transport:</span>
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
					<p itemscope="" itemtype="http://schema.org/Person" class="leader<?php if ($event->alterations->organiser) echo " altered\" title=\"Leader changed"; ?>">
						<span class='lbl'>Walk Leader:</span>
						<span class='val<?php echo $event->leader->noContactOfficeHours ? " noContactOfficeHours":"" ?>'>
							<span itemprop="name"><?php echo $event->leader->displayName; ?></span>
							<?php
								// Hide leader contact details if event has already happened
								if (!$this->eventInPast($event) && $event->leader->telephone != "")
								{
									echo " (<span itemprop='telephone' class='leadertel'>".$event->leader->telephone."</span>)"; 
									if ($event->leader->noContactOfficeHours)
										echo " &ndash; don't call during office hours";
								}
							?>
						</span>
					</p>
				<?php endif; 
				if (isset($event->backmarker)): ?>
					<p class="backmarker">
					<span class='lbl'>Backmarker:</span> <?php echo $event->backmarker->displayName; ?>
					</p>
				<?php endif;?>
			<?php elseif ($event instanceof Social):?>
				<?php if ($this->isTimeSet($event->start)):?>
				<p class="start<?php if ($event->alterations->placeTime) echo " altered\" title=\"Place & time altered"; ?>"><span>Start: </span><?php echo date("H:i", $event->start); ?></p>
				<?php if ($this->isTimeSet($event->end)):?>
				<p class="end"><span>End: </span><?php echo date("H:i", $event->end); ?> (approx)</p>
				<?php endif;
				endif; ?>
				
				<?php if ($event->location != ""): ?>
				<p class="location<?php if ($event->alterations->placeTime) echo " altered\" title=\"Place & time altered"; ?>">
					<span>Location: </span><?php if ($event->hasMap()):?><a href="#" rel="map"><a class='mobile app-link' href='geo:<?php echo $event->latLng->lat.",".$event->latLng->lng."?q=".$event->latLng->lat.",".$event->latLng->lng."(".urlencode($event->location).")";?>'>Open in map app</a><?php endif;
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
						<p class="socialbooking<?php if ($event->alterations->organiser) echo " altered\" title=\"Organiser altered"; ?>">
							<span>Contact: </span><?php echo nl2br($event->bookingsInfo);?>
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
						<p class="weekendbooking<?php if ($event->alterations->organiser) echo " altered\" title=\"Organiser altered"; ?>">
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
					<?php if ($event->hasMap() && !$this->forceMapOpen): ?>
						<p>
							<a href="#" rel="toggle-map" title="Show a map of this <?php echo strtolower($event->type);?>">Show map</a>
						</p>
						<?php if ($event instanceof WalkInstance && $event->canDownloadRoute()): ?>
							<p>
								<a href="/api/route?walkinstanceid=<?php echo $event->id;?>&amp;format=gpx" title="Download this walk in GPX format for a computer or GPS device">Download route</a>
							</p>
						<?php endif; ?>
					<?php endif; ?>
					<?php if ($this->eventInPast($event)): $attended = $event->wasAttendedBy(Jfactory::getUser()->id); ?>
						<?php if (SWG_EventsController::canSeeAttendanceCount() && $event->numAttendees > 0): // TODO: Add attendees & tickbox to past events on bottomless page?>
							<p><?php echo $event->numAttendees;if ($event->numAttendees == 1):?> person<?php else:?> people<?php endif;?> did this</p>
						<?php endif; if (SWG_EventsController::canRecordAttendance()): ?>
							<p>
								<a class="attendance" href="<?php echo JURI::current()?>?<?php echo JURI::buildQuery(array(
									"task" 	  => "attendance.attend",
									"evttype" => $event->getType(),
									"evtid"   => $event->id,
									"set"     => (int)(!$attended),
								));?>"
									><img src="/images/icons/<?php if ($attended):?>tick<?php else: ?>tickbox<?php endif;?>.png" width="19" height="16" /
								></a>
								You did this
							</p>
						<?php endif; if ($attended): ?>
							<p><a href="/your-diary/upload-track?wi=<?php echo $event->id;?>">Share GPS track</a></p>
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
		<?php endif; ?>
		<div class="description<?php if ($event->alterations->details) echo " altered\" title=\"Details altered"; ?>">
			<?php echo $event->description; ?>
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
			<?php if (!empty($detailLink)) : ?>
				<a class="detail-link" href="<?php echo $detailLink ?>">More details</a>
			<?php endif; ?>
		</div>
		
		<div style="clear:right;">&nbsp;</div>
	</div>
	</div>
</div>
