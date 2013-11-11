<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.tooltip');

if ($this->showForm): ?>
	<form name="addeditweekend" action="<?php echo JRoute::_('index.php')?>" method="post" id="addeditweekend" class="form-validate">
		<input type="hidden" name="view" value="addeditweekend">
		<input type="hidden" name="option" value="com_swg_events">
		<input type="hidden" name="task" value="addeditweekend.submit">
		
		<input type="hidden" name="jform[okToPublish]" value="0">
		<input type="hidden" name="jform[noContactOfficeHours]" value="0">
		<input type="hidden" name="jform[challenge]" value="0">
		<input type="hidden" name="jform[swg]" value="0">
		<?php echo JHtml::_('form.token'); echo $this->form->getInput('id'); ?>

		<fieldset>
			<legend>Basic</legend>
			<?php foreach ($this->form->getFieldset('basic') as $field): ?>
				<div>
					<?php echo $field->label; ?>
					<?php echo $field->input; ?>
				</div>
			<?php endforeach; ?>
		</fieldset>
		<fieldset>
			<legend>Booking</legend>
			<?php foreach ($this->form->getFieldset('booking') as $field): ?>
				<div>
					<?php echo $field->label; ?>
					<?php echo $field->input; ?>
				</div>
			<?php endforeach; ?>
		</fieldset>
		<fieldset>
			<legend>Details</legend>
			<?php foreach ($this->form->getFieldset('details') as $field): ?>
				<div>
					<?php echo $field->label; ?>
					<?php echo $field->input; ?>
				</div>
			<?php endforeach; ?>
		</fieldset>
		<fieldset>
			<legend>Alterations</legend>
			<?php foreach ($this->form->getFieldset('alterations') as $field): ?>
				<div>
					<?php echo $field->label; ?>
					<?php echo $field->input; ?>
				</div>
			<?php endforeach; ?>
		</fieldset>
		<input type="submit" class="submit" value="Save" />
	</form>
<?php endif; ?>