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
	include_once(JPATH_BASE."/swg/Factories/WalkInstanceFactory.php");
	$f = SWG::walkInstanceFactory();
	$wi = $f->getSingle($walkinstanceid);
	$walkid = $wi->walkid;
}
// Get the route for a particular walk if walkid is set
if (!empty($walkid))
{
	include_once(JPATH_BASE."/swg/Models/Walk.php");
	$walk = Walk::getSingle($walkid);
	$routes = Route::loadForWalkable($walk,false,1);
	if (!empty($routes))
	{
		// Check the leader has allowed walk downloads
		// TODO: Leaders can download more routes than normal members
		// TODO: Check visibility on the walkinstance instead where appropriate
		if ($walk->routeVisibility < Route::Visibility_Members)
		{
			header("HTTP/1.0 403 Forbidden");
			echo "Sorry, you can't download that route.";
			jexit();
		}
		
		header("Content-Type: application/gpx+xml");
		header("Content-Disposition: attachment; filename=\"{$walk->name}.gpx\"");
		$route = $routes[0]->writeGPX();
		echo $route->saveXML();
	}
	else
	{
		header("HTTP/1.0 404 Not Found");
	}
}

jexit();