<?php
// No direct access.
defined('_JEXEC') or die;

// Include dependancy of the main controllerform class
jimport('joomla.application.component.controllerform');

class SWG_LeaderUtilsControllerAddEditProgramme extends JControllerForm
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
		$saveModel	= $this->getModel('addeditprogramme');

		// Get the data from the form POST
		$data = JRequest::getVar('jform', array(), 'post', 'array');
		
		// Send the data to the model
		$saveModel->updateProgramme($data);
        $saveModel->getProgramme()->save();
			
		$this->setRedirect(JUri::base().'?option=com_swg_leaderutils&view=ListProgrammes', $saveModel->getProgramme()->title.' programme saved');
        return true;
	}
	
}
