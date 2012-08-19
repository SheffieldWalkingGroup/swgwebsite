<?php $form = $this->get('Form'); ?>

<form name="searchwalk" action="<?php echo JRoute::_('index.php')?>" method="get" id="searchwalk" name="searchwalk">
<input type="hidden" name="option" value="com_swg_events">
<input type="hidden" name="task" value="listwalks.search" />
<?php
// Iterate through the fields and display them.
foreach($form->getFieldset() as $field):
    // If the field is hidden, only use the input.
    if ($field->hidden):
        echo $field->input;
    else:
    ?>
    <dt>
        <?php echo $field->label; ?>
    </dt>
    <dd>
        <?php echo $field->input ?>
    </dd>
    <?php
    endif;
endforeach;
?>
<input type="submit" class="submit" value="Search" />