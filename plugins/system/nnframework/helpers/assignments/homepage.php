<?php
/**
 * NoNumber Framework Helper File: Assignments: HomePage
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

/**
 * Assignments: URL
 */
class NNFrameworkAssignmentsHomePage
{
	function passHomePage(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		$app = JFactory::getApplication();
		$menu = $app->getMenu('site');
		$home = $menu->getDefault(JFactory::getLanguage()->getTag());

		// return if option or other set values do not match the homepage menu item values
		if ($parent->params->option) {
			// check if option is different to home menu
			if (!$home || !isset($home->query['option']) || $home->query['option'] != $parent->params->option) {
				return $parent->pass(0, $assignment);
			}

			// check set values against home menu query items
			foreach ($home->query as $k => $v) {
				if ((isset($parent->params->{$k}) && $parent->params->{$k} != $v)
					|| (!isset($parent->params->{$k}) && JRequest::getCmd($k) != $v)
				) {
					return $parent->pass(0, $assignment);
				}
			}

			// check post values against home menu params
			foreach ($home->params->toObject() as $k => $v) {
				if (($v && isset($_POST[$k]) && $_POST[$k] != $v)
					|| (!$v && isset($_POST[$k]) && $_POST[$k])
				) {
					return $parent->pass(0, $assignment);
				}
			}
		}

		$pass = $this->checkPass($home);

		if (!$pass) {
			$pass = $this->checkPass($home, 1);
		}

		return $parent->pass($pass, $assignment);
	}

	function checkPass(&$home, $addlang = 0)
	{
		$pass = 0;

		$uri = JFactory::getURI();

		if ($addlang) {
			$sef = $uri->getVar('lang');
			if (empty($sef)) {
				$langs = array_keys(JLanguageHelper::getLanguages('sef'));
				$path = JString::substr($uri->toString(array('scheme', 'user', 'pass', 'host', 'port', 'path')), JString::strlen($uri->base()));
				$path = preg_replace('#^index\.php/?#', '', $path);
				$parts = explode('/', $path);
				$part = reset($parts);
				if (in_array($part, $langs)) {
					$sef = $part;
				}
			}
			if (empty($sef)) {
				return 0;
			}
		}

		$query = $uri->toString(array('query'));
		if (strpos($query, 'option=') === false && strpos($query, 'Itemid=') === false) {
			$url = $uri->toString(array('host', 'path'));
		} else {
			$url = $uri->toString(array('host', 'path', 'query'));
		}

		// remove the www.
		$url = preg_replace('#^www\.#', '', $url);
		// replace ampersand chars
		$url = str_replace('&amp;', '&', $url);
		// remove any language vars
		$url = preg_replace('#((\?)lang=[a-z-_]*(&|$)|&lang=[a-z-_]*)#', '\2', $url);
		// remove trailing nonsense
		$url = trim(preg_replace('#/?\??&?$#', '', $url));
		// remove the index.php/
		$url = preg_replace('#/index\.php(/|$)#', '/', $url);
		// remove trailing /
		$url = trim(preg_replace('#/$#', '', $url));

		$root = JURI::root();

		// remove the http(s)
		$root = preg_replace('#^.*?://#', '', $root);
		// remove the www.
		$root = preg_replace('#^www\.#', '', $root);
		// so also passes on urls with trailing /, ?, &, /?, etc...
		$root = preg_replace('#(Itemid=[0-9]*).*^#', '\1', $root);
		// remove trailing /
		$root = trim(preg_replace('#/$#', '', $root));

		if ($addlang) {
			$root .= '/' . $sef;
		}

		if (!$pass) {
			/* Pass urls:
			 * [root]
			 */
			$regex = '#^' . $root . '$#i';
			$pass = preg_match($regex, $url);
		}

		if (!$pass) {
			/* Pass urls:
			 * [root]?Itemid=[menu-id]
			 * [root]/?Itemid=[menu-id]
			 * [root]/index.php?Itemid=[menu-id]
			 * [root]/[menu-alias]
			 * [root]/[menu-alias]?Itemid=[menu-id]
			 * [root]/index.php?[menu-alias]
			 * [root]/index.php?[menu-alias]?Itemid=[menu-id]
			 * [root]/[menu-link]
			 * [root]/[menu-link]&Itemid=[menu-id]
			 */
			$regex = '#^' . $root
				. '(/('
				. 'index\.php'
				. '|'
				. '(index\.php\?)?' . preg_quote($home->alias, '#')
				. '|'
				. preg_quote($home->link, '#')
				. ')?)?'
				. '(/?[\?&]Itemid=' . (int) $home->id . ')?'
				. '$#i';
			$pass = preg_match($regex, $url);
		}

		return $pass;
	}
}