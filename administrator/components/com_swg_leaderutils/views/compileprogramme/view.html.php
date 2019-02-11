<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * HelloWorlds View
 */
class SWG_LeaderUtilsViewCompileProgramme extends JViewLegacy
{
  /**
   * HelloWorlds view display method
   * @return void
   */
  function display($tpl = null)
  {
    // Get data from the model
    $this->proposals = $this->get('Proposals');
    $this->programme = $this->get('Programme');

    // Check for errors.
    if (count($errors = $this->get('Errors')))
    {
      JError::raiseError(500, implode('<br />', $errors));
      return false;
    }
    
    // Add CSS
	$document =& JFactory::getDocument();
	$document->addStyleSheet('components/com_swg_leaderutils/css/compileprogramme.css');
    
	// Display the template
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
