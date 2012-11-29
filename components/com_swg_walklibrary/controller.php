<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
// import Joomla controller library
jimport('joomla.application.component.controller');
/**
 * SWG_Walks Component Controller
 */
class SWG_WalkLibraryController extends JController
{
  /* Permissions checks */
  function canAdd()
  {
    return JFactory::getUser()->authorise("walk.add","com_swg_walklibrary");
  }
  
  function canEdit($walkOrID)
  {
    // TODO: Leaders can edit own walks
    return JFactory::getUser()->authorise("walk.editall","com_swg_walklibrary");
  }
}