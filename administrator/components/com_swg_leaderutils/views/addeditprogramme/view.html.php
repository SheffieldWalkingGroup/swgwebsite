<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla view library
jimport('joomla.application.component.view');
jimport('joomla.form.form');
 
/**
 * HTML Add/Edit walk class for the SWG Walk Library component
 * 
 * The form has a file input near the top for a GPX route, and two submit buttons:
 * one near the file upload and one at the bottom. The file upload button processes
 * the GPX file only, whereas the main button processes the rest of the form. 
 * When a GPX file is uploaded, any blank fields are filled in and if the option to
 * overwrite existing data is enabled, all known fields are filled in. 
 */
class SWG_LeaderUtilsViewAddEditProgramme extends JViewLegacy
{
	
	// Overwriting JViewLegacy display method
	function display($tpl = null) 
	{
		// Assign data to the view
		$this->form	= $this->get('Form');
		$this->programme = $this->get('Programme');
		$model = $this->getModel('addeditprogramme');
		$this->editing = $model->editing();
		$this->showForm = true;

		// Check for errors.
		if (count($errors = $this->get('Errors'))) 
		{
			JError::raiseError(500, implode('<br />', $errors));
			return false;
		}

		// Display the view
		parent::display($tpl);
	}

	/**
   * Setting the toolbar
   */
    protected function addToolBar()
    {
        JToolBarHelper::title(JText::_('SWG Leader Utils'), 'swg_leaderutils');
        JToolBarHelper::preferences('com_swg_leaderutils');
    }
    /**
    * Method to set up the document properties
    *
    * @return void
    */
    protected function setDocument()
    {
        $document = JFactory::getDocument();
        $document->setTitle(JText::_('SWG LeaderUtils'));
    }
}
