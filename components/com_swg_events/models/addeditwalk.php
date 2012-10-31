<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

require_once JPATH_BASE."/swg/swg.php";
JLoader::register('Walk', JPATH_BASE."/swg/Models/Walk.php");
JLoader::register('Route', JPATH_BASE."/swg/Models/Route.php");

// Include dependancy of the main model form
jimport('joomla.application.component.modelform');
// import Joomla modelitem library
jimport('joomla.application.component.modelitem');
// Include dependancy of the dispatcher
jimport('joomla.event.dispatcher');

/**
 * AddEditWalk Model
 */
class SWG_EventsModelAddEditWalk extends JModelForm
{
  /**
   * The real walk object
   * @var Walk
   */
  private $walk;
    
  /**
   * Update the current walk with passed in form data
   * This also handles GPX data
   */
  public function updateWalk(array $formData)
  {
    $this->loadWalk($formData['id']);
    // Update all basic fields
    // Fields that can't be saved are just ignored
    // Invalid fields throw an exception - display this to the user and continue
    foreach ($formData as $name=>$value)
    {
      try
      {
        $this->walk->$name = $value;
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
        
    // Handle the route file upload
    $file = JRequest::getVar('jform',array(),'files','array');
    if (!empty($file) && $file['error']['route'] == UPLOAD_ERR_OK)
    {
      // We've been given a GPX file. Try to parse it.
      $gpx = DOMDocument::load($file['tmp_name']['route']);
      if ($gpx)
      {
        // Check for a GPX element at the root
        if ($gpx->getElementsByTagName("gpx")->length == 1)
        {
          // TODO: Turn on or off overwriting of existing properties
          $route = new Route($this->walk);
          $route->readGPX($gpx);
          $this->walk->setRoute($route);
          
          // Store this route for later requests
          JFactory::getApplication()->setUserState("uploadedroute", serialize($route));
        }
        else
        {
          echo "Must have only one GPX tag";
        }
      }
      else
      {
        echo "Not a GPX file";
      }
      
      // Return to the form
      
    }
    else
    {
      // Restore previously uploaded file from state
      $route = unserialize(JFactory::getApplication()->getUserState("uploadedroute"));
      if (isset($route))
      {
        $route->setWalk($this->walk);
        $this->walk->setRoute($route);
      }
      
      $this->walk->save();
    }
  }
  
  /**
   * Loads the walk specified, or a blank one if none specified
   */
  public function loadWalk($walkid)
  {
    if (empty($walkid))
    {
      $this->walk = new Walk();
    }
    else
    {
      $this->walk = Walk::getSingle($walkid);
    }
  }
  
  /**
   * Dumps the walk data as an array
   */
  public function getWalk()
  {
    // Load the walk if not already done
    if (!isset($this->walk))
    {
      $this->loadWalk(JRequest::getInt("walkid",0,"get"));
    }
    return array(
      'id'=>$this->walk->id,
      'name'=>$this->walk->name,
      'distanceGrade'=>$this->walk->distanceGrade,
      'difficultyGrade'=>$this->walk->difficultyGrade,
      'miles'=>$this->walk->miles,
      'location'=>$this->walk->location,
      'isLinear'=>(int)$this->walk->isLinear, // Joomla seems to ignore false?
      'startGridRef'=>$this->walk->startGridRef,
      'startPlaceName'=>$this->walk->startPlaceName,
      'endGridRef'=>$this->walk->endGridRef,
      'endPlaceName'=>$this->walk->endPlaceName,
      'routeDescription'=>$this->walk->description,
      'fileLinks'=>$this->walk->fileLinks,
      'information'=>$this->walk->information,
      'routeImage'=>$this->walk->routeImage,
      'suggestedBy'=>$this->walk->suggestedBy,
      'status'=>$this->walk->status,
      'specialTBC'=>$this->walk->specialTBC,
      'dogFriendly'=>$this->walk->dogFriendly,
      'transportByCar'=>$this->walk->transportByCar,
      'transportPublic'=>$this->walk->transportPublic,
      'childFriendly'=>$this->walk->childFriendly,
        
      'route' => $this->walk->route,
    );
  }

  /**
   * Get the form for entering a walk
   */
  public function getForm($data = array(), $loadData = true)
  {
    $app = JFactory::getApplication('site');

    // Get the form.
    $form = $this->loadForm('com_swg_events.addeditwalk', 'addeditwalk', array('control' => 'jform', 'load_data' => true));
    if (empty($form)) {
      return false;
    }
    
    // Bind existing walk data
    $form->bind($this->getWalk());
    return $form;

  }

  public function updItem($data)
  {
    $this->walk->save();
  }
}