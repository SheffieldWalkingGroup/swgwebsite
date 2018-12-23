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
		
		<fieldset>
			<legend>When are you available for walk leading?</legend>
			<h4>Key</h4>
			<dl class='availabilitykey'>
                <dt class='no'>&nbsp;</dt>
                <dd>Not available</dd>
                <dt class='yes'>&nbsp;</dt>
                <dd>Available</dd>
                <dt class='maybe'>&nbsp;</dt>
                <dd>Available if necessary</dd>
            </dl>
			<?php foreach ($this->form->getFieldset('yourdates') as $field): ?>
				<div>
					<?php echo $field->label; ?>
					<?php echo $field->input; ?>
				</div>
			<?php endforeach; ?>
		</fieldset>
		<fieldset>
			<legend>What walks do you want to lead?</legend>
			<?php foreach ($this->form->getFieldset('yourwalks') as $field): ?>
				<div>
					<?php echo $field->label; ?>
					<?php echo $field->input; ?>
				</div>
			<?php endforeach; ?>
		</fieldset>
		<fieldset>
			<legend>Do you want to backmark?</legend>
			<?php foreach ($this->form->getFieldset('backmarking') as $field): ?>
				<div>
					<?php echo $field->label; ?>
					<?php echo $field->input; ?>
				</div>
			<?php endforeach; ?>
		</fieldset>
		<input type="submit" class="submit" value="Save" />
		
	</form>
<?php endif; ?>
