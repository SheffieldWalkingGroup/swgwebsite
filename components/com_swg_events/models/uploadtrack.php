<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

require_once JPATH_BASE."/swg/swg.php";
require_once JPATH_BASE."/swg/Models/Route.php";

// Include dependancy of the main model form
jimport('joomla.application.component.modelform');
// import Joomla modelitem library
jimport('joomla.application.component.modelitem');
// Include dependancy of the dispatcher
jimport('joomla.event.dispatcher');

/**
 * UploadTrack Model
 */
class SWG_EventsModelUploadTrack extends JModelForm
{
	/**
	* The real walkInstance object
	* @var WalkInstance
	*/
	private $wi;
	
	private $form;
	
	/**
	* Get the form for uploading a track
	*/
	public function getForm($data = array(), $loadData = true)
	{
		$app = JFactory::getApplication('site');

		// Get the form.
		if (empty($this->form))
		{
			$this->form = $this->loadForm('com_swg_events.uploadtrack','uploadtrack');
			if (empty($this->form)) {
				return false;
			}
		}
		
		return $this->form;
	}
	
	/**
	* Returns the walk specified by the walkid parameter in the get string.
	* Loads it from the database if necessary.
	*/
	public function getWalkInstance()
	{
		// TODO: Check user has permission to view this walk
		if (isset($this->wi))
			return $this->wi;
		
		$wiFact = SWG::walkInstanceFactory();
		
		// Do we have a walk from the form?
		// Get the data from the form POST
		$formWI = JRequest::getInt('wi', array(), 'post', 'array');
		if (!empty($formWI))
		{
		    $this->wi = $wiFact->getSingle($formWI);
		}
		else
		{
			$this->wi = $wiFact->getSingle(JRequest::getInt("wi",0,"get"));
		}
		return $this->wi;
	}
	
	/**
	 * Sets the walkInstance to a specific walk
	 * @param WalkInstance $wi
	 */
	public function setWalkInstance(WalkInstance $wi)
	{
		$this->wi = $wi;
	}
	
	/**
	 * Get the track from the last form submission
	 */
	public function getCachedTrack()
	{
		$formWI = JRequest::getInt('wi', array(), 'post', 'array');
		if (!empty($formWI))
		{
			$route = unserialize(JFactory::getApplication()->getUserState("uploadedroute"));
			return $route;
		}
		return null;
	}
}
