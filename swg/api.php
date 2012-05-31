<?php
/**
 * This is the API to the walks database.
 * The following parameters are required, and must be provided as GET parameters:
 *   action      Specifies what action to take:
 *     (getevent)
 *   format      Specifies the format data should be returned in
 *     (json) 
 * 
 * Supported actions:
 *   getevent  Gets details about a single event
 *     Parameters:
 *       type  Type of event to load (GET, mandatory) (social|walkinstance|weekend)
 *       id    ID of event to load (GET, mandatory)
 */

define("SWG_PATH",$_SERVER['DOCUMENT_ROOT']."/swg/");

try {
  switch (strtolower($_GET['action'])) {
    case "getevent":
      switch(strtolower($_GET['type'])) {
        case "social":
          include_once(SWG_PATH."Models/Social.php");
          break;
      }
  }
}