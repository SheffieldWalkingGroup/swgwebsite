<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

// load tooltip behavior
JHtml::_('behavior.tooltip');
?>
<form action="<?php echo JRoute::_('index.php?option=com_swg_events'); ?>" method="post" name="adminForm">
	<table class="adminlist">
		<thead>HEAD</thead>
		<tfoot>FOOT</tfoot>
		<tbody>BODY</tbody>
	</table>
</form>