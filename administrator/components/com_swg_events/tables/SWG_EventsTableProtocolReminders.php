<?php 
// No direct access
defined('_JEXEC') or die('Restricted access');
 
// import Joomla table library
jimport('joomla.database.table');
 
/**
 * Hello Table class
 */
class HelloWorldTableHelloWorld extends JTable
{
    /**
     * Constructor
     *
     * @param object Database connector object
     */
    function __construct(&$db) 
    {
        parent::__construct('#__swg_events_protocolreminders', 'id', $db);
    }
}