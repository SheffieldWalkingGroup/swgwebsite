<?php
/**
 * Plugin Helper File
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

/**
 * ...
 */
class plgSystemNNFrameworkHelper
{
	function __construct()
	{
		$url = JFactory::getApplication()->input->getString('url', '');
		require_once JPATH_PLUGINS . '/system/nnframework/helpers/functions.php';
		$func = new NNFrameworkFunctions;

		if ($url)
		{
			echo $func->getByUrl($url);
			die;
		}

		$file = JFactory::getApplication()->input->getString('file', '');

		// only allow files that have .inc.php in the file name
		if (!$file || (strpos($file, '.inc.php') === false))
		{
			die;
		}

		$folder = JFactory::getApplication()->input->getString('folder', '');
		if ($folder)
		{
			$file = implode('/', explode('.', $folder)) . '/' . $file;
		}

		$allowed = array(
			'administrator/components/com_dbreplacer/dbreplacer.inc.php',
			'administrator/components/com_nonumbermanager/details.inc.php',
			'administrator/modules/mod_addtomenu/addtomenu.inc.php',
			'media/rereplacer/images/image.inc.php',
			'plugins/editors-xtd/articlesanywhere/articlesanywhere.inc.php',
			'plugins/editors-xtd/contenttemplater/contenttemplater.inc.php',
			'plugins/editors-xtd/modulesanywhere/modulesanywhere.inc.php',
			'plugins/editors-xtd/snippets/snippets.inc.php',
			'plugins/editors-xtd/sourcerer/sourcerer.inc.php'
		);

		if (!$file || (in_array($file, $allowed) === false))
		{
			die;
		}

		jimport('joomla.filesystem.file');

		if (JFactory::getApplication()->isSite())
		{
			JFactory::getApplication()->setTemplate('../administrator/templates/isis');
		}

		$_REQUEST['tmpl'] = 'component';
		JFactory::getApplication()->input->set('option', '1');

		JHtml::_('bootstrap.framework');
		JFactory::getDocument()->addScript(JURI::root(true) . '/administrator/templates/isis/js/template.js');
		JFactory::getDocument()->addStyleSheet(JURI::root(true) . '/administrator/templates/isis/css/template.css');

		JHtml::stylesheet('nnframework/popup.min.css', false, true);

		$file = JPATH_SITE . '/' . $file;

		$html = '';
		if (JFile::exists($file))
		{
			ob_start();
			include $file;
			$html = ob_get_contents();
			ob_end_clean();
		}

		JFactory::getDocument()->setBuffer($html, 'component');

		if (version_compare(JVERSION, '3.2', 'l'))
		{
			JFactory::getApplication()->render();
		}
		else
		{
			nnApplication::render();
		}

		$html = JResponse::toString(JFactory::getApplication()->getCfg('gzip'));
		$html = preg_replace('#\s*<' . 'link [^>]*href="[^"]*templates/system/[^"]*\.css[^"]*"[^>]* />#s', '', $html);
		$html = preg_replace('#(<' . 'body [^>]*class=")#s', '\1nnpopup ', $html);
		$html = str_replace('<' . 'body>', '<' . 'body class="nnpopup"', $html);

		echo $html;

		die;
	}
}

class nnApplication
{
	static function render()
	{
		$app = JFactory::getApplication();

		$options = array();
		// Setup the document options.
		$options['template'] = $app->get('theme');
		$options['file'] = $app->get('themeFile', 'index.php');
		$options['params'] = $app->get('themeParams');

		if ($app->get('themes.base'))
		{
			$options['directory'] = $app->get('themes.base');
		}
		// Fall back to constants.
		else
		{
			$options['directory'] = defined('JPATH_THEMES') ? JPATH_THEMES : (defined('JPATH_BASE') ? JPATH_BASE : __DIR__) . '/themes';
		}

		// Parse the document.
		JFactory::getDocument()->parse($options);

		// Trigger the onBeforeRender event.
		JPluginHelper::importPlugin('system');
		$app->triggerEvent('onBeforeRender');

		$caching = false;

		if ($app->isSite() && $app->get('caching') && $app->get('caching', 2) == 2 && !JFactory::getUser()->get('id'))
		{
			$caching = true;
		}

		// Render the document.
		$data = JFactory::getDocument()->render($caching, $options);

		// Set the application output data.
		$app->setBody($data);

		// Trigger the onAfterRender event.
		$app->triggerEvent('onAfterRender');

		// Mark afterRender in the profiler.
		// Causes issues, so commented out.
		// JDEBUG ? $app->profiler->mark('afterRender') : null;
	}
}
