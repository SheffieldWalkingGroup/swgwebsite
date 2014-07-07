<?php
class UnitConvert
{
	const Metre = 0;
	const Kilometre = 1;
	const Mile = 10;
	const Brontosaurus = 50;
	const Tram = 51;
	const London = 100;
	const World = 150;
	
	/**
	 * Array of distance units.
	 * Key: internal name
	 * Value: array of 'name', 'symbol', 'factor', 'pluralname'
	 * Where factor is the number of metres in one of this unit
	 * Pluralname is optional - if not specified, 's' is appended to the standard name
	 */
	private static $distance = array(
		self::Metre		=> array('name' => "metre", 'symbol' => "m", 'factor' => 1,),
		self::Kilometre	=> array('name' => "kilometre", 'symbol' => "km", 'factor' => 1000,'format' => ".0f"),
		self::Mile			=> array('name' => "mile", 'symbol' => 'mi', 'factor' => 1609.34,),
		self::Brontosaurus	=> array('name' => "brontosaurus", 'symbol' => "br", 'factor' => 138.2851, 'pluralname' => "brontosauruses",'format' => ".0f"),
		self::Tram			=> array('name' => "supertram", 'symbol' => "tram", "factor" => 34.8,'format' => ".0f"),
		self::London	=> array('name' => "time to London", 'symbol' => "London", 'factor' => 228018, 'pluralname' => "times to London",'format' => ".2f"),
		self::World		=> array('name' => "time round the world", 'symbol' => "C♁", 'factor' => 4e7, 'pluralname' => "times round the world", 'format' => ".3f"),
	);
	
	public static function getUnit($unit, $what)
	{
		if (array_key_exists($unit, self::$distance) && array_key_exists($what, self::$distance[$unit]))
			return self::$distance[$unit][$what];
	}
	
	/**
	 * Converts a distance
	 * @param float $input Input value
	 * @param int $inUnit Input units. See constants
	 * @param int $outUnit Output units. See constants
	 */
	public static function distance($input, $inUnit, $outUnit)
	{
		if ($inUnit == $outUnit)
			return $input;
		
		if (!isset(self::$distance[$inUnit]))
			throw new InvalidArgumentException("Input unit is not valid.");
		if (!isset(self::$distance[$outUnit]))
			throw new InvalidArgumentException("Output unit it not valid.");
		
		$metres = $input * self::$distance[$inUnit]['factor'];
		$output = $metres / self::$distance[$outUnit]['factor'];
		return $output;
	}
	
	public static function displayDistance($input, $inUnit, $outUnit, $abbrevUnits = true, $abbr = true)
	{
		if (isset(self::$distance[$outUnit]['format']))
			$format = self::$distance[$outUnit]['format'];
		else
		    $format = ".1f";
		
		$output = self::distance($input, $inUnit, $outUnit);
		
		$unit = self::$distance[$outUnit]['name'];
		if ($output != 1)
		{
			if (isset(self::$distance[$outUnit]['pluralname']))
				$unit = self::$distance[$outUnit]['pluralname'];
			else
				$unit = $unit."s";
		}
		
		if ($abbrevUnits)
		{
			$symbol = self::$distance[$outUnit]['symbol'];
			if ($abbr)
				$format = '%1$'.$format.'<abbr title=\'%3$s\'>%2$s</abbr>';
			else
				$format = '%1$'.$format.'%2$s';
			return sprintf($format, $output, $symbol, $unit); // No space
			
		}
		else
		{
			return sprintf('%1$'.$format.' %2$s', $output, $unit); // Note space
		}
	}

}