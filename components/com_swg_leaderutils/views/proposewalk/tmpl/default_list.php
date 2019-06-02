<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.tooltip');
?>

<?php if ($this->saved): ?>
    <h3>Edit walk proposal: <?php echo $this->programme->title; ?></h3>
    <p>Thanks, your availability has been saved. The vice chair will let you know when the programme is put together. Please contact the club if you need to change your dates.</p>
    <p><a href="<?php echo JRoute::_('index.php')?>">Edit availability</a></p>
<?php endif; ?>

<h3>Your walk proposals for <?php echo $this->programme->title; ?></h3>
<?php echo $this->introText; ?>
<?php if (count($this->proposals) == 0): ?>
    <p>You haven't proposed any walks for this programe yet.</p>
<?php else: ?>
    <?php foreach ($this->proposals as $proposal): ?>
        <h4><?php echo $proposal->walk->name;?></h4>
        <p><?php echo $proposal->getDateSummary(); ?></p>
        <?php if ($proposal->isInProgramme()): ?>
            <p>In the <?php if (!$proposal->walkInstance->okToPublish):?>draft <?php endif;?>programme for <?php echo strftime('%A %e %B', $proposal->walkInstance->start);?>. Please <a href='mailto:sheffieldwalkinggroup@hotmail.com'>email the vice chair</a> ASAP if you need to make changes.</p>
        <?php else: ?>
            <p>
                <?php if ($proposal->walk->leader == Leader::fromJoomlaUser(JFactory::getUser()->id)): ?>[<a href='../your-walks?view=addeditwalk&amp;walkid=<?php echo $proposal->walk->id;?>'>Edit walk</a>]<?php endif;?>
                [<a href='?proposal=<?php echo $proposal->id;?>'>Edit dates</a>]
                <?php /*[TODO: Delete option]*/ ?>
            </p>
        <?php endif; ?>
    <?php endforeach; ?>
<?php endif ?>
