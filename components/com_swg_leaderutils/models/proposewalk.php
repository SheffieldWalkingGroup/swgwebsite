<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

require_once JPATH_BASE."/swg/swg.php";
JLoader::register('WalkProgramme', JPATH_BASE."/swg/Models/WalkProgramme.php");
JLoader::register('Leader', JPATH_BASE."/swg/Models/Leader.php");
JLoader::register('Leader', JPATH_BASE."/swg/Models/Walk.php");
JLoader::register('Leader', JPATH_BASE."/swg/Models/WalkInstance.php");

// Include dependancy of the main model form
jimport('joomla.application.component.modelform');
// import Joomla modelitem library
jimport('joomla.application.component.modelitem');
// Include dependancy of the dispatcher
jimport('joomla.event.dispatcher');

// Custom form field
jimport('joomla.form.helper');
JForm::addFieldPath(JPATH_COMPONENT.'/models/fields');
JFormHelper::loadFieldClass('availability');

class SWG_LeaderUtilsModelProposeWalk extends JModelForm
{
	/**
	 * Programme we're setting availability for
	 * @var WalkProgramme
	 */
	private $programme;
	
	private $leader;
	
	private $form;
	
	private $walk;
	
	private $wi;
	
	private $editing = false;
	
	public function __construct()
	{
		$this->setProgramme(WalkProgramme::getNextProgrammeId());
		$this->setLeader(Leader::fromJoomlaUser(JFactory::getUser()->id));
		
		parent::__construct();
	}
	
	/**
	 * Make a dummy walk instance showing how it will look on the website
	 * 
	 * @param Walk $walk The walk in the database
	 *
	 * @return WalkInstance
	 */
	public function makeWalkInstance(Walk $walk)
	{
        $wi = WalkInstanceFactory::createFromWalk($walk);
        $wi->leader = $this->getLeader();
        return $wi;
	}
	
	/**
	 * Changes the leader being managed.
	 * This does not reset the form.
	 * @param int $leaderid
	 */
	public function setLeader($leaderOrID)
	{
		if ($leaderOrID instanceof Leader)
			$this->leader = $leaderOrID;
		else if (is_numeric($leaderOrID))
			$this->leader = Leader::getLeader($leaderOrID);
	}
	
	public function getLeader()
	{
		return $this->leader;
	}
	
	/**
	 * Changes the programme being managed.
	 * This will reset the form, because we expect the dates to be completely different
	 * @param int $programmeid
	 */
	public function setProgramme($programmeid)
	{
		$this->programme = WalkProgramme::get($programmeid);
		$this->form = null;
	}
	
	public function getProgramme()
	{
		return $this->programme;
	}
	
	public function getWalkInstance()
	{
        return $this->wi;
    }
    
    public function getWalk()
    {
        return $this->walk;
    }
    
    public function getProposal()
    {
        return $this->proposal;
    }
	
	/**
	 * Generate the form. The dates are not fixed, so we can't write an XML file for this.
	 * Each date has a checkbox, defining whether the leader is available (ticked) or not
	 */
	public function getForm($data = array(), $loadData = true)
	{
        $jinput = JFactory::getApplication()->input;
        // Are we editing an existing proposal?
        $proposalId = $jinput->get('proposal', 0, 'INT');
        if (!empty($proposalId)) {
            $this->proposal = WalkProposal::get($proposalId);
            $this->editing = true;
            $this->checkUserPermission($this->proposal);
            $this->walk = $this->proposal->walk;
        } else {
            $this->walk = Walk::getSingle($jinput->get('walkid', 0, 'INT'));
        }
		if ($this->walk) {
            $this->wi = $this->makeWalkInstance($this->walk);
            $this->wi->isDraft = true;
            $this->form = $this->loadForm('com_swg_leaderutils.proposewalk', 'proposewalk', array('control' => 'jform', 'load_data' => $loadData));
        } else {
            $this->form = $this->loadForm('com_swg_leaderutils.proposewalk', 'proposewalk'); // Without jform[field] bit
        }
		
		// TODO
		$loadData = array();
		
		if (empty($this->form)) {
			return false;
		}
		
		// Bind existing data
		
		if (!empty($this->proposal)) {
            $this->form->bind(array(
                'id' => $this->proposal->id,
                'leader' => $this->proposal->leader->id,
                'basicavailability' => null, // TODO
                'weekdays' => null,
                'availability' => $this->proposal->dates,
                'transport' => $this->proposal->timingAndTransport,
                'backmarker' => $this->proposal->backmarker->id,
                'comments' => $this->proposal->comments
            ));
		}
		
		return $this->form;
	}
	
	/**
	 * Check if the current user is allowed to edit a given walk proposal
	 *
	 * @param WalkProposal $proposal Walk proposal to check
	 *
	 * @throws AccessDeniedException
	 */
	private function checkUserPermission(WalkProposal $proposal)
	{
        if (
            $proposal->leader != Leader::fromJoomlaUser(JFactory::getUser()->id) &&
            !$this->getCanChooseLeader()
        ) {
            throw new AccessDeniedException("You can't edit that walk proposal");
        }
	}
	
	public function storeProposal(array $formData)
	{
        if (!empty($formData['id'])) {
            $proposal = WalkProposal::get($formData['id']);
            $this->checkUserPermission($proposal);
        } else {
            $proposal = new WalkProposal();
            $proposal->programme = WalkProgramme::getNextProgrammeId(); // TODO: Selectable?
        }
        
        if ($this->getCanChooseLeader() && !empty($formData['leader']))
            $proposal->leader = (int)$formData['leader'];
        else 
            $proposal->leader = Leader::fromJoomlaUser(JFactory::getUser()->id);
        
        $proposal->walk = (int)$formData['walkid'];
        $proposal->timingAndTransport = $formData['transport'];
        $proposal->comments = $formData['comments'];
        if (!empty($formData['backmarker']))
            $proposal->backmarker = (int)$formData['backmarker'];
        $proposal->populateDatesFromArray($formData['availability']);
        
        $proposal->save();
	}
	
	private function generateCalendarCheckboxes()
	{
	
	}
	
	public function getCanChooseLeader()
	{
        return (
            ($this->editing && SWG_LeaderUtilsController::canEditOtherProposal()) ||
            (!$this->editing && SWG_LeaderUtilsController::canAddOtherProposal())
        );
	}
}
