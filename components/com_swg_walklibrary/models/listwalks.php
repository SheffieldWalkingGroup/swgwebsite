<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// Include dependancy of the main model form
jimport('joomla.application.component.modelform');
// import Joomla modelitem library
jimport('joomla.application.component.modelitem');
require_once JPATH_BASE."/swg/swg.php";
JLoader::register('Walk', JPATH_BASE."/swg/Models/Walk.php");
JLoader::register('Leader', JPATH_BASE."/swg/Models/Leader.php");

/**
 * Lists a set of walks (not WalkInstances) - 
 * i.e. walks without a schedule.
 * This is useful for showing walk leaders the walks they've submitted,
 * and showing walk planners the available walks
 */
class SWG_WalkLibraryModelListWalks extends JModelForm
{
	// A walk list can be set, which overrides any other actions. For example, this may contain search results
	private $walkList = null;
	
	public function getWalks()
	{
		// If we have a custom walk list, return that
		if (isset($this->walkList))
		{
			return $this->walkList;
		}
		// Otherwise, get the default data
		switch(JRequest::getInt("initialView"))
		{
		case 0:
		default:
			return array();
		case 1:
			// Get this leader's record
			$leader = Leader::fromJoomlaUser(JFactory::getUser()->id);
			if (!empty($leader))
				return Walk::getWalksBySuggester($leader);
			else {
			    return array();
			}
			
		}
	}
	
	public function getForm($data = array(), $loadData = true)
	{ 
		// Get the form.
		$form = $this->loadForm('com_swg_walklibrary.searchwalk', 'searchwalk', array('control' => 'jform', 'load_data' => true));
		if (empty($form)) {
			return false;
		}
		
		return $form;
	}
	
	public function updItem($data)
	{
		die("OK");
	}
	
	/**
	* Pass in an array of walks to override the set walk list
	* @param array $walks
	*/
	public function setWalkList(array $walks)
	{
		$this->walkList = $walks;
	}
	
	public function hasWalkList()
	{
		return (isset($this->walkList));
	}
	
	/* Permissions checks */
	
	function canAdd()
	{
		return JFactory::getUser()->authorise("walk.add","com_swg_walklibrary.walks");
	}
	
	function canEdit($walkOrID)
	{
		return JController::getInstance('SWG_WalkLibrary')->canEdit($walkOrID);
	}
}