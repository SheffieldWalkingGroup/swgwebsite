<?php
// No direct access.
defined('_JEXEC') or die;

// Include dependancy of the main controllerform class
jimport('joomla.application.component.controllerform');

class SWG_WalkLibraryControllerAddEditWalk extends JControllerForm
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
		$model	= $this->getModel('addeditwalk');
		$view = $this->getView('addeditwalk','html');
		$view->setModel($model, true);

		// Get the data from the form POST
		$data = JRequest::getVar('jform', array(), 'post', 'array');
		
		// Send the data to the model
		$model->updateWalk($data);
		
		// If this is a new walk, set the suggester to the current user
		if (!isset($model->getWalk()->id))
		{
			$model->getWalk()->suggestedBy = Leader::getJoomlaUser(JFactory::getUser()->id);
		}
		
		// Did we save the walk, or just upload a GPX file?
		if (JRequest::getVar('upload', false, 'post'))
		{
			$view->display();
		}
		else
		{
			$model->getWalk()->save();
			// Redirect to the walk details page
			$url = $_SERVER['REQUEST_URI'];
			
			// Get the current URL parameters
			if (strpos($url, "?") !== false)
			{
				$inParams = explode("&", substr($url,strpos($url,"?")+1));
				$urlBase = substr($url,0,strpos($url,"?"));
			}
			else
			{
				$inParams = array();
				$urlBase = $url;
			}
			
			// Build the new URL parameters
			$params = array(
				"view=walkdetails",
				"walkid=".$model->getWalk()->id,
			);
			
			if (isset($inParams['option']))
			$params['option'] = $inParams['option'];
			
			JFactory::getApplication()->redirect($urlBase."?".implode("&amp;", $params));

			return true;
		}
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

}