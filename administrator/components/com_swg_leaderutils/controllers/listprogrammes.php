<?php
// No direct access.
defined('_JEXEC') or die;

// Include dependancy of the main controllerform class
jimport('joomla.application.component.controllerform');
// Include the actual search class

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
}
