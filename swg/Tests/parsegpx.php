<?php
define("_JEXEC",true);
define("JPATH_BASE", "/home/peter/swg/website/public_html"); // TODO!
require_once("../swg.php");
include_once("../Models/Walk.php");
$walk = new Walk();
$route = new Route($walk);

$route->readGPX(DOMDocument::loadXML(file_get_contents($argv[1])));
$walk->setRoute($route);

echo "Distance: ".$walk->miles." miles (".$walk->distanceGrade.")\n";
if ($walk->isLinear) echo "Linear"; else echo "Circular"; echo "\n";
echo "Start: ".$walk->startGridRef." (".$walk->startPlaceName.")\n";
echo "End: ".$walk->endGridRef." (".$walk->endPlaceName.")\n";

$route->save();

