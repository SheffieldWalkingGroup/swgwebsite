<?php
// No direct access.
defined('_JEXEC') or die;

// Include dependancy of the main controllerform class
jimport('joomla.application.component.controllerform');

class SWG_LeaderUtilsControllerManageAvailability extends JControllerForm
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
		$model	= $this->getModel('manageavailability');
		$view = $this->getView('manageavailability','html');
		$view->setModel($model, true);

		// Get the data from the form POST
		$input = JFactory::getApplication()->input;
		$data = new JRegistry($input->get('jform', '', 'array'));
		
		// The returned data only includes the dates when the leader IS available (unchecked checkboxes don't exist)
		// So get an array of all dates, set them all to not available, and apply the given availability on top.
		$model->setProgramme($data->get("programmeid"));
		
		// TODO: Check if programme exists
		$dates = $model->getProgramme()->dates;
		$availability = array();
		foreach ($dates as $date)
		{
			$availability[$date] = (bool)($data->get("availability_".$date, false));
		}
		$model->getProgramme()->setLeaderAvailability($model->getLeader()->id, $availability);
		
		$view->saved = true;
		$view->display();
		return true;
	}
	
	public function listMine()
	{
		$view = $this->getView('addeditwalk','html');
		$this->display();
	}
	
	/* Permissions checks */
	function canAdd()
	{
		return JFactory::getUser()->authorise("walk.add","com_swg_walklibrary");
	}
	
	function canEdit($walkOrID)
	{
		// TODO: Leaders can edit own walks
		return JFactory::getUser()->authorise("walk.editall","com_swg_walklibrary");
	}
	
	function canEditAll()
	{
		return JFactory::getUser()->authorise("walk.editall","com_swg_walklibrary");
	}

}