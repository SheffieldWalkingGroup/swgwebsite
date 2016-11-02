<?php
/**
 * NoNumber Framework Helper File: Assignments: Content
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
 * Assignments: Content
 */
class NNFrameworkAssignmentsContent
{
	function passPageTypes(&$parent, &$params, $selection = array(), $assignment = 'all')
	{
		$components = array('com_content', 'com_contentsubmit');
		if (!in_array($parent->params->option, $components))
		{
			return $parent->pass(0, $assignment);
		}
		if ($parent->params->view == 'category' && $parent->params->layout == 'blog')
		{
			$view = 'categoryblog';
		}
		else
		{
			$view = $parent->params->view;
		}

		return $parent->passSimple($view, $selection, $assignment);
	}

	function passCategories(&$parent, &$params, $selection = array(), $assignment = 'all', $article = 0)
	{
		// components that use the com_content secs/cats
		$components = array('com_content', 'com_flexicontent', 'com_contentsubmit');
		if (!in_array($parent->params->option, $components))
		{
			return $parent->pass(0, $assignment);
		}

		$selection = $parent->makeArray($selection);

		if (empty($selection))
		{
			return $parent->pass(0, $assignment);
		}

		$pass = 0;

		$is_content = in_array($parent->params->option, array('com_content', 'com_flexicontent'));
		$is_category = in_array($parent->params->view, array('category'));
		$is_item = in_array($parent->params->view, array('', 'article', 'item'));

		$inc = (
			$parent->params->option == 'com_contentsubmit'
			|| ($params->inc_categories && $is_content && $is_category)
			|| ($params->inc_articles && $is_content && $is_item)
			|| ($params->inc_others && !($is_content && ($is_category || $is_item)))
		);

		if ($inc)
		{
			if ($parent->params->option == 'com_contentsubmit')
			{
				// Content Submit
				$contentsubmit_params = new ContentsubmitModelArticle;
				if (in_array($contentsubmit_params->_id, $selection))
				{
					$pass = 1;
				}
			}
			else
			{
				if ($params->inc_others && !($is_content && ($is_category || $is_item)))
				{
					if ($article)
					{
						if (!isset($article->id))
						{
							if (isset($article->slug))
							{
								$article->id = (int) $article->slug;
							}
						}
						if (!isset($article->catid))
						{
							if (isset($article->catslug))
							{
								$article->catid = (int) $article->catslug;
							}
						}
						$parent->params->id = $article->id;
						$parent->params->view = 'article';
					}
				}

				if ($is_category)
				{
					$catid = $parent->params->id;
				}
				else
				{
					if (!$article && $parent->params->id)
					{
						$article = JTable::getInstance('content');
						$article->load($parent->params->id);
					}
					$catid = JFactory::getApplication()->input->getInt('catid', JFactory::getApplication()->getUserState('com_content.articles.filter.category_id'));
					if ($article && $article->catid)
					{
						$catid = $article->catid;
					}
					else if ($parent->params->view == 'featured')
					{
						$menuparams = $parent->getMenuItemParams($parent->params->Itemid);
						if (isset($menuparams->featured_categories))
						{
							$catid = $menuparams->featured_categories;
						}
					}
				}
				$catids = is_array($catid) ? $catid : array($catid);
				foreach ($catids as $catid)
				{
					if ($catid)
					{
						$pass = in_array($catid, $selection);
						if ($pass && $params->inc_children == 2)
						{
							$pass = 0;
						}
						else if (!$pass && $params->inc_children)
						{
							$parentids = self::getParentIds($parent, $catid);
							$parentids = array_diff($parentids, array('1'));
							foreach ($parentids as $id)
							{
								if (in_array($id, $selection))
								{
									$pass = 1;
									break;
								}
							}
							unset($parentids);
						}
					}
				}
			}
		}

		return $parent->pass($pass, $assignment);
	}

	function passArticles($parent, &$params, $selection = array(), $assignment = 'all', $article = 0)
	{
		if (!$parent->params->id
			|| !(($parent->params->option == 'com_content' && $parent->params->view == 'article')
				|| ($parent->params->option == 'com_flexicontent' && $parent->params->view == 'item')
			)
		)
		{
			return $parent->pass(0, $assignment);
		}

		$pass = 0;

		if ($selection && !is_array($selection))
		{
			if (!(strpos($selection, '|') === false))
			{
				$selection = explode('|', $selection);
			}
			else
			{
				$selection = explode(',', $selection);
			}
		}
		if (!empty($selection))
		{
			$pass = in_array($parent->params->id, $selection);
		}

		if ($params->keywords && !is_array($params->keywords))
		{
			$params->keywords = explode(',', $params->keywords);
		}
		if (!empty($params->keywords))
		{
			$pass = 0;
			if (!$article)
			{
				require_once JPATH_SITE . '/components/com_content/models/article.php';
				$model = JModelLegacy::getInstance('article', 'contentModel');
				$article = $model->getItem($parent->params->id);
			}
			if (isset($article->metakey) && $article->metakey)
			{
				$keywords = explode(',', $article->metakey);
				foreach ($keywords as $keyword)
				{
					if ($keyword && in_array(trim($keyword), $params->keywords))
					{
						$pass = 1;
						break;
					}
				}
				if (!$pass)
				{
					$keywords = explode(',', str_replace(' ', ',', $article->metakey));
					foreach ($keywords as $keyword)
					{
						if ($keyword && in_array(trim($keyword), $params->keywords))
						{
							$pass = 1;
							break;
						}
					}
				}
			}
		}

		return $parent->pass($pass, $assignment);
	}

	function getParentIds(&$parent, $id = 0)
	{
		return $parent->getParentIds($id, 'categories');
	}
}
