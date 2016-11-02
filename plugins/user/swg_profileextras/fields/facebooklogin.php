<?php

JLoader::register('Facebook', JPATH_SITE."/libraries/facebook/facebook.php");
JLoader::register('SWG', JPATH_SITE."/swg/swg.php");
/**
 * A facebook login button
 * Shows a login link if the user is logged in,
 * or their facebook name if they are logged in.
 * Optionally shows a logout/disconnect button if logged in.
 */
class JFormFieldFacebookLogin extends JFormField
{
	/** @var Facebook */
	private $fb;
	
	protected $type = "FacebookLogin";
	
	function __construct($form = null)
	{
		parent::__construct($form);
		$this->fb = SWG::getFacebook();
		if (!$this->fb)
			$this->fb = new Facebook(SWG::$fbconf);
			
		if ($this->fb->getUser())
		{
			$this->value = $this->fb->getAccessToken();
		}
		else
		{
			$this->value = "NONE";
		}
	}
	
	public function setup(&$element, $value, $group = null)
	{
		parent::setup($element, $value, $group);
		
 		return true;
	}
	
	public function __get($name)
	{
		if ($name == "value")
		{
			if ($this->fb->getUser())
			{
				$this->value = $this->fb->getAccessToken();
			}
			else
			{
				$this->value = "NONE";
			}
			return $this->value;
		}
		else
			return parent::__get($name);
	}
	
	public function getLabel()
	{
		return parent::getLabel();
	}
	
	public function getInput()
	{
		if ($this->fb->getUser())
		{
			$user = $this->fb->getUser();
			$profile = $this->fb->api("/me?fields=id,name,picture");
			$label = "<img src='".$profile['picture']['data']['url']."' />".$profile['name'];
			$logout = $this->fb->getLogoutUrl(array('next' => JURI::base()));
			return $label . "<br /><a href='".$logout."'>Log out of Facebook</a>";
		}
		else
		{
		    // Not logged in - display a login link
			$uri = JURI::current();
			$loginUrl = $this->fb->getLoginUrl(array(
				'redirect_uri'	=> "http://aquinas.dphin.co.uk/login/profile?layout=edit", // TODO: Needs to be an IP for development
			));
			
			return "<a href='".$loginUrl."'>Connect your Facebook account</a>";
		}
	}
	
	public function getValue()
	{
	}
}