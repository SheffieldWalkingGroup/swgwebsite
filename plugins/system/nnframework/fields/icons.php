<?php
/**
 * Element: Radio Images
 * Displays a list of radio items and the images you can chose from
 *
 * @package         NoNumber Framework
 * @version         14.2.6
 *
 * @author          Peter van Westen <peter@nonumber.nl>
 * @link            http://www.nonumber.nl
 * @copyright       Copyright © 2014 NoNumber All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

class JFormFieldNN_Icons extends JFormField
{
	public $type = 'Icons';
	private $params = null;

	protected function getInput()
	{
		$this->params = $this->element->attributes();
		$value = !is_array($this->value) ? explode(',', $this->value) : $this->value;

		$classes = array(
			'nonumber icon-contenttemplater',
			'home',
			'user',
			'locked',
			'comments',
			'comments-2',
			'out',
			'plus',
			'pencil',
			'pencil-2',
			'file',
			'file-add',
			'file-remove',
			'copy',
			'folder',
			'folder-2',
			'picture',
			'pictures',
			'list-view',
			'power-cord',
			'cube',
			'puzzle',
			'flag',
			'tools',
			'cogs',
			'cog',
			'equalizer',
			'wrench',
			'brush',
			'eye',
			'star',
			'calendar',
			'calendar-2',
			'help',
			'support',
			'warning',
			'checkmark',
			'mail',
			'mail-2',
			'drawer',
			'drawer-2',
			'box-add',
			'box-remove',
			'search',
			'filter',
			'camera',
			'play',
			'music',
			'grid-view',
			'grid-view-2',
			'menu',
			'thumbs-up',
			'thumbs-down',
			'plus-2',
			'minus-2',
			'key',
			'quote',
			'quote-2',
			'database',
			'location',
			'zoom-in',
			'zoom-out',
			'health',
			'wand',
			'refresh',
			'vcard',
			'clock',
			'compass',
			'address',
			'feed',
			'flag-2',
			'pin',
			'lamp',
			'chart',
			'bars',
			'pie',
			'dashboard',
			'lightning',
			'move',
			'printer',
			'color-palette',
			'camera-2',
			'cart',
			'basket',
			'broadcast',
			'screen',
			'tablet',
			'mobile',
			'users',
			'briefcase',
			'download',
			'upload',
			'bookmark',
			'out-2'
		);

		$html = array();

		if ($this->get('show_none'))
		{
			$checked = (in_array('0', $value) ? ' checked="checked"' : '');
			$html[] = '<fieldset>';
			$html[] = '<input type="radio" id="' . $this->id . '0" name="' . $this->name . '"' . ' value="0"' . $checked . '/>';
			$html[] = '<label for="' . $this->id . '0">' . JText::_('NN_NO_ICON') . '</label>';
			$html[] = '</fieldset>';
		}

		foreach ($classes as $i => $class)
		{
			$checked = (in_array($class, $value) ? ' checked="checked"' : '');
			$html[] = '<fieldset class="pull-left">';
			$html[] = '<input type="radio" id="' . $this->id . $class . '" name="' . $this->name . '"'
				. ' value="' . htmlspecialchars($class, ENT_COMPAT, 'UTF-8') . '"' . $checked . '/>';
			$html[] = '<label for="' . $this->id . $class . '" class="btn btn-small"><span class="icon-' . $class . '"></span></label>';
			$html[] = '</fieldset>';
		}

		return '<div id="' . $this->id . '" class="btn-group radio nn_icon_group">' . implode('', $html) . '</div>';
	}

	private function get($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}
