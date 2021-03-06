<?php if (!empty($module->title)) echo "<h3><a href='".$listPage."'>".$module->title."</a></h3>" ?>
<div class="events box socials">
  <ul>
    <?php foreach ($events as $event) {?>
      <li id="social_<?php echo $event->id; ?>" class="<?php if ($event->alterations->cancelled) echo "cancelled"; if ($event->alterations->anyAlterations()) echo " altered";?>">
        <span class="date<?php if ($event->alterations->date) echo " altered"?>"><?php echo date("l jS M", $event->start); ?></span>
        <h4>
          <a href="<?php echo $listPage."#social_".$event->id?>" class="eventinfopopup<?php if ($newMembers) echo " newmembers"; ?>" rel="social_<?php echo $event->id; ?>"><?php echo $event->name; ?></a>
        </h4>
      </li>
    <?php } ?>
  </ul>
  <?php if ($showMoreLink): ?>
    <p><a href="<?php echo $listPage;?>" class="more" title="All upcoming socials">More</a></p>
  <?php endif; ?>
</div>
