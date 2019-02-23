<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// Include dependancy of the main model form
jimport('joomla.application.component.modelform');
// import Joomla modelitem library
jimport('joomla.application.component.modelitem');
require_once JPATH_SITE."/swg/swg.php";

/**
 * Lists all walks programmes in the system
 */
class SWG_LeaderUtilsModelListProgrammes extends JModelList
{
	public function getProgrammes()
	{
		$factory = new WalkProgrammeFactory();
		$factory->reverse = true;
		return $factory->get();
	}
	
}
