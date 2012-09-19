<?php
require_once JPATH_BASE."/../swg/swg.php";
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
?>
<?php foreach($this->items as $i => $item): ?>
	<tr class="row<?php echo $i % 2; ?>">
		<td>
			<?php echo $item->id; ?>
		</td>
		<td>
			<?php echo $item->text; ?>
		</td>
		<td>
			<?php echo ucfirst(SWG::printableEventType($item->eventtype))."s"; ?>
		</td>
		<td>
		
		</td>
	</tr>
<?php endforeach; ?>