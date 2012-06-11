<div class="events box weekends">
  <ul>
    <?php foreach ($events as $event) {?>
      <li>
        <span class="date">
          <?php 
            // If the start and end are in the same month, just output the date of the start
            // and get the month from the end (e.g. 20th-22nd Apr)
            // If they're in different months, display the month for each.
            if (date("M",$event->startDate == date("M",$event->endDate))) {
             echo date("jS", $event->startDate);
            } else {
              echo date("jS M", $event->startDate);
            }
            echo "-".date("jS M", $event->endDate);
          ?>
        </span>
        <h4><a href="<?php echo $listPage."#weekendNo".$event->id?>" class="eventinfopopup" rel="weekend_<?php echo $event->id; ?>"><?php echo $event->name; ?></a></h4>
        <p>(<?php echo $event->area; ?>)</p>
        <p>Booking opens <?php echo $event->bookingsOpen; ?></p>
      </li>
    <?php } ?>
  </ul>
  <p><a href="<?php echo $listPage;?>" class="more">More</a></p>
</div>
