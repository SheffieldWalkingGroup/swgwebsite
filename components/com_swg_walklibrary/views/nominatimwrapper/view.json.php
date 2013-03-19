<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

include_once(JPATH_BASE."/swg/Models/Waypoint.php");

// We can look up a grid reference, a lat/long, or a location name
// For a grid reference of a lat/long, do a reverse geocode by instantiating a Waypoint.
// Location name search is done here for now - may be moved elsewhere when appropriate

/**
 * We can look up a grid reference, a lat/long, a postcode, or a location name
 *
 * Grid reference: specify a grid reference as a string (including the letters), parameter 'gridref' e.g. ?gridref=SK123456
 * Northing/Easting: specify as a pair of integer parameters, e.g. ?north=123456&east=123456
 * Lat/long: specify as a pair of float parameters, e.g. ?lat=53.21415&lon=-1.52135
 * Reverse geocoding searches are done by instansiating a waypoint and using the reverseGeocode() method.
 *
 * Postcode: specify as a string. Any spaces will be removed, e.g. ?postcode=S314AF
 * Location name: specify as a string. String is passed directly to Nominatim, e.g. ?search=Ladybower+inn
 * Scope: scope can optionally be limited to Sheffield (1), or the general area for walks (2). e.g. ?search=Ladybower+inn&scope=2 TODO: Use constants on some appropriate class
 * This is only a preference, nominatim will return results outside this area if necessary.
 * Searches are always limited to the UK.
 * Location name search is done here for now - may be moved elsewhere when appropriate
 */
 
header("Content-type: application/json");

$scopeSheffield = 1;
$scopeGeneralArea = 2;

if (
	!empty($_GET['lat']) && !empty($_GET['lon']) ||
	!empty($_GET['east']) && !empty($_GET['north']) ||
	!empty($_GET['gridref'])
)
{
	$wp = new Waypoint();
	if (!empty($_GET['lat']) && !empty($_GET['lon']))
		$wp->latLng = new LatLng((float)$_GET['lat'], (float)$_GET['lon']);
	else if (!empty($_GET['east']) && !empty($_GET['north']))
	{
		$osRef = new OSRef((int)$_GET['east'], (int)$_GET['north']);
		$latLng = $osRef->toLatLng();
		$latLng->OSGB36ToWGS84();
		$wp->latLng = $latLng;
	}
	else if (!empty($_GET['gridref']))
	{
		$osRef = getOSRefFromSixFigureReference($_GET['gridref']);
		$latLng = $osRef->toLatLng();
		$latLng->OSGB36ToWGS84();
		$wp->latLng = $latLng;
	}
	
	// Now do a reverse geocode & return the result
	$placeName = $wp->reverseGeocode();
	echo json_encode($placeName);
}
else if (
	!empty($_GET['postcode']) || !empty($_GET['search'])
)
{

	$options = array(
		"format=json",
		"accept-language=en-gb,en",
		"countrycodes=gb",
	);
	
// 	if (!empty($_GET['scope']))
// 	{
// 		if ($_GET['scope'] == $scopeSheffield)
// 			$viewbox = array('l'=>-1.6, 't'=>53.45, 'r'=>-1.3, 'b'=>53.3);
// 		else if ($_GET['scope'] == $scopeGeneralArea)
// 			$viewbox = array('l'=>-2.1, 't'=>53.6, 'r'=>-1.2, 'b'=>53.0);
// 		
// 		$options[] = "viewbox=".implode(",", $viewbox);
// 	}
	
	if (!empty($_GET['postcode']))
	{
		// Strip spaces and validate
		$postcode = str_upper(str_replace(" ","", $_GET['postcode']));
		if (!preg_match("/^[A-Z]{1,2}[0-9]{1,2}[A-Z]?[0-9][A-Z^CIKMOV]{2}$/", $postcode))
		{
			throw new InvalidArgumentException("Invalid postcode");
		}
		
		$options[] = "postalcode=".$postcode;
	}
	else
	{
		// Strip out any dangerous things
		// Only rawurlencode seems to encode things in the way Nominatim likes them
		$search = rawurlencode(strip_tags($_GET['search']));
	}
	
	// Send the request to Nominatim
	
	$curl = curl_init("http://nominatim.openstreetmap.org/search/".$search."?".implode("&", $options));
	curl_setopt($curl,CURLOPT_USERAGENT, "Sheffield Walking Group - admin contact tech@sheffieldwalkinggroup.org.uk");
	curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
	$res = curl_exec($curl);
	
	// Pass the results back unedited
	echo $res;
	
}
else
{
	echo "Must give a location or a search string";
}

$mainframe =& JFactory::getApplication();
$mainframe->close();
