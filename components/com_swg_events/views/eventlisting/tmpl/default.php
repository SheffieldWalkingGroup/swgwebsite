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
<?php endif;
foreach ($this->events as $event):?>
	<?php 
		if ($event->getType() != Event::TypeDummy)
			$detailLink = "/event?type=".$event->type."&amp;id=".$event->id;
		else
			$detailLink = null;
		include JPATH_SITE."/components/com_swg_events/helpers/tmpl/eventinfo.php";
		// Display any relevant protocol reminders after each event
		if (!empty($this->protocolReminders) && is_array($this->protocolReminders))
		{
			if (count($this->protocolReminders) < $nextProtocolReminder+1)
				$nextProtocolReminder = 0;
			?><p class="protocolreminder"><?php echo $this->protocolReminders[$nextProtocolReminder++]['text']; ?></p><?php 
		}
endforeach;
