<?php
// No direct access.
defined('_JEXEC') or die;

// Include dependancy of the main controllerform class
jimport('joomla.application.component.controllerform');

class SWG_LeaderUtilsControllerProposeWalk extends JControllerForm
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
		$model	= $this->getModel('proposewalk');
		$view = $this->getView('proposewalk','html');
		$view->setModel($model, true);

		// Get the data from the form POST
		$input = JFactory::getApplication()->input;
		$data  = $this->input->post->get('jform', array(), 'array');
		
		// TODO: Do stuff
		$model->storeProposal($data);
		$view->saved = true;
		$view->display();
		return true;
	}
	
	
		/* Permissions checks */
	/*function canAdd()
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
	}*/
	
}
