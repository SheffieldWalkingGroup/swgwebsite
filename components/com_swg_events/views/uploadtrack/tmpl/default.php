<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.tooltip');
?>

<h3>Upload Walk Track</h3>
<h4><?php echo $this->wi->name;?></h4>
<p><?php echo date("l jS F Y",$this->wi->start);?></p>
<p>Upload a track of the walk that you've recorded with a GPS device. Make sure the track is in GPX format and contains the whole walk with no extra parts.</p>
<form name="uploadtrack" action="<?php echo JRoute::_('index.php')?>" method="post" id="addeditweekend" class="form-validate" enctype="multipart/form-data">
	<input type="hidden" name="view" value="uploadtrack">
	<input type="hidden" name="option" value="com_swg_events">
	<input type="hidden" name="task" value="uploadtrack.upload">
	<input type="hidden" name="wi" value="<?php echo $this->wi->id;?>">
	<?php echo JHtml::_('form.token'); echo $this->form->getInput('id'); ?>
	
	<?php echo $this->form->getLabel("file"); echo $this->form->getInput("file");?>
	
	<input name="upload" type="submit" class="submit gpx-upload" value="Upload" />
	
	<div id="map" style="width:100%;height:400px;"></div>
	
	<?php if ($this->gotTrack):?><input name="submit" type="submit" class="submit" value="Save" /><?php endif; ?>
</form>