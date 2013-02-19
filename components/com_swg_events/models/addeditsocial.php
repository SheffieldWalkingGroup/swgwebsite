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
		else
			$this->social->start = strtotime($formData['date']);
		if (!empty($formData['endtime']))
			$this->social->end = strtotime($formData['date']." ".$formData['endtime']);
	}
	
	if (!empty($formData['newMemberStart']))
		$this->social->newMemberStart = strtotime($formData['date']." ".$formData['newMemberStart']);
	if (!empty($formData['newMemberEnd']))
		$this->social->newMemberEnd = strtotime($formData['date']." ".$formData['newMemberEnd']);
		    
    if ($this->social->isValid())
    {
		$this->social->save();
		
		// Redirect to the list page
		$itemid = JRequest::getInt('returnPage');
		if (empty($itemid))
			return false;
		$item = JFactory::getApplication()->getMenu()->getItem($itemid);
		$link = new JURI("/".$item->route);
		
		// Jump to the event?
		if (JRequest::getBool('jumpToEvent'))
			$link->setFragment("social_".$this->social->id);
		
		JFactory::getApplication()->redirect($link, "Social saved");
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
   * By default, a new social is visibile to current members.
   * @param int $id Social ID, or blank for a new social
   * @return Social
   */
  public function loadSocial($id = null)
  {
    if (empty($id))
    {
      $this->social = new Social();
      $this->social->showNormal = true;
    }
    else
    {
      $this->social = Social::getSingle($id);
    }
  }
  
  /**
   * Load the social we're adding/editing and returns its values as an array
   * If this is a new social, set some defaults
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