<?php 
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
?>
<h3><?php echo $this->walk->name; ?></h3>
<p><?php echo $this->walk->description; ?>
<dl>
  <dt>General area</dt>
    <dd><?php echo $this->walk->location ?></dd>
  <dt>Transport access</dt>
    <dd>
      <?php
      if ($this->walk->transportByCar && $this->walk->transportPublic)
        echo "Car or public transport";
      else if ($this->walk->transportByCar)
        echo "Car only";
      else if ($this->walk->transportPublic)
        echo "Public transport";
      else 
        echo "Not specified";
      ?>
    </dd>
  <dt>Shape</dt>
    <dd><?php 
      if ($this->walk->isLinear) 
        echo "Linear"; 
      else 
        echo "Circular"; 
    ?></dd>
  <dt>Start</dt>
    <dd><?php echo $this->walk->startGridRef.": ".$this->walk->startPlaceName; ?></dd>
  <?php if (isset($this->walk->endGridRef)): ?>
    <dt>End</dt>
      <dd><?php echo $this->walk->endGridRef.": ".$this->walk->endPlaceName; ?></dd>
  <?php endif; ?>
    <dt>Distance</dt>
      <dd><?php echo $this->walk->miles; ?> miles</dd>
    <dt>Grade</dt>
      <dd><?php echo $this->walk->distanceGrade.$this->walk->difficultyGrade; ?></dd>
    <dt>Suitable for dogs</dt>
      <dd><?php if ($this->walk->dogFriendly)
        echo "Yes";
      else
        echo "No";
      ?></dd>
    <dt>Suitable for pushchairs</dt>
      <dd><?php if ($this->walk->childFriendly)
        echo "Yes";
      else
        echo "No";
      ?></dd>
    <dt>Extra information</dt>
      <dd><?php echo $this->walk->information; ?></dd>
    <!-- TODO: File links? route image? -->
    <dt>Suggested by</dt>
		<dd>
			<?php if (!empty($this->walk->suggestedBy)): ?>
				<?php echo $this->walk->suggestedBy->displayName;?>
			<?php else: ?>
				Unknown
			<?php endif; ?>
		</dd>
	</dt>
</dl>

<h4>Map</h4>
<div id="map" style="width:100%;height:400px;"></div>

<h4>History</h4>
<?php if (count($this->walkInstances)): ?>
  <table>
    <thead>
      <tr>
        <th scope="col">Date</th>
        <th scope="col">Published name</th>
        <th scope="col">Led by</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($this->walkInstances as $wi): ?>
        <tr>
          <td><?php echo date("l jS F Y",$wi->start);?></td>
          <td><?php echo $wi->name; ?></td>
          <td><?php echo $wi->leader->displayName; ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php else: ?>
  <p>This walk has never been led.</p>
<?php endif;?>
<?php if ($this->canEdit):?><a href="<?php echo $this->urlToEdit($this->walk); ?>">Edit</a><?php endif;?>