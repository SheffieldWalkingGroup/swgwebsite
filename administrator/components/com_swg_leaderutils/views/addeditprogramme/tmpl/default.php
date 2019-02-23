<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.tooltip');
?>
<h3><?php if ($this->editing):?>Edit<?php else:?>Add<?php endif;?> walk programme</h3>
<?php if ($this->showForm): ?>
	<form name="addeditprogramme" action="<?php echo JRoute::_('index.php')?>" method="post" id="addeditprogramme">
		<input type="hidden" name="view" value="addeditprogramme">
		<input type="hidden" name="option" value="com_swg_leaderutils">
		<input type="hidden" name="task" value="addeditprogramme.submit">
		<?php echo JHtml::_('form.token'); if ($this->editing) echo $this->form->getInput('id'); ?>
		<?php foreach ($this->form->getFieldsets() as $fieldset):?>
            <fieldset>
                <legend><?php echo $fieldset->label; ?></legend>
                <?php foreach ($this->form->getFieldset($fieldset->name) as $field): ?>
                    <?php echo $field->label; ?>
                    <?php echo $field->input; ?>
                <?php endforeach; ?>
            </fieldset>
        <?php endforeach; ?>
        <input type="submit" class="submit" value="Save" />
		<input name="reset" type="reset" value="Undo changes" />
	</form>
<?php endif; ?>
