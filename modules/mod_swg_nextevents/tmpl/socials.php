<div class="events box socials">
  <ul>
    <?php foreach ($events as $event) {?>
      <li>
        <span class="date"><?php echo date("l jS M", strtotime($event->startDate)); ?></span>
        <h4>
          <a href="#" class="eventinfopopup" rel="social_<?php echo $event->id; ?>"><?php echo $event->name; ?></a>
        </h4>
      </li>
    <?php } ?>
  </ul>
  <p><a href="#" class="more">More</a></p>
</div>
