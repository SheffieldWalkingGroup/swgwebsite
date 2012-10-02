<?php
/**
 * NoNumber Framework Helper File: Assignments: PHP
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
 * Assignments: PHP
 */
class NNFrameworkAssignmentsPHP
{
	function passPHP(&$parent, &$params, $selection = array(), $assignment = 'all', $article = 0)
	{
		if (!is_array($selection)) {
			$selection = array($selection);
		}

		$pass = 0;
		foreach ($selection as $php) {
			// replace \n with newline and other fix stuff
			$php = str_replace('\|', '|', $php);
			$php = preg_replace('#(?<!\\\)\\\n#', "\n", $php);
			$php = str_replace('[:REGEX_ENTER:]', '\n', $php);

			if (trim($php) == '') {
				$pass = 1;
				break;
			}

			if (!$article && !(strpos($php, '$article') === false) && $parent->params->option == 'com_content' && $parent->params->view == 'article') {
				require_once JPATH_SITE . '/components/com_content/models/article.php';
				$model = JModel::getInstance('article', 'contentModel');
				$article = $model->getItem($parent->params->id);
			}
			if (!isset($Itemid)) {
				$Itemid = JRequest::getInt('Itemid');
			}
			if (!isset($mainframe)) {
				$mainframe = (strpos($php, '$mainframe') === false) ? '' : JFactory::getApplication();
			}
			if (!isset($app)) {
				$app = (strpos($php, '$app') === false) ? '' : JFactory::getApplication();
			}
			if (!isset($document)) {
				$document = (strpos($php, '$document') === false) ? '' : JFactory::getDocument();
			}
			if (!isset($doc)) {
				$doc = (strpos($php, '$doc') === false) ? '' : JFactory::getDocument();
			}
			if (!isset($database)) {
				$database = (strpos($php, '$database') === false) ? '' : JFactory::getDBO();
			}
			if (!isset($db)) {
				$db = (strpos($php, '$db') === false) ? '' : JFactory::getDBO();
			}
			if (!isset($user)) {
				$user = (strpos($php, '$user') === false) ? '' : JFactory::getUser();
			}

			$vars = '$article,$Itemid,$mainframe,$app,$document,$doc,$database,$db,$user';

			$val = '$temp_PHP_Val = create_function( \'' . $vars . '\', $php.\';\' );';
			$val .= ' $pass = ( $temp_PHP_Val(' . $vars . ') ) ? 1 : 0; unset( $temp_PHP_Val );';
			@eval($val);

			if ($pass) {
				break;
			}
		}

		return $parent->pass($pass, $assignment);
	}
}