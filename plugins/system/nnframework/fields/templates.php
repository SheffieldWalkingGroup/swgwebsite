<?php
/**
 * Element: Templates
 * Displays a select box of templates
 *
 * @package         NoNumber Framework
 * @version         12.9.7
 *
 * @author          Peter van Westen <peter@nonumber.nl>
 * @link            http://www.nonumber.nl
 * @copyright       Copyright © 2012 NoNumber All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;

class JFormFieldNN_Templates extends JFormField
{
	public $type = 'Templates';

	protected function getInput()
	{
		$this->params = $this->element->attributes();

		$size = (int) $this->def('size');
		$multiple = $this->def('multiple');
		$subtemplates = $this->def('subtemplates', 1);
		$show_system = $this->def('show_system', 1);

		require_once JPATH_ADMINISTRATOR . '/components/com_templates/helpers/templates.php';
		$rows = TemplatesHelper::getTemplateOptions('0');
		$options = $this->createList($rows, JPATH_ROOT . '/templates', $subtemplates, $show_system);

		require_once JPATH_PLUGINS . '/system/nnframework/helpers/html.php';
		return nnHTML::selectlist($options, $this->name, $this->value, $this->id, $size, $multiple);
	}

	function createList($rows, $templateBaseDir, $subtemplates = 1, $show_system = 1)
	{
		$options = array();

		if ($show_system) {
			$options[] = JHtml::_('select.option', 'system:component', JText::_('None') . ' (System - component)');
		}

		foreach ($rows as $option) {
			$options[] = $option;

			if ($subtemplates) {
				$options_sub = $this->getSubTemplates($option, $templateBaseDir);
				$options = array_merge($options, $options_sub);
			}
		}
		return $options;
	}

	function getSubTemplates($option, $templateBaseDir)
	{
		$options = array();
		$templateDir = dir($templateBaseDir . '/' . $option->value);
		while (false !== ($file = $templateDir->read())) {
			if (is_file($templateDir->path . '/' . $file)) {
				if (!(strpos($file, '.php') === false) && $file != 'index.php') {
					$file_name = str_replace('.php', '', $file);
					if ($file_name != 'index' && $file_name != 'editor' && $file_name != 'error') {
						$options[] = JHtml::_('select.option', $option->value . ':' . $file_name, '&nbsp;&nbsp;' . $file_name);
					}
				}
			}
		}
		$templateDir->close();

		return $options;
	}

	private function def($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}