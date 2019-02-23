<?php
// No direct access.
defined('_JEXEC') or die;

// Include dependancy of the main controllerform class
jimport('joomla.application.component.controllerform');

require_once JPATH_SITE."/swg/swg.php";

/**
 * This controller handles user input when searching for walks
 * @author peter
 *
 */
class SWG_LeaderUtilsControllerListProgrammes extends JControllerForm
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
	
	public function updateCurrentProgramme()
	{
        // Check for request forgeries.
		JRequest::checkToken() or jexit(JText::_('JINVALID_TOKEN'));
		
		// Get the data from the form POST
		WalkProgramme::setCurrentProgrammeID($_POST['currentProgramme']);
		WalkProgramme::setNextProgrammeID($_POST['nextProgramme']);
		
        $this->setRedirect(JUri::base().'?option=com_swg_leaderutils&view=ListProgrammes', 'Current &amp; next programme set');
        return true;
	}
}
