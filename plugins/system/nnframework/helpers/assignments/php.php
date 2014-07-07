<?php
/**
 * NoNumber Framework Helper File: Assignments: PHP
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
 * Assignments: PHP
 */
class NNFrameworkAssignmentsPHP
{
	function passPHP(&$parent, &$params, $selection = array(), $assignment = 'all', $article = 0)
	{
		if (!is_array($selection))
		{
			$selection = array($selection);
		}

		$pass = 0;
		foreach ($selection as $php)
		{
			// replace \n with newline and other fix stuff
			$php = str_replace('\|', '|', $php);
			$php = preg_replace('#(?<!\\\)\\\n#', "\n", $php);
			$php = trim(str_replace('[:REGEX_ENTER:]', '\n', $php));

			if ($php == '')
			{
				$pass = 1;
				break;
			}

			if (!$article && !(strpos($php, '$article') === false) && $parent->params->option == 'com_content' && $parent->params->view == 'article')
			{
				require_once JPATH_SITE . '/components/com_content/models/article.php';
				$model = JModelLegacy::getInstance('article', 'contentModel');
				$article = $model->getItem($parent->params->id);
			}
			else
			{
				$article = '';
			}
			if (!isset($Itemid))
			{
				$Itemid = JFactory::getApplication()->input->getInt('Itemid', 0);
			}
			if (!isset($mainframe))
			{
				$mainframe = JFactory::getApplication();
			}
			if (!isset($app))
			{
				$app = JFactory::getApplication();
			}
			if (!isset($document))
			{
				$document = JFactory::getDocument();
			}
			if (!isset($doc))
			{
				$doc = JFactory::getDocument();
			}
			if (!isset($database))
			{
				$database = JFactory::getDBO();
			}
			if (!isset($db))
			{
				$db = JFactory::getDBO();
			}
			if (!isset($user))
			{
				$user = JFactory::getUser();
			}
			$php .= ';return 1;';
			$temp_PHP_func = create_function('&$article, &$Itemid, &$mainframe, &$app, &$document, &$doc, &$database, &$db, &$user', $php);

			// evaluate the script
			ob_start();
			$pass = $temp_PHP_func($article, $Itemid, $mainframe, $app, $document, $doc, $database, $db, $user) ? 1 : 0;
			unset($temp_PHP_func);
			ob_end_clean();

			if ($pass)
			{
				break;
			}
		}

		return $parent->pass($pass, $assignment);
	}
}
