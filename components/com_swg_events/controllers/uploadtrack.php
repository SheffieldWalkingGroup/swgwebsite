<?php
// No direct access.
defined('_JEXEC') or die;

// Include dependancy of the main controllerform class
jimport('joomla.application.component.controllerform');

class SWG_EventsControllerUploadTrack extends JControllerForm
{
  
	// Store the model so it can be given to the view
	private $model;
	
	private $errors;

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
		
		$this->errors = array();

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
				$route = new Route();
				$wi = $model->getWalkInstance();
				if ($wi != null)
					$route->setWalk($wi);
				$route->readGPX($gpx);
				$route->uploadedBy = JFactory::getUser()->id;
				$route->uploadedDateTime = time();
				$route->type = Route::Type_Logged;
				
				if ($wi != null)
				{
					// Check that this route matches the walk
					if ($route->checkAgainstWalk($model->getWalkInstance(), $message, $details))
					{
						// Store this route for later requests
						JFactory::getApplication()->setUserState("uploadedroute", serialize($route));
					}
					else
					{
						throw new UserException("Can't upload this track", 0, "This track doesn't match that walk", $message.": ".$details);
					}
				}
				else
				{
				    // Try to find a matching walk
				    $wi = $route->findMatchingWalk();
				    if (isset($wi))
				    {
						$route->setWalk($wi);
						// Store this route for later requests
						JFactory::getApplication()->setUserState("uploadedroute", serialize($route));
						$model->setWalkInstance($wi);
				    }
				    else
						throw new Exception("That track doesn't match any walks. Check that it doesn't contain anything other than the walk.");
				}
				
				
			}
			else
			{
				throw new Exception("The track you uploaded is not a valid GPX file. If your track is in another format, please convert it to GPX first, then upload it again.");
			}
			
			$view->display();
		}
		else
		{
		    $route = unserialize(JFactory::getApplication()->getUserState("uploadedroute"));
			if ($route)
			{
				$route->save();
				
				// Set the distance on the WalkInstance, if it's not already set
				if (empty($wi->distance))
				{
					$wi->distance = $route->getDistance();
					$wi->save();
				}
			}
			else
			{
			    throw new Exception("There was an error while saving your track. You can try again in a while, or email us if that doesn't work.");
			}
			
			// Redirect to the specified page after saving
			$itemid = JRequest::getInt('returnPage');
			if (empty($itemid))
				return false;
			$item = JFactory::getApplication()->getMenu()->getItem($itemid);
			$link = new JURI("/".$item->route);
			
			// Jump to the event?
			if (JRequest::getBool('jumpToEvent'))
				$link->setFragment("walk_".$wi->id);
			
			JFactory::getApplication()->redirect($link, "Track saved");
			
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