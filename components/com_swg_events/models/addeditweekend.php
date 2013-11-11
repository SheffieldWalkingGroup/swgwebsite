<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

require_once JPATH_BASE."/swg/swg.php";
JLoader::register('Weekend', JPATH_BASE."/swg/Models/Weekend.php");

// Include dependancy of the main model form
jimport('joomla.application.component.modelform');
// import Joomla modelitem library
jimport('joomla.application.component.modelitem');
// Include dependancy of the dispatcher
jimport('joomla.event.dispatcher');

/**
 * AddEditWeekend Model
 */
class SWG_EventsModelAddEditWeekend extends JModelForm
{
  /**
   * The real weekend object
   * @var Weekend
   */
  private $weekend;
  
  /**
   * True if we're editing a weekend, false if we're adding
   */
  public function editing()
  {
    return (JRequest::getInt("weekendid",0,"get") != 0);
  }
    
  /**
   * Update the current walk with passed in form data
   */
  public function updateWeekend(array $formData)
  {

    $this->loadWeekend($formData['id']);
    // Update all basic fields
    // Fields that can't be saved are just ignored
    // Invalid fields throw an exception - display this to the user and continue
    foreach ($formData as $name=>$value)
    {
      try
      {
        $this->weekend->$name = $value;
      }
      catch (UnexpectedValueException $e)
      {
        echo "<p>";
        var_dump($name);
        var_dump($value);
        var_dump($e->getMessage());
        echo "</p>";
      }
    }
    
    // Date fields need to be converted
	if (!empty($formData['startdate']))
		$this->weekend->start = strtotime($formData['startdate']);
	else
		$this->weekend->start = null;
	if (!empty($formData['enddate']))
		$this->weekend->endDate = strtotime($formData['enddate']);
	else
		$this->weekend->endDate = null;
	
	// Alterations
	$this->weekend->alterations->incrementVersion();
	$this->weekend->alterations->setDetails($formData['alterations_details']);
	$this->weekend->alterations->setCancelled($formData['alterations_cancelled']);
	$this->weekend->alterations->setOrganiser($formData['alterations_organiser']);
	$this->weekend->alterations->setDate($formData['alterations_date']);
	
	if ($this->weekend->isValid())
	{
		$this->weekend->save();
		
		// Redirect to the list page
		$itemid = JRequest::getInt('returnPage');
		if (empty($itemid))
			return false;
		$item = JFactory::getApplication()->getMenu()->getItem($itemid);
		$link = new JURI("/".$item->route);
		
		// Jump to the event?
		if (JRequest::getBool('jumpToEvent'))
			$link->setFragment("weekend_".$this->weekend->id);
		
		JFactory::getApplication()->redirect($link, "Weekend saved");
	}
	
  }
  
  /**
   * Loads the weekend specified, or a blank one if none specified
   */
  public function loadWeekend($id)
  {
    if (empty($id))
    {
      $this->weekend = new Weekend();
      $this->weekend->swg = true;
    }
    else
    {
      $this->weekend = Weekend::getSingle($id);
    }
  }
  
  /**
   * Dumps the walk data as an array
   */
  public function getWeekend()
  {
    // Load the walk if not already done
    if (!isset($this->weekend))
    {
      $this->loadWeekend(JRequest::getInt("weekendid",0,"get"));
    }
    return $this->weekend->valuesToForm();
  }

  /**
   * Get the form for entering a walk
   */
  public function getForm($data = array(), $loadData = true)
  {
    $app = JFactory::getApplication('site');

    // Get the form.
    $form = $this->loadForm('com_swg_events.addeditweekend', 'addeditweekend', array('control' => 'jform', 'load_data' => true));
    if (empty($form)) {
      return false;
    }
    
    // Bind existing weekend data
    $form->bind($this->getWeekend());
    return $form;

  }

  public function updItem($data)
  {
    $this->weekend->save();
  }
}