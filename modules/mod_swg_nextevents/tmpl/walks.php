
<?php if (!empty($module->title)) echo "<h3><a href='".$listPage."'>".$module->title."</a></h3>"; ?>
<div class="events walks">
  <ul>
    <?php foreach ($events as $event):?>
      <li id="walk_<?php echo $event->id; ?>" class="<?php if ($event->alterations->cancelled) echo "cancelled"; else if ($event->alterations->anyAlterations()) echo " altered";?>">
        <span class="date<?php if ($event->alterations->date) echo " altered"?>"><?php echo date("l jS M", $event->start); ?></span>
        <h4>
          <a href="<?php echo $listPage."#walk_".$event->id?>" class="eventinfopopup" rel="walk_<?php echo $event->id; ?>"><?php echo $event->name; ?></a><span class="rating">&nbsp;(<?php echo $event->distanceGrade.$event->difficultyGrade;?>)</span>
        </h4>
      </li>
    <?php endforeach; ?>
  </ul>
  <?php if ($showMoreLink): ?>
    <p><a href="<?php echo $listPage;?>" class="more" title="All upcoming walks">More</a></p>
  <?php endif; ?>
</div>
