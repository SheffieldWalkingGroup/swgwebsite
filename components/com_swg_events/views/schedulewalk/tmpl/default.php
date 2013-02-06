<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.tooltip');

if ($this->showForm): ?>
	<form name="schedulewalk" action="<?php echo JRoute::_('index.php')?>" method="post" id="schedulewalk" class="form-validate">
		<input type="hidden" name="view" value="schedulewalk">
		<input type="hidden" name="option" value="com_swg_events">
		<input type="hidden" name="task" value="schedulewalk.submit">
		
		<input type="hidden" name="jform[okToPublish]" value="0">
		<input type="hidden" name="jform[dogFriendly]" value="0">
		<input type="hidden" name="jform[childFriendly]" value="0">
		<input type="hidden" name="jform[speedy]" value="0">
		<input type="hidden" name="jform[challenge]" value="0">
		<input type="hidden" name="jform[alterations[details]]" value="0">
		<input type="hidden" name="jform[alterations[cancelled]]" value="0">
		<input type="hidden" name="jform[alterations[placeTime]]" value="0">
		<input type="hidden" name="jform[alterations[organiser]]" value="0">
		<input type="hidden" name="jform[alterations[date]]" value="0">
		
		<?php echo JHtml::_('form.token'); echo $this->form->getInput('id'); ?>
		<?php foreach ($this->form->getFieldsets() as $fieldset): ?>
			<fieldset>
				<legend><?php echo $fieldset->label;?></legend>
				<?php foreach ($this->form->getFieldset($fieldset->name) as $field):?>
					<div>
						<?php echo $field->label; ?>
						<?php echo $field->input; ?>
					</div>
				<?php endforeach; ?>
			</fieldset>
		<?php endforeach; ?>
		<input type="submit" class="submit" value="Save" />
	</form>
<?php endif; ?>