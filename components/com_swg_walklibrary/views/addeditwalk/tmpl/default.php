<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.tooltip');
?>
<h3><?php if ($this->editing):?>Edit<?php else:?>Add<?php endif;?> walk</h3>
<?php if ($this->showForm): ?>
    <p>Please enter the details of your planned walk here. This will add it to the walk library; adding it to the walks programme is the next step</p>
	<form name="addeditwalk" action="<?php echo JRoute::_('index.php')?>" method="post" id="addeditwalk" enctype="multipart/form-data">
		<input type="hidden" name="view" value="addeditwalk">
		<input type="hidden" name="option" value="com_swg_walklibrary">
		<input type="hidden" name="task" value="addeditwalk.submit">
		<?php echo JHtml::_('form.token'); echo $this->form->getInput('id'); ?>
		<fieldset>
			<legend>Publicity</legend>
			<?php 
				echo $this->form->getLabel('name');
				echo $this->form->getInput('name');
				echo "<br>";
				echo $this->form->getLabel('description');
				echo $this->form->getInput('description');
				if ($this->controller->canEditAll())
				{
					echo "<br>";
					echo $this->form->getLabel('suggestedBy');
					echo $this->form->getInput('suggestedBy');
				}
			?>
		</fieldset>
		<fieldset>
			<legend>Route</legend>
			<p>
				If you have a GPX route or track for this walk, upload it here. If not, skip this step.<br>
				Lots of route planning software can generate GPX files, including OS Maps with a premium subscription: look for a 'Save' or 'Export' function and make sure you select GPX. You can also get a GPX track by recceing the route with a GPS logger that supports it.<br>
				The GPX file is used to fill in some of the values in this form, and if you want you can display it on the route map.
			</p>
			<?php
				echo $this->form->getLabel('route');
				echo $this->form->getInput('route');
				if (isset($this->walk->route))
					echo "<p>Uploaded route with ".$this->walk->route->numWaypoints()." waypoints</p>";
				
				echo "<p>";
				echo $this->form->getLabel('routeVisibility');
				echo $this->form->getInput('routeVisibility');
				echo "</p>"
			?>
			<input name="upload" type="submit" class="submit gpx-upload" value="Upload" />
			<input name="clearroute" type="submit" class="submit gpx-clear" value="Clear route" />
		</fieldset>
		<fieldset>
		<legend>Location</legend>
		<p>Where does the walk start and finish? You can enter grid references (remember the letters), or use the map. Click "Place here" to add a start or end point to the centre of the map; you can then drag this into place. It will try to update the grid reference and place name automatically. If you use the search function, it can sometimes help to add the type of location, e.g. "Rambler Inn" instead of "The Rambler".</p>
		<?php
			$linear=$this->form->getField("isLinear"); 
			echo $linear->label;
			echo $linear->input;
		?>
			
			<div class="clear"><!-- --></div>
        <fieldset>
            <legend>Map</legend>
            <input type="button" id="start_place" value="Place start marker" title="Place the start marker at the centre of the map. You can drag the marker into place." />
            <input type="button" id="end_place" value="Place end marker" title="Place the end marker at the centre of the map. You can drag the marker into place." />
		<?php 
			$map = $this->form->getField("locMap");
			// Moronic Joomla design means I apparently can't attach this data in the model, because if I instantiate the field before displaying the form it's just destroyed and recreated. GENIUS.
			if (!empty($this->walk->route))
				$map->attachRoute($this->walk->route);
			echo $map->input;
		?>
            <fieldset class="walklocationgroup">
				<legend>Start</legend>
				<?php
					$placeName = $this->form->getField("startPlaceName");
					$gridRef = $this->form->getField("startGridRef");
					echo $gridRef->label.$gridRef->input."<br />";
					echo $placeName->label.$placeName->input."<span class='loading'></span><br />";
				?>
			</fieldset>
			<fieldset class="walklocationgroup">
				<legend>End</legend>
				<?php
					$placeName = $this->form->getField("endPlaceName");
					$gridRef = $this->form->getField("endGridRef");
					echo $gridRef->label.$gridRef->input."<br />";
					echo $placeName->label.$placeName->input."<span class='loading'></span><br />";
				?>
			</fieldset>
		</fieldset>
		<fieldset>
			<legend>The route itself</legend>
			<?php 
				foreach ($this->form->getFieldset("route") as $field)
				{
					if ($field->hidden)
						echo $field->input;
					else
					{
						echo $field->label;
						echo $field->input;
						echo "<br>";
					}
				}
			?>
			<p>Please select these options whether or not you want dogs or pushchairs on your walk: you can choose this separately. This means that if someone else wants to lead your walk and allow dogs, they can do so.</p>
		</fieldset>
		<input name="save" type="submit" class="gpx-upload" value="Save" />
		<input name="reset" type="reset" value="Undo changes" />
	</form>
<?php endif; ?>
