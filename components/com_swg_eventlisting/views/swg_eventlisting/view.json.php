<?php
// What type of event to we want?
// TODO: Probably shouldn't return anything that isn't OK to publish - this is publicly accessible.
$type = JRequest::getVar('eventtype',null,"get");
$id = JRequest::getVar('id',null,"get","INTEGER");
if (!isset($id) || !isset($type))
  jexit("Type and ID must both be specified");
switch (strtolower($type)) {
  case "social":
    include_once(JPATH_BASE."/swg/Models/Social.php");
    $result = Social::getSingle($id);
    break;
  case "walk":
    include_once(JPATH_BASE."/swg/Models/WalkInstance.php");
    $result = WalkInstance::getSingle($id);
    break;
  case "weekend";
    include_once(JPATH_BASE."/swg/Models/Weekend.php");
    $result = Weekend::getSingle($id);
    break;
  default:
}

echo $result->jsonEncode();
die();