<?php
// No direct access.
defined('_JEXEC') or die;

// Include dependancy of the main controllerform class
jimport('joomla.application.component.controllerform');

class SWG_EventsControllerUploadTrack extends JControllerForm
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

	public function upload()
	{
		// Check for request forgeries.
		JRequest::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Initialise variables.
		$app	= JFactory::getApplication();
		$model	= $this->getModel('uploadtrack');
		$view = $this->getView('uploadtrack','html');
		$view->setModel($model, true);
		$wi = $model->getWalkInstance();
		
		// Are we uploading a track, or saving the track we've already uploaded?
		$file = JRequest::getVar('file',array(),'FILES','array');
		if (!empty($file) && $file['error'] == UPLOAD_ERR_OK)
		{
			// We've been given a GPX file. Try to parse it.
			$gpx = DOMDocument::load($file['tmp_name']);
			if ($gpx && $gpx->getElementsByTagName("gpx")->length == 1)
			{
				$route = new Route($model->getWalkInstance());
				$route->readGPX($gpx);
				$route->uploadedBy = JFactory::getUser()->id;
				$route->uploadedDateTime = time();
				
				// Store this route for later requests
				JFactory::getApplication()->setUserState("uploadedroute", serialize($route));
			}
			else
			{
				echo "Not a valid GPX file";
			}
			
			$view->display();
		}
		else
		{
		    $route = unserialize(JFactory::getApplication()->getUserState("uploadedroute"));
			if ($route)
			{
				$route->save();
			}
			else
			{
			    echo "Error while saving route";
			}
			
			// TODO: Redirect to correct place - "My diary"?
			JFactory::getApplication()->redirect("/whats-on/previous-events#walk_".$this->wi->id);
			
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