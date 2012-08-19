<?php
// No direct access.
defined('_JEXEC') or die;

// Include dependancy of the main controllerform class
jimport('joomla.application.component.controllerform');
// Include the actual search class
JLoader::register('WalkSearcher', JPATH_BASE."/swg/Controllers/WalkSearcher.php");

/**
 * This controller handles user input when searching for walks
 * @author peter
 *
 */
class SWG_EventsControllerListWalks extends JControllerForm
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
  
  public function search()
  {
    // This is a Safe Form - there is no effect to searching and it's safe to share URLS.
    // Therefore, there is no need to check tokens or use POST 
    
    $data = JRequest::getVar('jform', array(), 'get', 'array');
    $search = new WalkSearcher();
    
    // Go through each field on the form and, if it's set, convert its value to that expected by the searcher and apply it
    if (isset($data['keywords']))
    {
      // TODO: Allow searching by leaders' information if logged in?
      $search->setPublicText($data['keywords']);
    }
    
    //if (isset($data['location']))
    //{
//      $search->setLocation(array($data['location']));
    //}
    
    if (isset($data['grades']))
    {
      $search->setGrade($data['grades']);
    }
    
    // TODO: Transport
    
    // TODO: Leader
    $this->getModel()->setWalkList($search->search());
    
    $this->display();
    return true;
  }

}