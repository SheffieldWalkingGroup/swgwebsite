<?php
/**
 * Element: Header
 * Displays a title with a bunch of extras, like: description, image, versioncheck
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

require_once JPATH_PLUGINS . '/system/nnframework/helpers/text.php';

class JFormFieldNN_Header extends JFormField
{
	public $type = 'Header';
	private $params = null;

	protected function getLabel()
	{
		return '';
	}

	protected function getInput()
	{
		$this->params = $this->element->attributes();

		JHtml::stylesheet('nnframework/style.min.css', false, true);

		$title = $this->get('label');
		$description = $this->get('description');
		$xml = $this->get('xml');
		$url = $this->get('url');

		if ($description)
		{
			// variables
			$v1 = $this->get('var1');
			$v2 = $this->get('var2');
			$v3 = $this->get('var3');
			$v4 = $this->get('var4');
			$v5 = $this->get('var5');

			$description = NNText::html_entity_decoder(trim(JText::sprintf($description, $v1, $v2, $v3, $v4, $v5)));
		}

		if ($title)
		{
			$title = JText::_($title);
		}

		if ($description)
		{
			$description = str_replace('span style="font-family:monospace;"', 'span class="nn_code"', $description);
			if ($description['0'] != '<')
			{
				$description = '<p>' . $description . '</p>';
			}
		}

		if (!$xml && $this->form->getValue('element'))
		{
			if ($this->form->getValue('folder'))
			{
				$xml = 'plugins/' . $this->form->getValue('folder') . '/' . $this->form->getValue('element') . '/' . $this->form->getValue('element') . '.xml';
			}
			else
			{
				$xml = 'administrator/modules/' . $this->form->getValue('element') . '/' . $this->form->getValue('element') . '.xml';
			}
		}

		if ($xml)
		{
			$xml = JApplicationHelper::parseXMLInstallFile(JPATH_SITE . '/' . $xml);
			$version = 0;
			if ($xml && isset($xml['version']))
			{
				$version = $xml['version'];
			}
			if ($version)
			{
				if (!(strpos($version, 'PRO') === false))
				{
					$version = str_replace('PRO', '', $version);
					$version .= ' <small style="color:green">[PRO]</small>';
				}
				else if (!(strpos($version, 'FREE') === false))
				{
					$version = str_replace('FREE', '', $version);
					$version .= ' <small style="color:green">[FREE]</small>';
				}
				if ($title)
				{
					$title .= ' v';
				}
				else
				{
					$title = JText::_('Version') . ' ';
				}
				$title .= $version;
			}
		}
		$html = array();

		if ($title)
		{
			if ($url)
			{
				$title = '<a href="' . $url . '" target="_blank" title="' . preg_replace('#<[^>]*>#', '', $title) . '">' . $title . '</a>';
			}
			$html[] = '<h4>' . NNText::html_entity_decoder($title) . '</h4>';
		}
		if ($description)
		{
			$html[] = $description;
		}
		if ($url)
		{
			$html[] = '<p><a href="' . $url . '" target="_blank" title="' . JText::_('NN_MORE_INFO') . '">' . JText::_('NN_MORE_INFO') . '...</a></p>';
		}

		return '</div><div>' . implode('', $html);
	}

	private function get($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}
}
