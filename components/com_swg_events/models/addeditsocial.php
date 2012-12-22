<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

require_once JPATH_BASE."/swg/swg.php";
JLoader::register('Social', JPATH_BASE."/swg/Models/Social.php");

// Include dependancy of the main model form
jimport('joomla.application.component.modelform');
// import Joomla modelitem library
jimport('joomla.application.component.modelitem');
// Include dependancy of the dispatcher
jimport('joomla.event.dispatcher');

/**
 * AddEditSocial Model
 */
class SWG_EventsModelAddEditSocial extends JModelForm
{
  /**
   * The real social object
   * @var Social
   */
  private $social;
  
  /**
   * True if we're editing a social, false if we're adding
   */
  public function editing()
  {
    return (JRequest::getInt("socialid",0,"get") != 0);
  }
    
  /**
   * Update the current walk with passed in form data
   * This also handles GPX data
   */
  public function updateSocial(array $formData)
  {
    $this->loadSocial($formData['id']);
    // Update all basic fields
    // Fields that can't be saved are just ignored
    // Invalid fields throw an exception - display this to the user and continue
    foreach ($formData as $name=>$value)
    {
      try
      {
        $this->social->$name = $value;
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
    
    // Time fields need to be built up (they combine date & time internally)
	if (!empty($formData['date']))
	{
		if (!empty($formData['starttime']))
			$this->social->start = strtotime($formData['date']." ".$formData['starttime']);
		if (!empty($formData['endtime']))
			$this->social->end = strtotime($formData['date']." ".$formData['endtime']);
	}
    
    if ($this->social->isValid())
    {
		$this->social->save();
	}
	else
	{
	    // Find out why it's invalid. 
	    // All other errors should be caught by JS - just check it's either a normal social or a new members one.
	    if (!$this->social->showNormal && !$this->social->showNewMember)
	    {
			
	    }
	    
	}
	
  }
  
  /**
   * Loads the walk specified, or a blank one if none specified
   */
  public function loadSocial($id)
  {
    if (empty($id))
    {
      $this->social = new Social();
    }
    else
    {
      $this->social = Social::getSingle($id);
    }
  }
  
  /**
   * Dumps the walk data as an array
   */
  public function getSocial()
  {
    // Load the walk if not already done
    if (!isset($this->social))
    {
      $this->loadSocial(JRequest::getInt("socialid",0,"get"));
    }
    return $this->social->valuesToForm();
  }

  /**
   * Get the form for entering a walk
   */
  public function getForm($data = array(), $loadData = true)
  {
    $app = JFactory::getApplication('site');

    // Get the form.
    $form = $this->loadForm('com_swg_events.addeditsocial', 'addeditsocial', array('control' => 'jform', 'load_data' => true));
    if (empty($form)) {
      return false;
    }
    
    // Bind existing walk data
    $form->bind($this->getSocial());
    return $form;

  }

  public function updItem($data)
  {
    $this->social->save();
  }
}