<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// What type of event to we want?
// TODO: Probably shouldn't return anything that isn't OK to publish - this is publicly accessible.
$id = JRequest::getVar('id',null,"get","INTEGER");
if (!isset($id))
  jexit("ID must be specified");

include_once(JPATH_BASE."/swg/Models/Walk.php");
$result = Walk::getSingle($id);

echo $result->jsonEncode();
die();