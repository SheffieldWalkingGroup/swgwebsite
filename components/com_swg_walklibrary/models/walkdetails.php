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
 * WalkDetails Model
 */
class SWG_WalkLibraryModelWalkDetails extends JModelItem
{
  
  // A separate flag means we don't keep trying to fetch the walk if it's invalid
  private $fetchedWalk = false;
  private $walk = null;
  
  /**
   * Returns the walk specified by the walkid parameter in the get string.
   * Loads it from the database if necessary.
   */
  public function getWalk()
  {
    if (!$this->fetchedWalk)
    {
      // TODO: Check user has permission to view this walk
      $this->walk = Walk::getSingle(JRequest::getInt("walkid",0,"get"));
      $this->fetchedWalk = true;
    }
    
    return $this->walk;
  }
  
  
}