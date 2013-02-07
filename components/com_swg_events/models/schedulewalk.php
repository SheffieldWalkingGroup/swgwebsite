<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

require_once JPATH_BASE."/swg/swg.php";
JLoader::register('WalkInstance', JPATH_BASE."/swg/Models/WalkInstance.php");
JLoader::register('Walk', JPATH_BASE."/swg/Models/Walk.php");

// Include dependancy of the main model form
jimport('joomla.application.component.modelform');
// import Joomla modelitem library
jimport('joomla.application.component.modelitem');
// Include dependancy of the dispatcher
jimport('joomla.event.dispatcher');

/**
 * Schedule Walk Model
 */
class SWG_EventsModelScheduleWalk extends JModelForm
{
	/**
	* The real WalkInstance object
	* @var WalkInstance
	*/
	private $wi;
	
	/**
	* True if we're editing a walk instance, false if we're adding
	*/
	public function editing()
	{
		return (JRequest::getInt("walkinstanceid",0,"get") != 0);
	}
		
	/**
	* Update the current walk with passed in form data
	*/
	public function updateWI(array $formData)
	{
		// Load an existing walk instance (if any)
		if (!empty($formData['id']))
			$this->wi = WalkInstance::getSingle($formData['id']);
		else
			$this->wi = new WalkInstance();

		// Update all basic fields
		// Fields that can't be saved are just ignored
		// Invalid fields throw an exception - display this to the user and continue
		foreach ($formData as $name=>$value)
		{
			try
			{
				$this->wi->$name = $value;
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
		
		// Now do the fields that have to be done separately
		
		// Date & time
		$this->wi->start = strtotime($formData['date']." ".$formData['meetTime']);
		
		if ($this->wi->isValid())
		{
			$this->wi->save();
			
			// Redirect to the list page
			$itemid = JRequest::getInt('returnPage');
			if (empty($itemid))
				return false;
			$item = JFactory::getApplication()->getMenu()->getItem($itemid);
			$link = new JURI("/".$item->route);
			
			// Jump to the event?
			if (JRequest::getBool('jumpToEvent'))
				$link->setFragment("walk_".$this->wi->id);
			
			JFactory::getApplication()->redirect($link, "Walk scheduled");
		}
		else
		{
			
		}
		
	}
	
	/**
	* Load the walk instance we're adding/editing and returns its values as an array
	* If this is a new walk instance, set some defaults
	*/
	public function getWalkInstance()
	{
		// Load or create the walk instance if not already done
		if (!isset($this->wi))
		{
			if (JRequest::getInt("walkinstanceid",0,"get"))
				$this->wi = WalkInstance::getSingle(JRequest::getInt("walkinstanceid",0,"get"));
			else if (JRequest::getInt("walkid",0,"get"))
				$this->wi = WalkInstance::createFromWalk(Walk::getSingle(JRequest::getInt("walkid",0,"get")));
			else
				$this->wi = new WalkInstance();
		}
		
		return $this->wi->valuesToForm();
	}

	/**
	* Get the form for entering a walk
	*/
	public function getForm($data = array(), $loadData = true)
	{
		$app = JFactory::getApplication('site');

		// Get the form.
		$form = $this->loadForm('com_swg_events.schedulewalk', 'schedulewalk', array('control' => 'jform', 'load_data' => true));
		if (empty($form)) {
			return false;
		}
		
		// Bind existing walk data
		$form->bind($this->getWalkInstance());
		return $form;

	}
	
	public function updItem($data)
	{
		$this->wi->save();
	}
}