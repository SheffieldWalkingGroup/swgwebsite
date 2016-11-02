<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla controller library
jimport('joomla.application.component.controller');

/**
 * General Controller of WalkLibrary component
 */
class SWG_WalkLibraryController extends JController
{
  /**
   * display task
   *
   * @return void
   */
  function display($cachable = false)
  {
    // set default view if not set
    if (!JRequest::getCmd('view',false))
      JRequest::setVar('view', JRequest::getCmd('view', 'ListWalks'));

    // call parent behavior
    parent::display($cachable);
  }
}