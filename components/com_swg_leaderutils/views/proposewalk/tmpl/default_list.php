<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.tooltip');
?>

<?php if ($this->saved): ?>
    <h3>Edit walk proposal: <?php echo $this->programme->description; ?></h3>
    <p>Thanks, your availability has been saved. The vice chair will let you know when the programme is put together. Please contact the club if you need to change your dates.</p>
    <p><a href="<?php echo JRoute::_('index.php')?>">Edit availability</a></p>
<?php endif; ?>

<h3>Your walk proposals for <?php echo $this->programme->description; ?></h3>
<?php echo $this->introText; ?>
<?php if (count($this->proposals) == 0): ?>
    <p>You haven't proposed any walks for this programe yet.</p>
<?php else: ?>
    <?php foreach ($this->proposals as $proposal): ?>
        <h4><?php echo $proposal->walk->name;?></h4>
        <p><?php echo $proposal->getAvailableDates(); ?></p>
    <?php endforeach; ?>
<?php endif ?>
