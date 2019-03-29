<?php
// No direct access.
defined('_JEXEC') or die;

// Include dependancy of the main controllerform class
jimport('joomla.application.component.controllerform');
require_once JPATH_SITE . '/swg/swg.php';
require_once JPATH_SITE . '/swg/Factories/WalkProposalFactory.php';

class SWG_LeaderUtilsControllerCompileProgramme extends JControllerForm
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
		$proposalFactory = new WalkProposalFactory();
		$wiFactory = new WalkInstanceFactory();
		$model = $this->getModel();
		$programme = $model->getProgramme();
		
		// Get the data from the form POST
		$proposalDates = $_POST['proposals'];
		foreach ($proposalDates as $proposalId => $date) {
            $proposal = WalkProposal::get($proposalId);

            if ($proposal->isInProgramme()) {
                $walkInstance = $proposal->walkInstance;
            } else {
                $walk = $proposal->walk;
                $walkInstance = $wiFactory->createFromWalk($walk);
                $walkInstance->meetPlaceTime = $proposal->timingAndTransport;
                // TODO: Comments
                
            }
            
            $walkInstance->start = date('U', strtotime($date));
            $walkInstance->save();
            
            $proposal->walkInstance = $walkInstance;
            $proposal->save();
            
            $programme->addWalk($walkInstance);
            
            $this->setRedirect(JUri::base().'?option=com_swg_leaderutils&view=CompileProgramme', 'Walks added to programme');
		}
		
		
		

		
		/*
		
		// Send the data to the model
		$saveModel->updateProgramme($data);
        $saveModel->getProgramme()->save();
			
		$this->setRedirect(JUri::base().'?option=com_swg_leaderutils&view=ListProgrammes', $saveModel->getProgramme()->title.' programme saved');
        return true;*/
	}
	
}
