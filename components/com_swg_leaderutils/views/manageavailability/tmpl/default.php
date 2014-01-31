<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.tooltip');
?>

<?php if ($this->saved): ?>
	<h3><?php echo $this->programme->description; ?></h3>
	<p>Thanks, your availability has been saved.</p>
	<p><a href="<?php echo JRoute::_('index.php')?>">Edit availability</a></p>
<?php else: ?>
	<form name="manageavailability" action="<?php echo JRoute::_('index.php')?>" method="post" id="manageavailability">
		<input type="hidden" name="view" value="manageavailability">
		<input type="hidden" name="option" value="com_swg_leaderutils">
		<input type="hidden" name="task" value="manageavailability.submit">
		<?php echo JHtml::_('form.token'); echo $this->form->getInput('id'), $this->form->getField("programmeid")->input; ?>
		
		<h3><?php echo $this->programme->description; ?></h3>
		
		<table>
			<?php $daysInWeekend = 0; // Days left in current weekend - don't repeat weekend info if this is > 0 ?>
			<?php foreach ($this->programme->dates as $date): ?>
				<tr class="<?php echo $this->dayClass($date); ?>">
					<td>
						<?php echo $this->getField($date)->input; ?>
					</td>
					<td colspan="<?php echo ($this->isWeekendAway($date) ? 1 : 2) ?>">
						<?php echo $this->getField($date)->label; ?>
					</td>
					<?php if ($this->isWeekendAway($date) && $daysInWeekend == 0): ?>
						<td rowspan="<?php $daysInWeekend = $this->weekendLength($date); echo $daysInWeekend;?>">
							<?php echo $this->weekendInfo($date); ?>
						</td>
					<?php endif; if ($daysInWeekend > 0) $daysInWeekend--; ?>
				</tr>
			<?php endforeach; ?>
		</table>
		<input name="submit" type="submit" value="Submit" />
	</form>
<?php endif; ?>