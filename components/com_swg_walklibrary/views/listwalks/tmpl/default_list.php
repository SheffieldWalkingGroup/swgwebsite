<table>
<thead>
<tr>
<td>&nbsp;</td>
<th scope="col">ID</th>
<th scope="col">Name</th>
<th scope="col">Grade</th>
</tr>
</thead>
<tbody>
  <?php foreach ($this->walks as $walk):?>
    <tr>
      <td>
        <a href="<?php echo $this->urlToView($walk); ?>">View</a> 
        <?php if ($this->controller->canEdit($walk)): ?>
			<a href="<?php echo $this->urlToEdit($walk); ?>">Edit</a>
		<?php endif; ?>
      </td>
      <td><?php echo $walk->id;?></td>
      <td><?php echo $walk->name; ?></td>
      <td><?php echo $walk->distanceGrade.$walk->difficultyGrade ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>