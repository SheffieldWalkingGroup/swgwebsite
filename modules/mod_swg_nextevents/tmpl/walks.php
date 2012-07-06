<div class="events box walks">
  <ul>
    <?php foreach ($events as $event) {?>
      <li class="<?php if ($event->alterations->cancelled) echo "cancelled"; if ($event->alterations->anyAlterations()) echo " altered";?>">
        <span class="date<?php if ($event->alterations->date) echo " altered"?>"><?php echo date("l jS M", $event->start); ?></span>
        <h4>
          <a href="<?php echo $listPage."#walkNo".$event->id?>" class="eventinfopopup" rel="walk_<?php echo $event->id; ?>"><?php echo $event->name; ?>&nbsp;(<?php echo $event->distanceGrade.$event->difficultyGrade;?>)</a>
        </h4>
      </li>
    <?php } ?>
  </ul>
  <p><a href="<?php echo $listPage;?>" class="more">More</a></p>
</div>
