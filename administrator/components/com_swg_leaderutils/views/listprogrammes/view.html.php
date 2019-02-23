<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * HTML Event listing class for the SWG Events component
 */
class SWG_LeaderUtilsViewListProgrammes extends JViewLegacy
{

	// Overwriting JViewLegacy display method
	function display($tpl = null) 
	{
		// Assign data to the view
		$this->programmes = $this->get('Programmes');

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
