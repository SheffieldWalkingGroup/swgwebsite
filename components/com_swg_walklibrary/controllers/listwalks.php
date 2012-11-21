<?php
// No direct access.
defined('_JEXEC') or die;

// Include dependancy of the main controllerform class
jimport('joomla.application.component.controllerform');
// Include the actual search class
JLoader::register('WalkSearcher', JPATH_BASE."/swg/Controllers/WalkSearcher.php");
JLoader::register('Leader',JPATH_BASE."/swg/Models/Leader.php");

/**
 * This controller handles user input when searching for walks
 * @author peter
 *
 */
class SWG_WalkLibraryControllerListWalks extends JControllerForm
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
    
    // Need to get each grade separately, then build the array.
    // If no grades are set, allow all.
    $possibleGrades = array("A1","A2","A3","B1","B2","B3","C1","C2","C3");
    $selectedGrades = array();
    foreach ($possibleGrades as $grade)
    {
      if (!empty($data['grade'.$grade]))
      {
        $selectedGrades[] = $grade;
      }
    }
    
    if (!empty($selectedGrades))
    {
      $search->setGrade($selectedGrades);
    }
    
    // Areas
    if (!empty($data['location']))
    {
      $search->setLocation(array((int)$data['location']));
    }
        
    // Transport
    if (isset($data['transportPublic']))
      $search->setTransportPublic((bool)$data['transportPublic']);
    
    if (isset($data['transportCar']))
      $search->setTransportByCar((bool)$data['transportCar']);
    
    // TODO: Leader - currently this searches who suggested it, not anyone else who's led it
    if (!empty($data['leader']))
      $search->setSuggestedBy(Leader::getLeader($data['leader']));
    
    $this->getModel()->setWalkList($search->search());
    
    $this->display();
    return true;
  }

}