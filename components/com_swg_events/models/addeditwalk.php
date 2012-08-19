<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

require_once JPATH_BASE."/swg/swg.php";
JLoader::register('Walk', JPATH_BASE."/swg/Models/Walk.php");

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
   * Create a new, blank walk
   * TODO: Need a method to load an existing one
   */
  public function __construct()
  {
    $this->walk = new Walk();
    parent::__construct();
  }
  
  /**
   * Update the current walk with passed in form data
   * This also handles GPX data
   */
  public function updateWalk(array $formData)
  {
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
        var_dump($e->getMessage());
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
          $this->walk->loadRoute($gpx);
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
    }
  }
  
  /**
   * Dumps the walk data as an array
   */
  public function getWalk()
  {
    return array(
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
      'routeDescription'=>$this->walk->routeDescription,
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

  /**
   * Get the message
   * @return object The message to be displayed to the user
   */
  function &getItem()
  {

    if (!isset($this->_item))
    {
      $cache = JFactory::getCache('com_helloworld', '');
      $id = $this->getState('helloworld.id');
      $this->_item =  $cache->get($id);
      if ($this->_item === false) {

      }
    }
    return $this->_item;

  }

  public function updItem($data)
  {
    // set the variables from the passed data
    $id = $data['id'];

    // set the data into a query to update the record
    $db		= $this->getDbo();
    $query	= $db->getQuery(true);
    $query->clear();
    $query->update(' #__helloworld ');
    $query->set(' greeting = '.$db->Quote($greeting) );
    $query->where(' id = ' . (int) $id );

    $db->setQuery((string)$query);

    if (!$db->query()) {
      JError::raiseError(500, $db->getErrorMsg());
      return false;
    } else {
      return true;
    }
  }
}