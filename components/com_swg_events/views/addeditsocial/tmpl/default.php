<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.tooltip');

if ($this->showForm): ?>
	<form name="addeditsocial" action="<?php echo JRoute::_('index.php')?>" method="post" id="addeditsocial" class="form-validate">
		<input type="hidden" name="view" value="addeditsocial">
		<input type="hidden" name="option" value="com_swg_events">
		<input type="hidden" name="task" value="addeditsocial.submit">
		
		<input type="hidden" name="jform[okToPublish]" value="0">
		<input type="hidden" name="jform[shownormal]" value="0">
		<input type="hidden" name="jform[shownewmember]" value="0">
		
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