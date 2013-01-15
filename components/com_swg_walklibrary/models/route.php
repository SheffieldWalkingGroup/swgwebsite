<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

require_once JPATH_BASE."/swg/swg.php";
JLoader::register('Route', JPATH_BASE."/swg/Models/Route.php");

// Include dependancy of the main model form
jimport('joomla.application.component.modelform');
// import Joomla modelitem library
jimport('joomla.application.component.modelitem');
// Include dependancy of the dispatcher
jimport('joomla.event.dispatcher');

/**
 * RouteModel
 */
class SWG_WalkLibraryModelRoute extends JModelItem
{
  
  // A separate flag means we don't keep trying to fetch the walk if it's invalid
  private $fetchedRoute = false;
  private $route = null;
  
  /**
   * Returns the route specified by the routeid parameter in the get string.
   * Loads it from the database if necessary.
   */
  public function getRoute()
  {
    if (!$this->fetchedRoute)
    {
      // TODO: Check user has permission to view this walk
      $this->route = Route::loadSingle(JRequest::getInt("walkid",0,"get"));
      $this->fetchedRoute = true;
    }
    
    return $this->route;
  }
}