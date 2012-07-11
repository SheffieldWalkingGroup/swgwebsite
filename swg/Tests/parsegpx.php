<?php
define("_JEXEC",true);
include_once("../Models/Walk.php");
$walk = new Walk();
$walk->loadRoute(file_get_contents("/home/peter/Documents/Outdoors/Derwent_hd_loop.gpx"), true);

echo "Distance: ".$walk->miles." miles (".$walk->distanceGrade.")\n";
if ($walk->isLinear) echo "Linear"; else echo "Circular"; echo "\n";
echo "Start: ".$walk->startGridRef." (".$walk->startPlaceName.")\n";
echo "End: ".$walk->endGridRef." (".$walk->endPlaceName.")\n";

