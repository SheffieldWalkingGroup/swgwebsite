<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * HTML Event listing class for the SWG Events component
 */
class SWG_WalkLibraryViewListWalks extends JView
{
	private $showList = false;
	private $showSearch = true;
	private $noWalksMsg;

	// Overwriting JView display method
	function display($tpl = null) 
	{
		// Assign data to the view
		$this->walks = $this->get('Walks');
		$this->controller = JController::getInstance('SWG_WalkLibrary');

		// Check for errors.
		if (count($errors = $this->get('Errors'))) 
		{
			JError::raiseError(500, implode('<br />', $errors));
			return false;
		}

		// Set the template & title
		switch(JRequest::getInt("initialView"))
		{
			case 0:
				$this->pageTitle =  "List walks";
				$this->showList = true;
				$this->showSearch = false;
				$this->noWalkMsg = "No walks found";
				break;
			case 1:
				// Get this leader's record
				// TODO: Integrate with Joomla users
				$leader = Leader::fromJoomlaUser(JFactory::getUser()->id);
				if ($leader == null)
				{
					$this->pageTitle = "Walks you've suggested";
					$this->showList = true;
					$this->showSearch = false;
					$this->noWalkMsg = "Your leader account hasn't been set up properly. Please contact the vice chair or webmaster.";
				}
				else
				{
					$this->pageTitle = "Walks suggested by ".$leader->displayName;
					$this->showList = true;
					$this->showSearch = false;
					$this->noWalkMsg = "You haven't suggested any walks yet";
				}
				break;
			case 2:
				$this->pageTitle = "Suggested walks";
				$this->showList = true;
				$this->showSearch = false;
				$this->noWalkMsg = "No walks have been suggested";
				break;
			case 3:
			default:
				$this->pageTitle = "Walks library";
				$this->noWalkMsg = "No walks found";
				if ($this->getModel()->hasWalkList())
				{
					$this->showList = true;
					$this->showSearch = false;
				}
				else
				{
					$this->showList = false;
					$this->showSearch = true;
				}
				break;
		}

		// Set page heading.
		// TODO: Fix hack?
		JFactory::getApplication()->getMenu()->getActive()->params->set("page_heading", $this->pageTitle);

		// Display the view
		parent::display($tpl);
	}

	protected function pageTitle()
	{
		return $this->pageTitle;
	}

	public function showList() { return $this->showList; }
	public function showSearch() { return $this->showSearch; }
	public function noWalksMsg() { return $this->noWalkMsg; }
	public function showSearchResults() { return $this->getModel()->hasWalkList(); }

	public function urlToView(Walk $walk) { return $this->walkURL($walk,"walkdetails"); }
	public function urlToEdit(Walk $walk) { return $this->walkURL($walk,"addeditwalk"); }
	private function walkURL(Walk $walk, $view)
	{
	// Get the current URL. We want to strip off anything in the parameters except a component
	$url = $_SERVER['REQUEST_URI'];

	// Get the current URL parameters
	if (strpos($url, "?") !== false)
	{
		$inParams = explode("&", substr($url,strpos($url,"?")+1));
		$urlBase = substr($url,0,strpos($url,"?"));
	}
	else
	{
		$inParams = array();
		$urlBase = $url;
	}

	// Build the new URL parameters
	$params = array(
		"view=".$view,
		"walkid=".$walk->id,
	);

	if (isset($inParams['option']))
		$params['option'] = $inParams['option'];

	return $urlBase."?".implode("&amp;", $params);
	}
}