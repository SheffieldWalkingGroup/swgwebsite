<?php
// No direct access.
defined('_JEXEC') or die;
// Include dependancy of the main controllerform class
jimport('joomla.application.component.controllerform');

class SWG_EventsControllerAddEditSocial extends JControllerForm
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
    $model	= $this->getModel('addeditsocial');
    $view = $this->getView('addeditsocial','html');
    $view->setModel($model, true);

    // Get the data from the form POST
    $data = JRequest::getVar('jform', array(), 'post', 'array');
    
    // Send the data to the model
    $model->updateSocial($data);
    $view->display();

    // check if ok and display appropriate message.  This can also have a redirect if desired.
    /*if ($upditem) {
      echo "<h2>Updated Greeting has been saved</h2>";
    } else {
      echo "<h2>Updated Greeting failed to be saved</h2>";
    }*/

    return true;
  }
  
  /* Permissions checks */
  function canAdd()
  {
    return JFactory::getUser()->authorise("social.add","com_swg_events");
  }
  
  function canEdit($socialorID)
  {
    // TODO: Some can edit own socials, e.g. publicity officers
    return JFactory::getUser()->authorise("social.editall","com_swg_events");
  }

}