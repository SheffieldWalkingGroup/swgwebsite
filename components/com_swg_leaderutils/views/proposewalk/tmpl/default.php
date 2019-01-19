<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.tooltip');
?>

<?php if ($this->saved): ?>
    <h3>Edit walk proposal: <?php echo $this->programme->description; ?></h3>
    <p>Thanks, your availability has been saved.</p>
    <p><a href="<?php echo JRoute::_('index.php')?>">Edit availability</a></p>
<?php else: ?>

    <h3>Propose to lead a walk in <?php echo $this->programme->description; ?></h3>

    <?php if ($this->walkInstance): ?>
        <?php $event = $this->walkInstance; include JPATH_SITE."/components/com_swg_events/helpers/tmpl/eventinfo.php";?>

            
        <form name="proposewalk" action="<?php echo JRoute::_('index.php')?>" method="post" id="proposewalk">
            <input type="hidden" name="view" value="proposewalk">
            <input type="hidden" name="option" value="com_swg_leaderutils">
            <input type="hidden" name="task" value="proposewalk.submit">
            <input type="hidden" name="jform[walkid]" value="<?php echo $this->walk->id?>">
            <?php echo JHtml::_('form.token'); echo $this->form->getInput('id'), $this->form->getField("programmeid")->input; ?>
            
            <?php if ($this->canChooseLeader()): ?>
                <fieldset>
                    <legend>Leader</legend>
                    <?php foreach ($this->form->getFieldset('leader') as $field): ?>
                    <div>
                        <?php echo $field->label; ?>
                        <?php echo $field->input; ?>
                    </div>
                <?php endforeach; ?>
                </fieldset>
            <?php endif; ?>
            
            <fieldset>
                <legend>When are you available for walk leading?</legend>
                <p>These options mark certain day(s) available every week through the programme. You can then mark individual days by clicking in the calendar.</p>
                <dl>
                    <dt><?php echo $this->form->getField('basicavailability')->label; ?></dt>
                    <dd><?php echo $this->form->getField('basicavailability')->input; ?></dd>
                    
                    <dt><?php echo $this->form->getField('weekdays')->label; ?></dt>
                    <dd><?php echo $this->form->getField('weekdays')->input; ?></dd>
                </dl>
                            
                <h4>Key</h4>
                <dl class='availabilitykey'>
                    <dt class='no'>&nbsp;</dt>
                    <dd>I can't lead a walk on this day</dd>
                    <dt class='maybe'>&nbsp;</dt>
                    <dd>I can lead on this/these day(s)</dd>
                    <dt class='yes'>&nbsp;</dt>
                    <dd>This/these would be particularly good day(s) to lead</dd>
                </dl>
                <?php echo $this->form->getField('availability')->input; ?>
                
            </fieldset>
            <fieldset>
                <legend>Other details</legend>
                <p>If you're travelling by public transport, remember to check if the times are different on different days.</p>
                <?php foreach ($this->form->getFieldset('details') as $field): ?>
                    <div>
                        <?php echo $field->label; ?>
                        <?php echo $field->input; ?>
                    </div>
                <?php endforeach; ?>
            </fieldset>
            <input type="submit" class="submit" value="Save" />
            
        </form>
    <?php else: /* if ($this->walkInstance) */ ?>
        <form name="proposewalk_select" action="<?php echo JRoute::_('index.php')?>" method="get">
        <p>Which walk do you want to lead? (You can also select a walk from the walks library or your own walks)</p>
        <?php foreach ($this->form->getFieldset('walk') as $field): ?>
            <div>
                <?php echo $field->label; ?>
                <?php echo $field->input; ?>
            </div>
        <?php endforeach; ?>
        <input type="submit" class="submit" value="Continue" />
    <?php endif ?>
<?php endif ?>
