<?php

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('calendar');

class JFormFieldYearMonth extends JFormFieldCalendar
{
	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   11.1
	 */
	protected function getInput()
	{
		// Translate placeholder text
		$hint = $this->translateHint ? JText::_($this->hint) : $this->hint;

		// Initialize some field attributes.
		$format = $this->format;

		// Build shared the attributes array.
		$attributes = array();

		empty($this->class)     ? null : $attributes['class'] = $this->class;
		!$this->readonly        ? null : $attributes['readonly'] = 'readonly';
		!$this->disabled        ? null : $attributes['disabled'] = 'disabled';
		empty($this->onchange)  ? null : $attributes['onchange'] = $this->onchange;
		empty($hint)            ? null : $attributes['placeholder'] = $hint;
		
		if ($this->required)
		{
			$attributes['required'] = '';
			$attributes['aria-required'] = 'true';
		}
		
		// Parse 'NOW' to current time
		if (strtoupper($this->value) == 'NOW')
			$this->value = strftime("%Y-%m");
		
		// Get month and year parts
		if (!empty($this->value))
		{
			$yearVal = substr($this->value, 0, strpos($this->value,"-"));
			$monthVal = substr($this->value, strpos($this->value,"-")+1);
		}
		else
		{
			$yearVal = 0;
			$monthVal = 0;
		}
		

		// Create the options
		$monthOpts = array("-- Month --", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
		
		if (isset($this->element['startYear']))
			$startYear = (int)$this->element['startYear'];
		else if (isset($this->element['startRelYear']))
			$startYear = strftime("%Y") + (int)$this->element['startRelYear'];
		else
			$startYear = strftime("%Y")-10;
		if (isset($this->element['stopYear']))
			$stopYear = (int)$this->element['stopYear'];
		else if (isset($this->element['stopRelYear']))
			$stopYear = strftime("%Y") + (int)$this->element['stopRelYear'];
		else
			$stopYear = strftime("%Y")+10;
			
		$yearOpts = array("-- Year -- ");
		for ($i = $startYear; $i <= $stopYear; $i++)
		{
			$yearOpts[$i] = $i;
		}
		
		// Create the lists
		$monthAttr = $attributes;
		if ($this->autofocus)
			$monthAttr['autofocus'] = 'autofocus';
		$monthAttr['onchange'] = "var el = document.getElementById('{$this->id}'); el.value = el.value.substr(0, el.value.indexOf('-')) + '-' + this.value;";
		$yearAttr = $attributes;
		$yearAttr['onchange'] = "var el = document.getElementById('{$this->id}'); el.value = this.value + '-' + el.value.substr(el.value.indexOf('-')+1);";
		
		// Joomla can't run multiple fields as one, so we use this hidden field. JS updates it when the dropdowns change, it stores the date in %Y-%m format.
		$date = '<input type="hidden" name="' . $this->name . '" id="' . $this->id . '" value="'.$this->value.'" />';
		$month = JHtml::_('select.genericlist', $monthOpts, $this->name."_month", $monthAttr, null, null, $monthVal, $this->id."_month");
		$year = JHtml::_('select.genericlist', $yearOpts, $this->name."_year", $yearAttr, null, null, $yearVal, $this->id."_year");
		return $date . $month . $year;
	}
}