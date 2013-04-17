<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.tooltip');

if ($this->showForm): ?>
	<form name="addeditwalk" action="<?php echo JRoute::_('index.php')?>" method="post" id="addeditwalk" name="addeditwalk" enctype="multipart/form-data">
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
			?>
		</fieldset>
		<fieldset>
			<legend>Route</legend>
			<p>
				If you have a GPX route or track for this walk, upload it here. 
				You can get a GPX route by using various route planning software 
				or websites (e.g.  <a href="http://bikehike.co.uk/index.php">bikehike.co.uk</a>),
				or you can get a GPX track by walking the route with a GPS logger
				and exporting the data to GPX format. 
				The GPX file is used to fill in some of the values in this form,
				and if you want you can display it on the route map.
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
		</fieldset>
		<fieldset>
		<legend>Location</legend>
		<?php
			$linear=$this->form->getField("isLinear"); 
			echo $linear->label;
			echo $linear->input;
		?>
			<fieldset class="walklocationgroup">
				<legend>Start</legend>
				<?php
					$placeName = $this->form->getField("startPlaceName");
					$gridRef = $this->form->getField("startGridRef");
					echo $gridRef->label.$gridRef->input."<br />";
					echo $placeName->label.$placeName->input."<br />";
				?>
				<input type="button" id="start_place" value="Place here" title="Place the start marker at the centre of the map. You can drag the marker into place." />
			</fieldset>
			<fieldset class="walklocationgroup">
				<legend>End</legend>
				<?php
					$placeName = $this->form->getField("endPlaceName");
					$gridRef = $this->form->getField("endGridRef");
					echo $gridRef->label.$gridRef->input."<br />";
					echo $placeName->label.$placeName->input."<br />";
				?>
				<input type="button" id="end_place" value="Place here" title="Place the end marker at the centre of the map. You can drag the marker into place." />
			</fieldset>
			<div class="clear"><!-- --></div>
		<?php 
			$map = $this->form->getField("locMap");
			// Moronic Joomla design means I apparently can't attach this data in the model, because if I instantiate the field before displaying the form it's just destroyed and recreated. GENIUS.
			if (!empty($this->walk->route))
				$map->attachRoute($this->walk->route);
			echo $map->input;
		?>
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
		</fieldset>
		<input name="save" type="submit" class="gpx-upload" value="Save" />
		<input name="reset" type="reset" value="Undo changes" />
	</form>
<?php endif; ?>