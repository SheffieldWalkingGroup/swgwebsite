<?php

require_once("swg/swg.php");
/**
 * A base model providing global functionality
 */
abstract class SWGBaseModel {

	/**
	* Adds fields on this event to a query being prepared to go into the database
	* @param JDatabaseQuery &$query Query being prepared. Modified in place.
	*/
	public function toDatabase(JDatabaseQuery &$query)
	{
		foreach ($this->dbmappings as $var => $dbField)
		{
			if (isset($this->$var))
				$query->set($dbField." = '".$query->escape($this->$var)."'");
		}
	}

	public function fromDatabase(array $dbArr)
	{
		foreach ($this->dbmappings as $var => $dbField)
		{
			$this->$var = $dbArr[$dbField];
		}
	}
  
	/**
	* Performs JSON encoding of this object.
	* Needed because we use protected properties with read-only access
	*/
	public function jsonEncode() {
		return json_encode($this->sharedProperties());
	}
	
	/**
	* Gets a nested array of all non-private properties
	*/
	public function sharedProperties() {
		$properties = array();
		
		foreach ($this as $key => $value) {
			// If the current property is another SWG Model, ask it to JSON encode itself
			if ($value instanceof SWGBaseModel) {
				$properties[$key] = $value->sharedProperties();
			} else if ($key != "dbmappings") {
				$properties[$key] = $value;
			}
		}
		return $properties;
	}
}