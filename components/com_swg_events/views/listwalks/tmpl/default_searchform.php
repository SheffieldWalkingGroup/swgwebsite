<?php $form = $this->get('Form'); ?>

<form name="searchwalk" action="<?php echo JRoute::_('index.php')?>" method="get" id="searchwalk" name="searchwalk">
<input type="hidden" name="option" value="com_swg_events">
<input type="hidden" name="task" value="listwalks.search" />

<h3>Route</h3>
<?php echo $form->getLabel("keywords").$form->getInput("keywords");?><br />
<?php echo $form->getLabel("location").$form->getInput("location");?><br />
<label>Filter by grades</label>
<table>
  <tbody>
    <tr>
      <td>&nbsp;</td>
      <th scope='col'>1</th>
      <th scope='col'>2</th>
      <th scope='col'>3</th>
    </tr>
    <tr>
      <th scope='row'>A</th>
      <td><?php echo $form->getInput("gradeA1")?></td>
      <td><?php echo $form->getInput("gradeA2")?></td>
      <td><?php echo $form->getInput("gradeA3")?></td>
    </tr>
    <tr>
      <th scope='row'>B</th>
      <td><?php echo $form->getInput("gradeB1")?></td>
      <td><?php echo $form->getInput("gradeB2")?></td>
      <td><?php echo $form->getInput("gradeB3")?></td>
    </tr>
    <tr>
      <th scope='row'>C</th>
      <td><?php echo $form->getInput("gradeC1")?></td>
      <td><?php echo $form->getInput("gradeC2")?></td>
      <td><?php echo $form->getInput("gradeC3")?></td>
    </tr>
  </tbody>
</table>

<h3>Accessibility</h3>
<?php echo $form->getLabel("transportPublic").$form->getInput("transportPublic");?><br />
<?php echo $form->getLabel("transportCar").$form->getInput("transportCar");?><br />

<h3>History in the SWG programme</h3>
<?php echo $form->getLabel("leader").$form->getInput("leader");?><br />

<input type="submit" class="submit" value="Search" />