<div class="events box walks">
  <ul>
    <?php foreach ($events as $event) {?>
      <li class="<?php if ($event->isCancelled()) echo "cancelled";?>">
        <span class="date"><?php echo date("l jS M", $event->startDate); ?></span>
        <h4>
          <a href="<?php echo $listPage."#walkNo".$event->id?>" class="eventinfopopup" rel="walk_<?php echo $event->id; ?>"><?php echo $event->name; ?>&nbsp;(<?php echo $event->distanceGrade.$event->difficultyGrade;?>)</a>
        </h4>
      </li>
    <?php } ?>
  </ul>
  <p><a href="<?php echo $listPage;?>" class="more">More</a></p>
</div>
<div class="event popup walk" id="walk-popup" style="display:none;">
   <div class="eventheader">
      <span class="date"></span>
      <span class="rating"></span>
      <h3></h3>
    </div>
    <div class="description">
      <p></p>
      <p class="icons"></p>
    </div>
    <div class="eventinfo">
      <p class="start">
        <span>Start:</span>
          <a href="" title="Streetmap view of approximate location"></a>
      </p>
      <p class="end">
        <span>End:</span>
      </p>
      <p class="transport">
        <span>Transport:</span> TODO: Transport
      </p>
      <p class="leader">
        <span>Walk Leader:</span> TODO: Leader
      </p>
      <p class="backmarker">
        <span>Backmarker:</span> TODO: Backmarker
      </p>
    </div>
    <div style="clear:right;">&nbsp;</div>
  </div>