<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

?>

<h1>Walks Programmes</h1>
<a href="?option=com_swg_leaderutils&amp;view=AddEditProgramme">Add programme</a>
<table>
    <thead>
        <tr>
            <th>Special</th>
            <th>Start date</th>
            <th>End date</th>
            <th>Title</th>
            <th colspan='3'>Options</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($this->programmes as $programme): ?>
            <tr>
                <td><?php if ($programme->special): ?>Special<?php else: ?>&nbsp;<?php endif; ?></td>
                <td><?php echo date("Y-m-d", $programme->startDate); ?></td>
                <td><?php echo date("Y-m-d", $programme->endDate); ?></td>
                <td><?php echo $programme->title; ?></td>
                <td><?php if ($programme->id == WalkProgramme::getCurrentProgrammeID()): ?>Current<?php else: ?>&nbsp;<?php endif ?></td>
                <td><?php if ($programme->id == WalkProgramme::getNextProgrammeID()): ?>Next<?php else: ?>&nbsp;<?php endif ?></td>
                <td><a href="?option=com_swg_leaderutils&amp;view=AddEditProgramme&amp;id=<?php echo $programme->id;?>">Edit</a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
