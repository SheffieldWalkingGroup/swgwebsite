<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

require_once JPATH_SITE."/swg/swg.php";

// Include dependancy of the main model form
jimport('joomla.application.component.modelform');
// import Joomla modelitem library
jimport('joomla.application.component.modelitem');
// Include dependancy of the dispatcher
jimport('joomla.event.dispatcher');

/**
 * AddEditWalk Model
 */
class SWG_LeaderUtilsModelAddEditProgramme extends JModelForm
{
	/**
	* The real programme object
	* @var WalkProgramme
	*/
	private $programme;

	/**
	* True if we're editing a programme, false if we're adding
	*/
	public function editing()
	{
		return (JRequest::getInt("id",0,"get") != 0);
	}
		
	/**
	* Update the current walk with passed in form data
	* This also handles GPX data
	*/
	public function updateProgramme(array $formData)
	{
		$this->loadProgramme($formData['id']);
		// Update all basic fields
		// Fields that can't be saved are just ignored
		// Invalid fields throw an exception - display this to the user and continue
		if (!isset($formData['special']))
            $formData['special'] = false;
        
		foreach ($formData as $name=>$value)
		{
			try
			{
				$this->programme->$name = $value;
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
	}
	
	/**
	* Loads the programme specified, or a blank one if none specified
	*/
	public function loadProgramme($id)
	{
		if (empty($id))
		{
			$this->programme = $this->createNextProgramme();
		}
		else
		{
			$this->programme = WalkProgramme::get($id);
		}
	}
	
	private function createNextProgramme()
	{
        $newProgramme = new WalkProgramme();
        
        $oldProgramme = WalkProgramme::get(WalkProgramme::getNextProgrammeId());
        $oldEndDate = clone($oldProgramme->endDate);
        $newStartDate = $oldEndDate->add(new DateInterval('P1D'));
        
        // Go to the end of the month, then step forward a day at a time to finish on a Friday or Sunday.
        $newEndDate = new DateTime($oldEndDate->format('Y-m-01'));
        $newEndDate->add(new DateInterval('P1M'));
        $newEndDate->sub(new DateInterval('P1D'));
        
        while ($newEndDate->format('N') != 6 && $newEndDate->format('N') != 1) {
            $newEndDate = $newEndDate->add(new DateInterval('P1D'));
        }
        
        $newProgramme->startDate = clone($newStartDate);
        $newProgramme->endDate = clone($newEndDate);
        $newProgramme->title = $newStartDate->format('F Y');
        
        return $newProgramme;
	}

	/**
	* Dumps the programme data as an array
	*/
	public function getProgramme()
	{
		// Load the programme if not already done
		if (!isset($this->programme))
		{
			$this->loadProgramme(JRequest::getInt("id",0,"get"));
		}
		return $this->programme;
	}

	/**
	* Get the form for entering a programme
	*/
	public function getForm($data = array(), $loadData = true)
	{
		$app = JFactory::getApplication('administrator');

		// Get the form.
		$form = $this->loadForm('com_swg_leaderutils.addeditprogramme', 'addeditprogramme', array('control' => 'jform', 'load_data' => true));
		if (empty($form)) {
			return false;
		}
		
		$programme = $this->getProgramme();
		
		// Bind existing walk data
		$form->bind($programme->valuesToForm());
		
		return $form;

	}

	public function updItem($data)
	{
		$this->programme->save();
	}
}
