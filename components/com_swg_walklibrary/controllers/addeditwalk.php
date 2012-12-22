<?php
// No direct access.
defined('_JEXEC') or die;

// Include dependancy of the main controllerform class
jimport('joomla.application.component.controllerform');

class SWG_WalkLibraryControllerAddEditWalk extends JControllerForm
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
    $view->setModel($model, true);

    // Get the data from the form POST
    $data = JRequest::getVar('jform', array(), 'post', 'array');
    
    // Send the data to the model
    $model->updateWalk($data);
    $view->display();

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
  
  /* Permissions checks */
  function canAdd()
  {
    return JFactory::getUser()->authorise("walk.add","com_swg_walklibrary");
  }
  
  function canEdit($walkOrID)
  {
    // TODO: Leaders can edit own walks
    return JFactory::getUser()->authorise("walk.editall","com_swg_walklibrary");
  }

}