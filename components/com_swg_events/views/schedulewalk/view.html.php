<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla view library
jimport('joomla.application.component.view');
jimport('joomla.form.form');
 
/**
 * HTML schedule walk class for the SWG events component
 */
class SWG_EventsViewScheduleWalk extends JViewLegacy
{
	function display($tpl = null)
	{
		$app		= JFactory::getApplication();
		$params		= $app->getParams();
		$dispatcher = JDispatcher::getInstance();

		// Get some data from the models
		$state		= $this->get('State');
		$this->form	= $this->get('Form');
		$this->wi	= $this->get('WalkInstance');
		
		if (!empty($this->wi['id']))
		{
			// Set the existing leader & backmarker
			if (!empty($this->wi['leaderid']))
				$this->form->setValue("leader", null, $this->wi['leaderid']);
			if (!empty($this->wi['backmarkerid']))
				$this->form->setValue("backmarker", null, $this->wi['backmarkerid']);
		}
		
		// Check the current user can edit this walk (or add a new one)
		/*if (
			($model->editing() && !$controller->canEdit($this->social)) ||
			(!$model->editing() && !$controller->canAdd())
		)
		{
		return JError::raiseWarning(403, JText::_('JERROR_ALERTNOAUTHOR'));
		}*/
		
		// Add CSS
		$document =& JFactory::getDocument();
		$document->addStyleSheet('components/com_swg_events/css/addedit.css');
		
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