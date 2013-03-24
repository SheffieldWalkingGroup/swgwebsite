<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla view library
jimport('joomla.application.component.view');
jimport('joomla.form.form');
 
/**
 * HTML Add/Edit walk class for the SWG Walk Library component
 * 
 * The form has a file input near the top for a GPX route, and two submit buttons:
 * one near the file upload and one at the bottom. The file upload button processes
 * the GPX file only, whereas the main button processes the rest of the form. 
 * When a GPX file is uploaded, any blank fields are filled in and if the option to
 * overwrite existing data is enabled, all known fields are filled in. 
 */
class SWG_WalkLibraryViewAddEditWalk extends JView
{
	function display($tpl = null)
	{
		$app		= JFactory::getApplication();
		$params		= $app->getParams();
		$dispatcher = JDispatcher::getInstance();
		$model	    = $this->getModel('addeditwalk');
		$controller = JController::getInstance('SWG_WalkLibrary');

		// Get some data from the models
		$state		= $this->get('State');
		$this->form	= $this->get('Form');
		$this->walk	= $this->get('Walk');
		// Check the current user can edit this walk (or add a new one)
		if (
			($model->editing() && !$controller->canEdit($this->walk)) ||
			(!$model->editing() && !$controller->canAdd())
		)
		{
			return JError::raiseWarning(403, JText::_('JERROR_ALERTNOAUTHOR'));
		}

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