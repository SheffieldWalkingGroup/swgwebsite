<?php
/**
 * NoNumber Framework Helper File: VersionCheck
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

class NNVersions
{
	public static $instance = null;

	public static function getInstance()
	{
		if (!self::$instance)
		{
			self::$instance = new NoNumberVersions;
		}

		return self::$instance;
	}
}

class NoNumberVersions
{
	function getMessage($element = '', $xml = '', $version = '', $type = 'system', $admin = 1)
	{
		if (!$element)
		{
			return '';
		}

		$alias = preg_replace('#[^a-z]#', '', strtolower($element));

		if ($xml)
		{
			$xml = JApplicationHelper::parseXMLInstallFile(JPATH_SITE . '/' . $xml);
			if ($xml && isset($xml['version']))
			{
				$version = $xml['version'];
			}
		}

		if (!$version)
		{
			$version = self::getXMLVersion($element, $type, $admin);
		}

		if (!$version)
		{
			return '';
		}

		JHtml::_('jquery.framework');
		JHtml::script('nnframework/script.min.js', false, true);
		$url = 'download.nonumber.nl/extensions.php?j=3&e=' . $alias;
		$script = "
			jQuery(document).ready(function() {
				nnScripts.loadajax(
					'" . $url . "',
					'nnScripts.displayVersion( data, \"" . $alias . "\", \"" . str_replace(array('FREE', 'PRO'), '', $version) . "\" )',
					'nnScripts.displayVersion( \"\" )'
				);
			});
		";
		JFactory::getDocument()->addScriptDeclaration($script);

		return '<div class="alert alert-error" style="display:none;" id="nonumber_version_' . $alias . '">' . $this->getMessageText($alias, $version) . '</div>';
	}

	function getMessageText($alias, $version)
	{
		jimport('joomla.filesystem.file');

		$version = str_replace(array('FREE', 'PRO'), '', $version);

		if (JFile::exists(JPATH_ADMINISTRATOR . '/components/com_nonumbermanager/nonumbermanager.xml')
			|| JFile::exists(JPATH_ADMINISTRATOR . '/components/com_nonumbermanager/com_nonumbermanager.xml')
		)
		{
			$url = 'index.php?option=com_nonumbermanager';
		}
		else
		{
			$url = 'http://www.nonumber.nl/' . $alias . '#download';
		}

		$msg = '<strong>'
			. JText::_('NN_NEW_VERSION_AVAILABLE')
			. ': <a href="' . $url . '" target="_blank">'
			. JText::sprintf('NN_UPDATE_TO', '<span id="nonumber_newversionnumber_' . $alias . '"></span>')
			. '</a></strong><br /><em>'
			. JText::sprintf('NN_CURRENT_VERSION', $version)
			. ' ('
			. JText::_('NN_ONLY_VISIBLE_TO_ADMIN')
			. ')</em>';

		return html_entity_decode($msg, ENT_COMPAT, 'UTF-8');
	}

	function getCopyright($name, $version, $jedid = 0, $element = 'nnframework', $type = 'system', $copyright = 1, $admin = 1)
	{
		$html = array();
		$html[] = '<p style="text-align:center;">';
		$html[] = JText::_($name);

		if (!$version)
		{
			$version = self::getXMLVersion($element, $type, $admin);
		}

		if ($version)
		{
			if (!(strpos($version, 'PRO') === false))
			{
				$version = str_replace('PRO', '', $version);
				$version .= ' <small>[PRO]</small>';
			}
			else if (!(strpos($version, 'FREE') === false))
			{
				$version = str_replace('FREE', '', $version);
				$version .= ' <small>[FREE]</small>';
			}
			$html[] = ' v' . $version;
		}
		if ($copyright)
		{
			$html[] = '<br />' . JText::_('COPYRIGHT') . ' &copy; ' . date('Y') . ' NoNumber ' . JText::_('ALL_RIGHTS_RESERVED');
			if ($jedid)
			{
				$html[] = '<br />' . html_entity_decode(JText::sprintf('NN_RATE', $jedid));
			}
		}
		$html[] = '</p>';

		return implode('', $html);
	}

	static function getXMLVersion($element = 'nnframework', $type = 'system', $admin = 1, $urlformat = 0)
	{
		if (!$element)
		{
			$element = 'nnframework';
		}
		if (!$type)
		{
			$type = 'system';
		}
		if (!strlen($admin))
		{
			$admin = 1;
		}

		switch ($type)
		{
			case 'component':
			case 'components':
			case 'module':
			case 'modules':
				$type .= in_array($type, array('component', 'module')) ? 's' : '';
				if ($admin)
				{
					$path = JPATH_ADMINISTRATOR;
				}
				else
				{
					$path = JPATH_SITE;
				}
				$path .= '/' . $type . '/' . ($type == 'modules' ? 'mod_' : 'com_') . $element . '/' . ($type == 'modules' ? 'mod_' : '') . $element . '.xml';
				break;
			default:
				$path = JPATH_PLUGINS . '/' . $type . '/' . $element . '/' . $element . '.xml';
				break;
		}

		$version = '';
		$xml = JApplicationHelper::parseXMLInstallFile($path);
		if ($xml && isset($xml['version']))
		{
			$version = trim($xml['version']);
			if ($urlformat)
			{
				$version = '?v=' . strtolower(str_replace(array('FREE', 'PRO'), array('f', 'p'), $version));
			}
		}

		return $version;
	}
}
