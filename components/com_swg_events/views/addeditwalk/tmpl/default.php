<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.tooltip');

if ($this->showForm): ?>
  <form name="addeditwalk" action="<?php echo JRoute::_('index.php')?>" method="post" id="addeditwalk" name="addeditwalk" enctype="multipart/form-data">
  <input type="hidden" name="option" value="com_swg_events">
  <input type="hidden" name="task" value="addeditwalk.submit">
  <?php echo JHtml::_('form.token'); ?>
    <fieldset>
      <legend>Publicity</legend>
      <?php 
        echo $this->form->getLabel('name');
        echo $this->form->getInput('name');
        echo "<br>";
        echo $this->form->getLabel('routeDescription');
        echo $this->form->getInput('routeDescription');
      ?>
    </fieldset>
    <fieldset>
      <legend>GPX</legend>
      <p>
        If you have a GPX route or track for this walk, upload it here. 
        You can get a GPX route by using various route planning software 
        or websites (e.g.  <a href="http://bikehike.co.uk/index.php">bikehike.co.uk</a>),
        or you can get a GPX track by walking the route with a GPS logger
        and exporting the data to GPX format. The GPX file is currently not published,
        it's just used to fill in some of the values in this form.
      </p>
      <?php 
        echo $this->form->getLabel('route');
        echo $this->form->getInput('route');
        echo "<br>";
        echo $this->form->getLabel('routeOverwrite');
        echo $this->form->getInput('routeOverwrite');
      ?>
      <br>
      <input type="submit" class="submit gpx-upload" value="Upload" />
    </fieldset>
    <fieldset>
      <legend>Location</legend>
      <?php 
        foreach ($this->form->getFieldset("location") as $field)
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
  </form>
<?php endif; ?>