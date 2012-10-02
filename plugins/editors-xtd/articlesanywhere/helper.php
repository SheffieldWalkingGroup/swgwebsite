<?php
/**
 * Plugin Helper File
 *
 * @package         Articles Anywhere
 * @version         2.4.2
 *
 * @author          Peter van Westen <peter@nonumber.nl>
 * @link            http://www.nonumber.nl
 * @copyright       Copyright © 2012 NoNumber All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die;

/**
 * Plugin that places the button
 */
class plgButtonArticlesAnywhereHelper
{
	function __construct(&$params)
	{
		$this->params = $params;
	}

	/**
	 * Display the button
	 *
	 * @return array A two element array of ( imageName, textToInsert )
	 */
	function render($name)
	{
		$app = JFactory::getApplication();

		$button = new JObject();

		if ($app->isSite()) {
			$enable_frontend = $this->params->enable_frontend;
			if (!$enable_frontend) {
				return $button;
			}
		}

		JHtml::_('behavior.modal');

		$document = JFactory::getDocument();

		$button_style = 'articlesanywhere';

		if (!$this->params->button_icon) {
			$button_style = 'blank blank_articlesanywhere';
		}
		$document->addStyleSheet(JURI::root(true) . '/plugins/editors-xtd/articlesanywhere/css/style.css');

		$link = 'index.php?nn_qp=1'
			. '&folder=plugins.editors-xtd.articlesanywhere'
			. '&file=articlesanywhere.inc.php'
			. '&name=' . $name;

		$text_ini = strtoupper(str_replace(' ', '_', $this->params->button_text));
		$text = JText::_($text_ini);
		if ($text == $text_ini) {
			$text = JText::_($this->params->button_text);
		}

		$button->set('modal', true);
		$button->set('link', $link);
		$button->set('text', $text);
		$button->set('name', $button_style);
		$button->set('options', "{handler: 'iframe', size: {x:window.getSize().x-100, y: window.getSize().y-100}}");

		return $button;
	}
}