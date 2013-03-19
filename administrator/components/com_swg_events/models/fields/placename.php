<?php

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');
 
jimport('joomla.form.formfield');

/**
 * A text field that integrates with Nominatim for placename lookups
 */
class JFormFieldPlaceName extends JFormFieldText
{
	protected $type = "PlaceName";
	
	public function getInput()
	{
		// Load Nominatim Javascript
		// Use our passthrough so we can add our own database later
		$document = JFactory::getDocument();
		JHtml::_('behavior.framework', true);
		$document->addScript(JURI::base()."administrator/components/com_swg_events/models/fields/placename.js");
		$document->addScriptDeclaration(<<<PNF
window.addEvent("domready", function()
{
	new JFormFieldPlaceName("{$this->id}", "{$this->id}_button");
});
PNF
);
		$input = parent::getInput();
		if (isset($this->element['searchLabel']))
			$searchLabel = $this->element['searchLabel'];
		else
			$searchLabel = "Search";
		return $input."<input type='button' id='".$this->id."_button' value='".$searchLabel."' />";
	}
}