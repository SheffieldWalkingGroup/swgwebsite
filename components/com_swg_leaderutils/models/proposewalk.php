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
	
	/**
	 * Generate the form. The dates are not fixed, so we can't write an XML file for this.
	 * Each date has a checkbox, defining whether the leader is available (ticked) or not
	 * TODO: Display weekends and bank holidays (bank holidays currently not in database)
	 */
	public function getForm($data = array(), $loadData = true)
	{
        $jinput = JFactory::getApplication()->input;
		$this->walk = Walk::getSingle($jinput->get('walkid', 0, 'INT'));
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
		$this->form->getField('availability')->setProgramme($this->programme);
		
		// TODO: Bind existing data
		
		/*if (!isset($this->form))
		{
			// Looks like I have to generate the XML for the form?! WTF? Hurr durr OOP is hard for our programmer users durr.
			$XMLDoc = new DomDocument("1.0", "UTF-8");
			$formXML = $XMLDoc->createElement("form");
			$formXML->setAttribute("name", "manageavailability");
			$XMLDoc->appendChild($formXML);
			$fieldsetXML = $XMLDoc->createElement("fieldset");
			$fieldsetXML->setAttribute("name", "availability");
			$formXML->appendChild($fieldsetXML);
			$progNumXML = $XMLDoc->createElement("field");
			$progNumXML->setAttribute("type", "hidden");
			$progNumXML->setAttribute("name", "programmeid");
			$progNumXML->setAttribute("default", $this->programme->id);
			$fieldsetXML->appendChild($progNumXML);
			
			foreach ($this->programme->getLeaderAvailability($this->leader->id) as $date => $available)
			{
				$field = $XMLDoc->createElement("field");
				$field->setAttribute("type", "checkbox");
				$field->setAttribute("name", "availability_".$date);
				$field->setAttribute("label", strftime("%A %e %B %Y", $date));
				$field->setAttribute("value", 1);
				$field->setAttribute("default", $available ? 1 : 0);
				$fieldsetXML->appendChild($field);
			}
			
			$this->form = $this->loadForm("com_swg_leaderutils.manageavailability", $XMLDoc->saveXML(), array('control' => 'jform', 'load_data' => false));
		}*/
		
		return $this->form;
	}
	
	public function storeProposal(array $formData)
	{
        $proposal = new WalkProposal();
        if ($this->getCanChooseLeader() && !empty($formData['leader']))
            $proposal->leader = (int)$formData['leader'];
        else 
            $proposal->leader = Leader::fromJoomlaUser(JFactory::getUser()->id);
        $proposal->programme = WalkProgramme::getNextProgrammeId(); // TODO: Selectable?
        $proposal->walk = (int)$formData['walkid'];
        $proposal->timingAndTransport = $formData['transport'];
        $proposal->comments = $formData['comments'];
        if (!empty($formData['backmarker']))
            $proposal->backmarker = (int)$formData['backmarker'];
        $proposal->populateDatesFromArray($formData['availability']);
        
//         echo "<pre>";
//         print_r($formData);
//         print_r($proposal);
//         die("agr");
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
