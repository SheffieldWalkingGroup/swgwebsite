<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');
// import the Joomla modellist library
jimport('joomla.application.component.modellist');
/**
 * HelloWorldList Model
 */
class SWG_EventsModelSWG_Events extends JModelList
{
  /**
   * Method to build an SQL query to load the list data.
   *
   * @return	string	An SQL query
   */
  protected function getListQuery()
  {
    // Create a new query object.
    $db = JFactory::getDBO();
    $query = $db->getQuery(true);
    // Select some fields
    $query->select('id,text,eventtype,ordering');
    // From the hello table
    $query->from('#__swg_events_protocolreminders');
    return $query;
  }
}