<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// What type of event to we want?
// TODO: Probably shouldn't return anything that isn't OK to publish - this is publicly accessible.
$walkinstanceid = JRequest::getVar('walkinstanceid',null,"get","INTEGER");
$walkid = JRequest::getVar('walkid',null,"get","INTEGER");
$routeid = JRequest::getVar('routeid',null,"get","INTEGER");
if (!isset($walkid) && !isset($routeid) && !isset($walkinstanceid))
  jexit("Walk or route ID must be specified");

include_once(JPATH_BASE."/swg/Models/Route.php");
// If a routeID is specified, return that route.
if (isset($routeid))
{
  $route = Route::loadSingle($routeid);
  if (!empty($route))
  {
    print $route->jsonEncode();
    jexit();
  }
}
// If we get here, we failed to load a route.

// Get a route for a walkinstance (this means getting the walk)
if (isset($walkinstanceid))
{
  include_once(JPATH_BASE."/swg/Models/WalkInstance.php");
  $wi = WalkInstance::getSingle($walkinstanceid);
  $walkid = $wi->walkid;
}
// Get the route for a particular walk if walkid is set
if (isset($walkid))
{
  include_once(JPATH_BASE."/swg/Models/Walk.php");
  $walk = Walk::getSingle($walkid);
  $routes = Route::loadForWalkable($walk,false,1);
  if (!empty($routes))
    $result = $routes[0];
  else
    $result = false; // Error condition - notify caller there are no routes available
}

if ($result instanceof SWGBaseModel)
  echo $result->jsonEncode();
else
  echo json_encode($result);
jexit();