<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

require_once JPATH_BASE."/swg/swg.php";
JLoader::register('Walk', JPATH_BASE."/swg/Models/Walk.php");
JLoader::register('Route', JPATH_BASE."/swg/Models/Route.php");

// Include dependancy of the main model form
jimport('joomla.application.component.modelform');
// import Joomla modelitem library
jimport('joomla.application.component.modelitem');
// Include dependancy of the dispatcher
jimport('joomla.event.dispatcher');

/**
 * AddEditWalk Model
 */
class SWG_WalkLibraryModelAddEditWalk extends JModelForm
{
	/**
	* The real walk object
	* @var Walk
	*/
	private $walk;

	/**
	* True if we're editing a walk, false if we're adding
	*/
	public function editing()
	{
		return (JRequest::getInt("walkid",0,"get") != 0);
	}
		
	/**
	* Update the current walk with passed in form data
	* This also handles GPX data
	*/
	public function updateWalk(array $formData)
	{
		$this->loadWalk($formData['id']);
		// Update all basic fields
		// Fields that can't be saved are just ignored
		// Invalid fields throw an exception - display this to the user and continue
		foreach ($formData as $name=>$value)
		{
			try
			{
				$this->walk->$name = $value;
			}
			catch (UnexpectedValueException $e)
			{
				echo "<p>";
				var_dump($name);
				var_dump($value);
				var_dump($e->getMessage());
				echo "</p>";
			}
		}
			
		// Handle the route file upload
		$file = JRequest::getVar('jform',array(),'files','array');
		if (!empty($file) && $file['error']['route'] == UPLOAD_ERR_OK)
		{
			// We've been given a GPX file. Try to parse it.
			$gpx = DOMDocument::load($file['tmp_name']['route']);
			if ($gpx)
			{
				// Check for a GPX element at the root
				if ($gpx->getElementsByTagName("gpx")->length == 1)
				{
					// Get the route ID if we have an existing route
					if (isset($this->walk->route))
					{
						$routeID = $this->walk->route->id;
					}
					else
					{
						$routeID = null;
					}
					// TODO: Turn on or off overwriting of existing properties
					
					if (isset($this->walk->route))
					{
						$route = $this->walk->route;
					}
					else
					{
						$route = new Route($this->walk);
					}
					$route->readGPX($gpx);
					$route->uploadedBy = JFactory::getUser()->id;
					$route->uploadedDateTime = time();
					$this->walk->setRoute($route);
					
					// Store this route for later requests
					JFactory::getApplication()->setUserState("uploadedroute", serialize($route));
				}
				else
				{
					echo "Must have only one GPX tag";
				}
			}
			else
			{
				echo "Not a GPX file";
			}
			
			// Return to the form
		
		}
		else
		{
			// Restore previously uploaded file from state
			$route = unserialize(JFactory::getApplication()->getUserState("uploadedroute"));
			if ($route)
			{
				$route->setWalk($this->walk);
				$this->walk->setRoute($route);
			}
		}
		
		// If we have a route, allow the user to set options on it
		if (isset($this->walk->route))
		{
			$this->walk->route->visibility = $formData['routeVisibility'];
		}
	}
	
	/**
	 * Remove the route from this walk
	 */
	public function clearRoute()
	{
		$this->walk->unsetRoute();
	}

	/**
	* Loads the walk specified, or a blank one if none specified
	*/
	public function loadWalk($walkid)
	{
		if (empty($walkid))
		{
			$this->walk = new Walk();
		}
		else
		{
			$this->walk = Walk::getSingle($walkid);
			$this->walk->loadRoute();
		}
	}

	/**
	* Dumps the walk data as an array
	*/
	public function getWalk()
	{
		// Load the walk if not already done
		if (!isset($this->walk))
		{
			$this->loadWalk(JRequest::getInt("walkid",0,"get"));
		}
		return $this->walk;
	}

	/**
	* Get the form for entering a walk
	*/
	public function getForm($data = array(), $loadData = true)
	{
		$app = JFactory::getApplication('site');

		// Get the form.
		$form = $this->loadForm('com_swg_events.addeditwalk', 'addeditwalk', array('control' => 'jform', 'load_data' => true));
		if (empty($form)) {
			return false;
		}
		
		$walk = $this->getWalk();
		
		// Bind existing walk data
		$form->bind($walk->valuesToForm());
		if (isset($walk->route))
		{
			$form->setValue("routeVisibility", null, $walk->route->visibility);
		}
		
		return $form;

	}

	public function updItem($data)
	{
		$this->walk->save();
	}
}