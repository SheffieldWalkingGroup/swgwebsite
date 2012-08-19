<?php
// No direct access.
defined('_JEXEC') or die;

// Include dependancy of the main controllerform class
jimport('joomla.application.component.controllerform');

class SWG_EventsControllerAddEditWalk extends JControllerForm
{
  
  // Store the model so it can be given to the view
  private $model;

  public function getModel($name = '', $prefix = '', $config = array('ignore_request' => true))
  {
    if (!isset($this->model))
    {
      $this->model = parent::getModel($name, $prefix, array('ignore_request' => false));
    }
    return $this->model;
  }

  public function submit()
  {
    // Check for request forgeries.
    JRequest::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

    // Initialise variables.
    $app	= JFactory::getApplication();
    $model	= $this->getModel('addeditwalk');
    $view = $this->getView('addeditwalk','html');

    // Get the data from the form POST
    $data = JRequest::getVar('jform', array(), 'post', 'array');
    
    // Send the data to the model
    $model->updateWalk($data);
    $this->display();

    // check if ok and display appropriate message.  This can also have a redirect if desired.
    /*if ($upditem) {
      echo "<h2>Updated Greeting has been saved</h2>";
    } else {
      echo "<h2>Updated Greeting failed to be saved</h2>";
    }*/

    return true;
  }
  
  public function listMine()
  {
    $view = $this->getView('addeditwalk','html');
    $this->display();
    
  }

}