<div class="box">
  <ul>
    <?php foreach ($events as $event) {?>
      <li class="<?php if ($event->isCancelled()) echo "cancelled";?>">
        <span class="date"><?php echo date("l jS M", strtotime($event->startDate)); ?></span>
        <h4>
          <a href="#"><?php echo $event->name; ?>&nbsp;(<?php echo $event->distanceGrade.$event->difficultyGrade;?>)</a>
        </h4>
      </li>
    <?php } ?>
  </ul>
  <p><a href="#" class="more">More</a></p>
</div>