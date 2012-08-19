<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla view library
jimport('joomla.application.component.view');
jimport('joomla.form.form');
 
/**
 * A detailed view of a walk.
 * 
 * This can be used from the walk library, or from an event list.
 * Because most (all?) fields can be changed on a WalkInstance,
 * this can take either a Walk or a WalkInstance
 */
class SWG_EventsViewAddEditWalk extends JView
{
  function display($tpl = null)
  {
    $app		= JFactory::getApplication();
	$params		= $app->getParams();
	$dispatcher = JDispatcher::getInstance();
    $model	    = $this->getModel('addeditwalk');

	// Get some data from the models
	$state		= $this->get('State');
	$walk		= $this->get('Walk');

	// Check for errors.
	if (count($errors = $this->get('Errors'))) 
	{
		JError::raiseError(500, implode('<br />', $errors));
		return false;
	}
	
	// Display the view
	parent::display($tpl);
  }
}