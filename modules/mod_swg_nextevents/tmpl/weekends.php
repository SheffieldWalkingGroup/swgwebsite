<?php if (!empty($module->title)) echo "<h3><a href='".$listPage."'>".$module->title."</a></h3>" ?>
<div class="events weekends">
  <ul>
    <?php foreach ($events as $event) {?>
      <li id="weekend_<?php echo $event->id; ?>" class="<?php if ($event->alterations->cancelled) echo "cancelled"; if ($event->alterations->anyAlterations()) echo " altered";?>">
        <span class="date<?php if ($event->alterations->date) echo " altered"?>">
          <?php 
            // If the start and end are in the same month, just output the date of the start
            // and get the month from the end (e.g. 20th-22nd Apr)
            // If they're in different months, display the month for each.
            if (date("M",$event->start == date("M",$event->endDate))) {
             echo date("jS", $event->start);
            } else {
              echo date("jS M", $event->start);
            }
            echo "-".date("jS M", $event->endDate);
          ?>
        </span>
        <span class="area"><?php echo $event->area; ?></span>
        <h4><a href="<?php echo $listPage."#weekend_".$event->id?>" class="eventinfopopup" rel="weekend_<?php echo $event->id; ?>"><?php echo $event->name; ?></a></h4>
        <p>Booking opens <?php echo $event->bookingsOpen; ?></p>
      </li>
    <?php } ?>
  </ul>
  <?php if ($showMoreLink): ?>
    <p><a href="<?php echo $listPage;?>" class="more" title="All upcoming weekends">More</a></p>
  <?php endif; ?>
</div>
