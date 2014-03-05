<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla view library
jimport('joomla.application.component.view');
jimport('joomla.form.form');
 
/**
 * HTML Add/Edit weekend class for the SWG events component
 */
class SWG_EventsViewAddEditWeekend extends JViewLegacy
{
  function display($tpl = null)
  {
    $app		= JFactory::getApplication();
	$params		= $app->getParams();
	$dispatcher = JDispatcher::getInstance();
    /*$model	    = $this->getModel('addeditweekend');
    $controller = JControllerLegacy::getInstance('SWG_Events');*/

	// Get some data from the models
	$state		= $this->get('State');
	$this->form	= $this->get('Form');
	$this->weekend	= $this->get('Weekend');
	
	// Check the current user can edit this walk (or add a new one)
	/*if (
	    ($model->editing() && !$controller->canEdit($this->weekend)) ||
	    (!$model->editing() && !$controller->canAdd())
    )
	{
	  return JError::raiseWarning(403, JText::_('JERROR_ALERTNOAUTHOR'));
	}*/
	
	// Add CSS
	$document =& JFactory::getDocument();
	$document->addStyleSheet('components/com_swg_events/css/addedit.css');
	
	// Add form validation
	JHTML::_('behavior.formvalidation');
	$document->addScriptDeclaration(<<<VAL
window.addEvent('domready', function(){
    document.formvalidator.setHandler('submit', function (value) {
        alert("WOO");
    });
});
VAL
);
	

	// Check for errors.
	if (count($errors = $this->get('Errors'))) 
	{
		JError::raiseError(500, implode('<br />', $errors));
		return false;
	}
$this->showForm = true;
	// Display the view
	parent::display($tpl);
  }
}