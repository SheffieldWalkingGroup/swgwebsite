<?php

require_once("SWGBaseModel.php");
include_once(JPATH_BASE."/swg/lib/phpcoord/phpcoord-2.3.php");

class Waypoint extends SWGBaseModel
{
	/**
	* Lat/long coordinates in WGS84
	* @var LatLng
	*/
	private $latLng;

	/**
	* OS northing/easting coordinates in OSGB36
	* @var OSRef
	*/
	private $osRef;

	/**
	* Altitude in metres
	* @var int
	*/
	private $alt;

	/**
	* Timestamp for this waypoint. Can be set as a Unix timestamp or any string parsed by strtotime.
	* @var int
	*/
	private $time;

	public function __set($name, $value)
	{
		switch ($name)
		{
			case "latLng":
				if ($value instanceof LatLng)
				{
					$this->latLng = clone $value;
					// Also convert to osRef
					$value->WGS84ToOSGB36();
					$osRef = $value->toOSRef();
					$this->osRef =& $osRef;
				}
				break;
			case "osRef":
				if ($value instanceof OSRef)
				{
					$this->osRef = clone $value;
					// Also convert to osRef
					$latLng = $value->toLatLng();
					$latLng->OSGB36ToWGS84();
					$this->latLng =& $latLng;
				}
				break;
			case "altitude":
				$this->altitude = (int)$value;
				break;
			case "time":
				if (is_numeric($value))
					$this->time = (int)$value;
				else if (strtotime($value))
					$this->time = strtotime($value);
				break;
		}
	}
		
	public function __get($name)
	{
		return $this->$name;
	}

	/**
	* Attempts to get a place name by reverse geocoding.
	* TODO: Look in our own database for common points
	* If we don't have a reference for this point in our own database,
	* we use OpenStreetMap's Nominatim API (CC-BY-SA)
	* See http://wiki.openstreetmap.org/wiki/Nominatim#Reverse_Geocoding_.2F_Address_lookup
	* We only use the returned value if it's one of the following, in this order (first is kept):
	* * information
	* * parking
	* * building
	* * townhall
	* If none of these match, the following combinations are also valid:
	* * place_of_worship, suburb
	* * bus_stop, suburb
	* * pub, suburb
	* * cafe, suburb
	* (Note: Suburb is usually the village name, e.g. Tideswell CP)
	* TODO: Maybe display location type, e.g. pub, car park...
	*/
	public function reverseGeocode()
	{
		$validLocationTypes = array(
			"information","building",
			"townhall",
		);

		$backupLocations = array(
			"place_of_worship",
			"bus_stop",
			"bus_station",
			"parking",
			"pub",
			"cafe",
			"library",
			"school",
			"university",
			"fuel",
			"fountain",
			"clock",
			"marketplace",
			"police",
			"post_office",
			"shelter",
			"toilets"
		);
		$return = false; // We return false if no suitable place found

		// TODO: Our own database

		// Connect to Nominatim with CURL, get results in XML format
		$options = array(
			'format=xml',
			"lat=".$this->latLng->lat,
			"lon=".$this->latLng->lng,
			'addressdetails=1',
		);

		$curl = curl_init("http://nominatim.openstreetmap.org/reverse?".implode("&", $options));
		curl_setopt($curl,CURLOPT_USERAGENT, "Sheffield Walking Group - admin contact tech@sheffieldwalkinggroup.org.uk");
		curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);

		$res = curl_exec($curl);
		if ($res)
		{
			// Use the DOM parser
			$result = DomDocument::loadXML($res);
			$address = $result->getElementsByTagName("addressparts")->item(0)->childNodes;

			// Get a suitable place name
			$possibleLocation = ""; // Store second-class location data here until we find something better
			foreach($address as $addressPart)
			{
				if (in_array($addressPart->nodeName, $validLocationTypes))
				{
					$return = $addressPart->nodeValue;
					break;
				}

				if (empty($possibleLocation) && in_array($addressPart->nodeName, $backupLocations))
				{
					$possibleLocation = $addressPart->nodeValue;

					if ($addressPart->nodeName != "suburb")
					{
						$suburbs = $result->getElementsByTagName("addressparts")->item(0)->getElementsByTagName("suburb");
						if (!empty($suburbs) && !empty($suburbs->item(0)->nodeValue))
						{
							$suburb = $suburbs->item(0)->nodeValue;
							// Strip out "CP" if present
							$suburb = trim(str_replace("CP", "", $suburb));
							$possibleLocation.= ", ".$suburb;
						}
					}
				}
			}

			if (!empty($possibleLocation))
				return $possibleLocation;
		}
		else
		{
			// FIXME: Error handling
			var_dump(curl_error($curl));
			die();
		}

		return $return;
	}

	/**
	* Calculates the distance between two waypoints
	* Assumes waypoints are close enough to use Pythagoras' Theorem
	* Does not include vertical distance.
	* 
	* @param Waypoint $w Other waypoint
	* @return int Distance in metres
	*/
	public function distanceTo(Waypoint $w)
	{
		$deltaEasting = $this->osRef->easting - $w->osRef->easting;
		$deltaNorthing = $this->osRef->northing - $w->osRef->northing;
		return sqrt(pow($deltaEasting,2) + pow($deltaNorthing,2));
	}

	/**
	* Gets a nested array of all non-private properties
	* Adds support for LatLng - reformatted to lat and lng (floats)
	*/
	public function sharedProperties() {
		$properties = parent::sharedProperties();

		$properties['lat'] = $this->latLng->lat;
		$properties['lng'] = $this->latLng->lng;
		return $properties;
	}
}
