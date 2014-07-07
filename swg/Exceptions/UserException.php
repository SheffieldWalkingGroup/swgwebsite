<?php

/**
 * Exception caused by user action or input
 * Includes extra fields so we can explain what they did wrong and how to fix it
 */
class UserException extends RuntimeException
{
	private $subHead;
	private $details;
	
	public function __construct($message = "", $code = 0, $subHead = "", $details = "", Exception $previous = null)
	{
		$this->subHead = $subHead;
		$this->details = $details;
		parent::__construct($message, $code, $previous);
	}
	
	public function getSubHead()
	{
		return $this->subHead;
	}
	
	public function getDetails()
	{
		return $this->details;
	}
}