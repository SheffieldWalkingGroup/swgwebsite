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

// Import library dependencies
jimport('joomla.plugin.plugin');

// Load common functions
require_once JPATH_PLUGINS . '/system/nnframework/helpers/text.php';
require_once JPATH_PLUGINS . '/system/nnframework/helpers/protect.php';

/**
 * Plugin that places articles
 */
class plgSystemArticlesAnywhereHelper
{
	function __construct(&$params)
	{
		$this->params = $params;

		$this->params->comment_start = '<!-- START: Articles Anywhere -->';
		$this->params->comment_end = '<!-- END: Articles Anywhere -->';
		$this->params->message_start = '<!--  Articles Anywhere Message: ';
		$this->params->message_end = ' -->';


		$bts = '((?:<p(?: [^>]*)?>)?)((?:\s*<br ?/?>)?\s*)';
		$bte = '(\s*(?:<br ?/?>\s*)?)((?:</p>)?)';
		$this->params->tags = '(' . preg_quote($this->params->article_tag, '#')
			. ')';
		$this->params->regex = '#'
			. $bts . '\{' . $this->params->tags . '(?: ([^\}]*))?\}' . $bte
			. '(.*?)'
			. $bts . '\{/\3\}' . $bte
			. '#s';
		$this->params->breaks_start = $bts;
		$this->params->breaks_end = $bte;

		$user = JFactory::getUser();
		$this->params->aid = $user->getAuthorisedViewLevels();

		$db = JFactory::getDBO();
		$selects = $db->getTableColumns('#__content');
		if (is_array($selects)) {
			unset($selects['introtext']);
			unset($selects['fulltext']);
			$selects = array_keys($selects);
			$this->params->dbselects_content = $selects;
		}


		$this->params->dispatcher = 0;
	}

	////////////////////////////////////////////////////////////////////
	// onContentPrepare
	////////////////////////////////////////////////////////////////////

	function onContentPrepare(&$article)
	{
		$message = '';


		if (isset($article->text)) {
			$this->processArticles($article->text, $article, 'articles', $message);
		}
		if (isset($article->description)) {
			$this->processArticles($article->description, $article, 'articles', $message);
		}
		if (isset($article->title)) {
			$this->processArticles($article->title, $article, 'articles', $message);
		}
		if (isset($article->created_by_alias)) {
			$this->processArticles($article->created_by_alias, $article, 'articles', $message);
		}
	}

	////////////////////////////////////////////////////////////////////
	// onAfterDispatch
	////////////////////////////////////////////////////////////////////

	function onAfterDispatch()
	{
		$document = JFactory::getDocument();
		$docType = $document->getType();

		// PDF
		if ($docType == 'pdf') {
			$buffer = $document->getBuffer('component');
			if (is_array($buffer)) {
				if (isset($buffer['component']) && isset($buffer['component'][''])) {
					$this->replaceTags($buffer['component']['']);
				}
			} else {
				$this->replaceTags($buffer);
			}
			$document->setBuffer($buffer, 'component');
			return;
		}

		if (($docType == 'feed' || JRequest::getCmd('option') == 'com_acymailing') && isset($document->items)) {
			$itemids = array_keys($document->items);
			foreach ($itemids as $i) {
				$this->onContentPrepare($document->items[$i]);
			}
		}

		$buffer = $document->getBuffer('component');
		if (!empty($buffer)) {
			if (is_array($buffer)) {
				if (isset($buffer['component']) && isset($buffer['component'][''])) {
					$this->tagArea($buffer['component'][''], 'ARTA', 'component');
				}
			} else {
				$this->tagArea($buffer, 'ARTA', 'component');
			}
			$document->setBuffer($buffer, 'component');
		}
	}

	////////////////////////////////////////////////////////////////////
	// onAfterRender
	////////////////////////////////////////////////////////////////////
	function onAfterRender()
	{
		$document = JFactory::getDocument();
		$docType = $document->getType();

		// not in pdf's
		if ($docType == 'pdf') {
			return;
		}

		$html = JResponse::getBody();
		if ($html == '') {
			return;
		}

		if ($docType != 'html') {
			$this->replaceTags($html);
			$this->cleanLeftoverJunk($html);
		} else {
			if (!(strpos($html, '<body') === false) && !(strpos($html, '</body>') === false)) {
				$html_split = explode('<body', $html, 2);
				$body_split = explode('</body>', $html_split['1'], 2);

				// only do stuff in body
				$this->protect($body_split['0']);
				$this->replaceTags($body_split['0']);

				$html_split['1'] = implode('</body>', $body_split);
				$html = implode('<body', $html_split);
			} else {
				$this->protect($html);
				$this->replaceTags($html);
			}

			$this->cleanLeftoverJunk($html);
			$this->unprotect($html);

			// replace head with newly generated head
			// this is necessary because the plugins might have added scripts/styles to the head
			$orig_document = clone($document);
			$this->updateHead($html, $orig_document);
			unset($orig_document);
		}

		JResponse::setBody($html);
	}

	function replaceTags(&$str)
	{
		if ($str == '') {
			return;
		}

		$document = JFactory::getDocument();
		$docType = $document->getType();

		$message = '';

		// COMPONENT
		if ($docType == 'feed' || JRequest::getCmd('option') == 'com_acymailing') {
			$s = '#(<item[^>]*>)#s';
			$str = preg_replace($s, '\1<!-- START: ARTA_COMPONENT -->', $str);
			$str = str_replace('</item>', '<!-- END: ARTA_COMPONENT --></item>', $str);
		}
		if (strpos($str, '<!-- START: ARTA_COMPONENT -->') === false) {
			$this->tagArea($str, 'ARTA', 'component');
		}

		$components = $this->getTagArea($str, 'ARTA', 'component');

		$article = null;
		foreach ($components as $component) {
			$this->processArticles($component['1'], $article, 'components', $message);
			$str = str_replace($component['0'], $component['1'], $str);
		}

		// EVERYWHERE
		$this->processArticles($str, $article, 'other');
	}

	function tagArea(&$str, $ext = 'EXT', $area = '')
	{
		if ($str && $area) {
			$str = '<!-- START: ' . strtoupper($ext) . '_' . strtoupper($area) . ' -->' . $str . '<!-- END: ' . strtoupper($ext) . '_' . strtoupper($area) . ' -->';
			if ($area == 'article_text') {
				$str = preg_replace('#(<hr class="system-pagebreak".*?/>)#si', '<!-- END: ' . strtoupper($ext) . '_' . strtoupper($area) . ' -->\1<!-- START: ' . strtoupper($ext) . '_' . strtoupper($area) . ' -->', $str);
			}
		}
	}

	function getTagArea(&$str, $ext = 'EXT', $area = '')
	{
		$matches = array();
		if ($str && $area) {
			$start = '<!-- START: ' . strtoupper($ext) . '_' . strtoupper($area) . ' -->';
			$end = '<!-- END: ' . strtoupper($ext) . '_' . strtoupper($area) . ' -->';
			$matches = explode($start, $str);
			array_shift($matches);
			foreach ($matches as $i => $match) {
				list($text) = explode($end, $match, 2);
				$matches[$i] = array(
					$start . $text . $end,
					$text
				);
			}
		}
		return $matches;
	}

	function processArticles(&$string, &$art, $area = 'articles', $message = '')
	{

		$regex_close = '#\{/' . $this->params->tags . '\}#si';

		if (preg_match($regex_close, $string)) {
			if (@preg_match($regex_close . 'u', $string)) {
				$regex_close .= 'u';
			}
			$regex = $this->params->regex;
			if (@preg_match($regex . 'u', $string)) {
				$regex .= 'u';
			}

			$matches = array();
			$count = 0;
			while ($count++ < 10 && preg_match($regex_close, $string) && preg_match_all($regex, $string, $matches, PREG_SET_ORDER) > 0) {
				foreach ($matches as $match) {
						$parts = explode('|', $match['4']);
						$match['4'] = array_shift($parts);
						$ignores = array();
						foreach ($parts as $p) {
							if (!(strpos($p, '=') === false)) {
								list($key, $val) = explode('=', $p, 2);
								if (in_array($key, array('ignore_access', 'ignore_state'))) {
									$val = str_replace(array('\{', '\}'), array('{', '}'), $val);
									$ignores[$key] = $val;
								}
							}
						}

						$html = $this->processMatch($string, $art, $match, $ignores, $message);
						$string = str_replace($match['0'], $html, $string);
				}
				$matches = array();
			}
		}
	}

	function processMatch(&$string, &$art, &$match, &$ignores, &$message)
	{
		$html = '';
		if ($message != '') {
			if ($this->params->place_comments) {
				$html = $this->params->comment_start . $this->params->message_start . $message . $this->params->message_end . $this->params->comment_end;
			}
		} else {
			$p1_start = $match['1'];
			$br1a = $match['2'];
			//$type		= $match['3'];
			$id = $match['4'];
			$br1b = $match['5'];
			$p1_end = $match['6'];
			$html = $match['7'];
			$p2_start = $match['8'];
			$br2a = $match['9'];
			// end tag
			$br2b = $match['10'];
			$p2_end = $match['11'];

			$html = trim($html);
			preg_match('#^' . $this->params->breaks_start . '(.*?)' . $this->params->breaks_end . '$#s', trim($html), $text_match);

			if ($match['1'] && ($p1_end || $text_match['1'])) {
				$match['1'] = '';
			}
			if ($p2_end && ($p2_start || $text_match['5'])) {
				$p2_end = '';
			}

			if (!(strpos($string, '{/div}') === false) && preg_match('#^' . $this->params->breaks_start . '(\{div[^\}]*\})' . $this->params->breaks_end . '(.*?)' . $this->params->breaks_start . '(\{/div\})' . $this->params->breaks_end . '#s', $html, $div_match)) {
				if ($div_match['1'] && $div_match['5']) {
					$div_match['1'] = '';
				}
				if ($div_match['7'] && $div_match['11']) {
					$div_match['11'] = '';
				}
				$html = $div_match['2'] . $div_match['3'] . $div_match['4'] . $div_match['1'] . $div_match['6'] . $div_match['11'] . $div_match['8'] . $div_match['9'] . $div_match['10'];
			}

			$type = 'article';
			if (!(strpos($id, ':') === false)) {
				$type = explode(':', $id, 2);
				if ($type['0'] == 'k2') {
					$id = trim($type['1']);
				}
			}
			$html = $this->processArticle($id, $art, $html, $type, $ignores);

			if ($this->params->place_comments) {
				$html = $this->params->comment_start . $html . $this->params->comment_end;
			}

			$html = $p1_start . $br1a . $br1b . $html . $br2a . $br2b . $p2_end;

			$html = preg_replace('#((?:<p(?: [^>]*)?>\s*)?)((?:<br ?/?>)?\s*<div(?: [^>]*)?>.*?</div>\s*(?:<br ?/?>)?)((?:\s*</p>)?)#', '\3\2\1', $html);
			$html = preg_replace('#(<p(?: [^>]*)?>\s*)<p(?: [^>]*)?>#', '\1', $html);
			$html = preg_replace('#(</p>\s*)</p>#', '\1', $html);
		}

		return $html;
	}

	function processArticle($id, $art, $text = '', $type = 'article', $ignores = array(), $first = 0)
	{
		if (!$first) {
			// do second pass
			$text = $this->processArticle($id, $art, $text, $type, $ignores, 1);
		}

		if ($first) {
			// first pass: search for normal tags and tags around tags
			$regex = '#\{(/?(?:[^\}]*\{[^\}]*\})*[^\}]*)\}#si';
		} else {
			$regex_close = '#\{/' . $this->params->tags . '\}#si';
			if (preg_match($regex_close, $text)) {
				return $text;
			}
			// second pass: only search for normal tags
			$regex = '#\{(/?[^\{\}]+)\}#si';
		}

		if (!preg_match_all($regex, $text, $matches, PREG_SET_ORDER)) {
			return $text;
		}

		$app = JFactory::getApplication();
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		if ($type == 'k2' && !$this->params->dbselects_k2) {
			$type = '';
		}

		if ($type == 'k2') {
			$selects = $this->params->dbselects_k2;
		} else {
			$selects = $this->params->dbselects_content;
		}
		if ($art && in_array($id, array('current', 'self', '{id}', '{title}', '{alias}'))) {
			if (isset($art->id)) {
				$id = $art->id;
			} else if (isset($art->link) && preg_match('#&amp;id=([0-9]*)#', $art->link, $match)) {
				$id = $match['1'];
			}
		}

		foreach ($matches as $match) {
			$data = trim($match['1']);
			if (!(strpos($data, 'intro') === false)) {
				$selects[] = 'introtext';
			} else if (!(strpos($data, 'full') === false)) {
				$selects[] = 'fulltext';
			} else if (!(strpos($data, 'text') === false)) {
				$selects[] = 'introtext';
				$selects[] = 'fulltext';
			}
		}
		$selects = array_unique($selects);
		$selects = 'a.`' . implode('`, a.`', $selects) . '`';
		$query->select($selects);

		$joins = '';
		if ($type == 'article') {
			$query->select('CASE WHEN CHAR_LENGTH(a.alias) THEN CONCAT_WS(":", a.id, a.alias) ELSE a.id END AS slug');
			$query->select('CASE WHEN CHAR_LENGTH(c.alias) THEN CONCAT_WS(":", c.id, c.alias) ELSE c.id END AS catslug');

			$query->leftJoin('#__categories AS c ON c.id = a.catid');
		}

			$query->from('#__content as a');

		$where = 'a.title = ' . $db->q(NNText::html_entity_decoder($id));
		$where .= ' OR a.alias = ' . $db->q(NNText::html_entity_decoder($id));
		if (is_numeric($id)) {
			$where .= ' OR a.id = ' . $id;
		}
		$query->where('(' . $where . ')');

		$ignore_state = isset($ignores['ignore_state']) ? $ignores['ignore_state'] : $this->params->ignore_state;
		if (!$ignore_state) {
			$jnow = JFactory::getDate();
			$now = $jnow->toMySQL();
			$nullDate = $db->getNullDate();
			if ($type == 'k2') {
				$query->where('a.published = 1 AND trash = 0');
			} else {
				$query->where('a.state = 1');
			}
			$query->where('( a.publish_up = ' . $db->q($nullDate) . ' OR a.publish_up <= ' . $db->q($now) . ' )');
			$query->where('( a.publish_down = ' . $db->q($nullDate) . ' OR a.publish_down >= ' . $db->q($now) . ' )');
		}

		$ignore_access = isset($ignores['ignore_access']) ? $ignores['ignore_access'] : $this->params->ignore_access;
		if (!$ignore_access) {
			$query->where('a.access IN(' . implode(', ', $this->params->aid) . ')');
		}

		$query->order('a.ordering');

		$db->setQuery($query);
		$article = $db->loadObject();

		if (!$article) {
			return '<!-- ' . JText::_('AA_ACCESS_TO_ARTICLE_DENIED') . ' -->';
		}

		$ifregex = '#\{if:([^\}]+)\}(.*?)(?:\{else\}(.*?))?\{/if\}#si';
		if (preg_match_all($ifregex, $text, $ifs, PREG_SET_ORDER) > 0) {
			foreach ($ifs as $if) {
				$pass = 0;
				$eval = '$pass = ( ( $article->' . str_replace('=', '==', trim($if['1'])) . ' ) ? 1 : 0 );';
				$eval = str_replace('$article->!', '!$article->', $eval);
				eval($eval);
				if (!$pass) {
					$text = str_replace($if['0'], (isset($if['3']) ? $if['3'] : ''), $text);
				} else {
					$text = str_replace($if['0'], $if['2'], $text);
				}
			}
		}

		if (!preg_match_all($regex, $text, $matches, PREG_SET_ORDER)) {
			return $text;
		}

		foreach ($matches as $match) {
			$data = trim($match['1']);
			$ok = 0;
			$str = '';
			$data = explode(':', $data, 2);
			$tag = trim($data['0']);
			$extra = isset($data['1']) ? trim($data['1']) : '';
			if ($tag == '/link') {
				$str = '</a>';
				$ok = 1;
			} else if ($tag == '/div') {
				$str = '</div>';
				$ok = 1;
			} else if ($tag == 'div' || strpos($tag, 'div ') === 0) {
				if ($tag != 'div') {
					$extra = str_replace('div ', '', $tag) . ':' . $extra;
				}

				$str = '';
				if ($extra) {
					$extra = explode('|', $extra);
					$extras = new stdClass();
					foreach ($extra as $e) {
						if (!(strpos($e, ':') === false)) {
							list($key, $val) = explode(':', $e, 2);
							$extras->$key = $val;
						}
					}
					if (isset($extras->class)) {
						$str .= 'class="' . $extras->class . '"';
					}

					$style = array();
					if (isset($extras->width)) {
						if (is_numeric($extras->width)) {
							$extras->width .= 'px';
						}
						$style[] = 'width:' . $extras->width;
					}
					if (isset($extras->height)) {
						if (is_numeric($extras->height)) {
							$extras->height .= 'px';
						}
						$style[] = 'height:' . $extras->height;
					}
					if (isset($extras->align)) {
						$style[] = 'float:' . $extras->align;
					} else if (isset($extras->float)) {
						$style[] = 'float:' . $extras->float;
					}

					if (!empty($style)) {
						$str .= ' style="' . implode(';', $style) . ';"';
					}
				}
				$str = trim('<div ' . trim($str)) . '>';
				$ok = 1;
			} else if (
				($tag == 'link' || $tag == 'url')
				|| !(strpos($tag, 'readmore') === false)
			) {
				if (isset($article->id)) {
						$slug = 'id=' . $article->slug;
						if ($article->catid) {
							$slug .= '&catid=' . $article->catslug;
						}
						$link = 'index.php?option=com_content&view=article&' . $slug;
						$component = JComponentHelper::getComponent('com_content');
						$menus = $app->getMenu('site');
						$menuitems = $menus->getItems('component_id', $component->id);
						$id = 0;
						if (is_array($menuitems)) {
							foreach ($menuitems as $item) {
								if (@$item->query['view'] == 'article' && @$item->query['id'] == $article->id) {
									$id = $item->id;
									break;
								}
							}
							if (!$id) {
								foreach ($menuitems as $item) {
									if (@$item->query['view'] == 'category' && @$item->query['id'] == $article->catid) {
										$id = $item->id;
										break;
									}
								}
							}

							if (!$id) {
								foreach ($menuitems as $item) {
									if (@$item->query['view'] == 'section' && @$item->query['id'] == $article->sectionid) {
										$id = $item->id;
										break;
									}
								}
							}
						}
					if ($id) {
						$link .= '&Itemid=' . $id;
					}
					$link = JRoute::_($link);

					if ($tag == 'link') {
						$str = '<a href="' . $link . '">';
					} else if ($tag == 'url') {
						$str = $link;
					} else {
						// load the content language file
						$lang = JFactory::getLanguage();
						$lang->load('com_content');
						$class = 'readmore';

						$readmore = '';
						if ($extra) {
							$extra = explode('|', $extra);
							if (trim($extra['0'])) {
								$readmore = JText::sprintf(trim($extra['0']), $article->title);
								if (!$readmore) {
									$readmore = $extra['0'];
								}
							}
							if (isset($extra['1'])) {
								$class = trim($extra['1']);
							}
						}

						if (!$readmore) {
							$attribs = json_decode($article->attribs);
							if ($attribs->alternative_readmore) {
								$readmore = $attribs->alternative_readmore;
							} else {
								$readmore = JText::_('COM_CONTENT_READ_MORE');
							}
							$readmore .= $article->title;
						}
						if ($class == 'readmore') {
							$str = '<p class="' . $class . '"><a href="' . $link . '">' . $readmore . '</a></p>';
						} else {
							$str = '<a class="' . $class . '" href="' . $link . '">' . $readmore . '</a>';
						}
					}
					$ok = 1;
				}
			} else if (in_array($tag, array('image-intro', 'image-fulltext')) && isset($article->images)) {
				$images = json_decode($article->images);

				if ($tag == 'image-intro') {
					$class = 'img-intro-' . htmlspecialchars($images->float_intro);
					$caption = $images->image_intro_caption ? 'class="caption" title="' . htmlspecialchars($images->image_intro_caption) . '" ' : '';
					$src = htmlspecialchars($images->image_intro);
					$alt = htmlspecialchars($images->image_intro_alt);
				} else {
					$class = 'img-fulltext-' . htmlspecialchars($images->float_fulltext);
					$caption = $images->image_fulltext_caption ? 'class="caption" title="' . htmlspecialchars($images->image_fulltext_caption) . '" ' : '';
					$src = htmlspecialchars($images->image_fulltext);
					$alt = htmlspecialchars($images->image_fulltext_alt);
				}

				$str = '<div class="' . $class . '"><img ' . $caption . 'src="' . $src . '" alt="' . $alt . '"/></div>';
				$ok = 1;
			} else if (
				(strpos($tag, 'text') === 0)
				|| (strpos($tag, 'intro') === 0)
				|| (strpos($tag, 'full') === 0)
			) {
				// TEXT data
				$article->text = '';

				if (!(strpos($tag, 'intro') === false)) {
					if (isset($article->introtext)) {
						$article->text = $article->introtext;
						$ok = 1;
					}
				} else if (!(strpos($tag, 'full') === false)) {
					if (isset($article->fulltext)) {
						$article->text = $article->fulltext;
						$ok = 1;
					}
				} else if (!(strpos($tag, 'text') === false)) {
					if (isset($article->introtext) && isset($article->fulltext)) {
						$article->text = $article->introtext . $article->fulltext;
						$ok = 1;
					}
				}

				$article->parameters = new JRegistry;
				if ($type == 'k2') {
					$article->parameters->loadINI($article->params);
				} else {
					$article->parameters->loadString($article->attribs);
				}

				if ($this->params->run_content_plugins) {
					if (!$this->params->dispatcher) {
						$plugins = JPluginHelper::getPlugin('system');
						$plugins = array_merge($plugins, JPluginHelper::getPlugin('content'));
						foreach ($plugins as $plugin) {
							if ($plugin->type == 'system'
								&& in_array($plugin->name, array(
									'articlesanywhere',
									'betterpreview',
									'jcemediabox'
								))
							) {
								continue;
							}
							JPluginHelper::importPlugin($plugin->type, $plugin->name);
						}
						$this->params->dispatcher = clone(JDispatcher::getInstance());
					}
					$this->params->dispatcher->trigger('onPrepareContent', array(&$article, &$article->parameters, 0));
				}

				$str = $article->text;

				if ($extra) {
					$attribs = explode(':', $extra);

					$max = 0;
					$strip = 0;
					foreach ($attribs as $attrib) {
						if (trim($attrib) == 'strip') {
							$strip = 1;
						} else {
							$max = $attrib;
						}
					}

					$word_limit = (!(strpos($max, 'word') === false));
					if ($strip) {
						// remove pagenavcounter
						$str = preg_replace('#(<' . 'div class="pagenavcounter">.*?</div>)#si', ' ', $str);
						// remove pagenavbar
						$str = preg_replace('#(<' . 'div class="pagenavbar">(<div>.*?</div>)*</div>)#si', ' ', $str);
						// remove scripts
						$str = preg_replace('#(<' . 'script[^a-z0-9].*?</script>)#si', ' ', $str);
						$str = preg_replace('#(<' . 'noscript[^a-z0-9].*?</noscript>)#si', ' ', $str);
						// remove other tags
						$str = preg_replace('#(<' . '/?[a-z][a-z0-9]?.*?>)#si', ' ', $str);
						// remove double whitespace

						$str = trim(preg_replace('#\s+#s', ' ', $str));
						if ($max) {
							$orig_len = strlen($str);
							if ($word_limit) {
								// word limit
								$str = trim(preg_replace('#^(([^\s]+\s*){' . (int) $max . '}).*$#s', '\1', $str));
								if (strlen($str) < $orig_len) {
									if (preg_match('#[^a-z0-9]$#si', $str)) {
										$str .= ' ';
									}
									if ($this->params->use_ellipsis) {
										$str .= '...';
									}
								}
							} else {
								// character limit
								$max = (int) $max;
								if ($max < $orig_len) {
									$str = rtrim(substr($str, 0, ($max - 3)));
									if (preg_match('#[^a-z0-9]$#si', $str)) {
										$str .= ' ';
									}
									if ($this->params->use_ellipsis) {
										$str .= '...';
									}
								}
							}
						}
					} else if ($max && ($word_limit || (int) $max < strlen($str))) {
						$max = (int) $max;

						// store pagenavcounter & pagenav (exclude from count)
						preg_match('#<' . 'div class="pagenavcounter">.*?</div>#si', $str, $pagenavcounter);
						$pagenavcounter = isset($pagenavcounter['0']) ? $pagenavcounter['0'] : '';
						if ($pagenavcounter) {
							$str = str_replace($pagenavcounter, '<!-- ARTA_PAGENAVCOUNTER -->', $str);
						}
						preg_match('#<' . 'div class="pagenavbar">(<div>.*?</div>)*</div>#si', $str, $pagenav);
						$pagenav = isset($pagenav['0']) ? $pagenav['0'] : '';
						if ($pagenav) {
							$str = str_replace($pagenav, '<!-- ARTA_PAGENAV -->', $str);
						}

						// add explode helper strings around tags
						$explode_str = '<!-- ARTA_TAG -->';
						$str = preg_replace('#(<\/?[a-z][a-z0-9]?.*?>|<!--.*?-->)#si', $explode_str . '\1' . $explode_str, $str);

						$str_array = explode($explode_str, $str);

						$str = array();
						$tags = array();
						$count = 0;
						$is_script = 0;
						foreach ($str_array as $i => $str_part) {
							if (fmod($i, 2)) {
								// is tag
								$str[] = $str_part;
								preg_match('#^<(\/?([a-z][a-z0-9]*))#si', $str_part, $tag);
								if (!empty($tag)) {
									if ($tag['1'] == 'script') {
										$is_script = 1;
									}

									if (!$is_script
										// only if tag is not a single html tag
										&& (strpos($str_part, '/>') === false)
										// just in case single html tag has no closing character
										&& !in_array($tag['2'], array('area', 'br', 'hr', 'img', 'input', 'param'))
									) {
										$tags[] = $tag['1'];
									}

									if ($tag['1'] == '/script') {
										$is_script = 0;
									}
								}
							} else if ($is_script) {
								$str[] = $str_part;
							} else {
								if ($word_limit) {
									// word limit
									if ($str_part) {
										$words = explode(' ', trim($str_part));
										$word_count = count($words);
										if ($max < ($count + $word_count)) {
											$words_part = array();
											$word_count = 0;
											foreach ($words as $word) {
												if ($word) {
													$word_count++;
												}
												if ($max < ($count + $word_count)) {
													break;
												}
												$words_part[] = $word;
											}
											$string = rtrim(implode(' ', $words_part));
											if (preg_match('#[^a-z0-9]$#si', $string)) {
												$string .= ' ';
											}
											if ($this->params->use_ellipsis) {
												$string .= '...';
											}
											$str[] = $string;
											break;
										}
										$count += $word_count;
									}
									$str[] = $str_part;
								} else {
									// character limit
									if ($max < ($count + strlen($str_part))) {
										// strpart has to be cut off
										$maxlen = $max - $count;
										if ($maxlen < 3) {
											$string = '';
											if (preg_match('#[^a-z0-9]$#si', $str_part)) {
												$string .= ' ';
											}
											if ($this->params->use_ellipsis) {
												$string .= '...';
											}
											$str[] = $string;
										} else {
											$string = rtrim(substr($str_part, 0, ($maxlen - 3)));
											if (preg_match('#[^a-z0-9]$#si', $string)) {
												$string .= ' ';
											}
											if ($this->params->use_ellipsis) {
												$string .= '...';
											}
											$str[] = $string;
										}
										break;
									}
									$count += strlen($str_part);
									$str[] = $str_part;
								}
							}
						}

						// revers sort open tags
						krsort($tags);
						$tags = array_values($tags);
						$count = count($tags);

						for ($i = 0; $i < 3; $i++) {
							foreach ($tags as $ti => $tag) {
								if ($tag['0'] == '/') {
									for ($oi = $ti + 1; $oi < $count; $oi++) {
										$opentag = $tags[$oi];
										if ($opentag == $tag) {
											break;
										}
										if ('/' . $opentag == $tag) {
											unset($tags[$ti]);
											unset($tags[$oi]);
											break;
										}
									}
								}
							}
						}

						foreach ($tags as $tag) {
							// add closing tag to end of string
							if ($tag['0'] != '/') {
								$str[] = '</' . $tag . '>';
							}
						}
						$str = implode('', $str);

						$str = str_replace(array('<!-- ARTA_PAGENAVCOUNTER -->', '<!-- ARTA_PAGENAV -->'), array($pagenavcounter, $pagenav), $str);
					}
				}

				if ($art && isset($art->id) && $art->id) {
					$str = str_replace('view=article&amp;id=' . $art->id, 'view=article&amp;id=' . $article->id, $str);
				}
			} else if (ctype_alnum(str_replace(array('-', '_'), '', $tag))) {
				// Get data from db columns
				if (isset($article->$tag)) {
					$str = $article->$tag;
					$ok = 1;
				}
				// otherwise get data from parameter data
				if (!$ok) {
					$params = new stdClass();
					if (isset($article->attribs)) {
						$params = json_decode($article->attribs);
					} else if (isset($article->params)) {
						$params = json_decode($article->params);
					}
					if (isset($article->images)) {
						$images = json_decode($article->images);
					}
					if (isset($article->urls)) {
						$urls = json_decode($article->urls);
					}
					if ($params && isset($params->$tag)) {
						$str = $params->$tag;
						$ok = 1;
					} else if ($images && isset($images->$tag)) {
						$str = $images->$tag;
						$ok = 1;
					} else if ($urls && isset($urls->$tag)) {
						$str = $urls->$tag;
						$ok = 1;
					}
				}

				if ($ok
					&& !(strpos($str, '-') == false)
					&& !preg_match('#[a-z]#i', $str)
					&& strtotime($str)
				) {
					if (!$extra) {
						$extra = JText::_('DATE_FORMAT_LC2');
					}
					if (!(strpos($extra, '%') === false)) {
						$extra = NNText::dateToDateFormat($extra);
					}
					$str = JHtml::_('date', $str, $extra);
				}
			}

			if ($ok) {
				$text = str_replace($match['0'], $str, $text);
			}
		}

		return $text;
	}


	/*
	 * Protect input and text area's
	 */
	function addParagraphTags(&$string, $p_start = '', $p_end = '')
	{
		$str = trim(preg_replace('#<\!--.*?-->#si', '', $string));

		if ($str == '') {
			return;
		}

		// if there is a starting p tag
		if ($p_start) {
			$p_match = '#<p( |\s|>)#si';
			// add starting p tag if content has no starting p tag
			// or if ending p tag appears before starting p tag
			if (
				!(preg_match($p_match, $str))
				|| (
					!(stripos($str, '</p>') === false)
					&& stripos($str, '</p>') < stripos($str, '<p')
				)
			) {
				$string = $p_start . $string;
			}
		}
		// if there is a ending p tag
		if ($p_end) {
			// add ending p tag if content has no ending p tag
			// or if starting p tag appears later than ending p tag
			if (
				stripos($str, '</p>') === false
				|| strripos($str, '</p>') < strripos($str, '<p')
			) {
				$string .= $p_end;
			}
		}
	}

	function protect(&$str)
	{
		NNProtect::protectForm($str, '{' . $this->params->article_tag);
	}

	function unprotect(&$str)
	{
		NNProtect::unprotectForm($str, '{' . $this->params->article_tag);
	}

	function cleanLeftoverJunk(&$str)
	{
		if (!(strpos($str, '{/' . $this->params->article_tag . '}') === false)) {
			$regex = $this->params->regex;
			if (@preg_match($regex . 'u', $str)) {
				$regex .= 'u';
			}
			$str = preg_replace($regex, '', $str);
		}
		$str = preg_replace('#<\!-- (START|END): ARTA_[^>]* -->#', '', $str);
		if (!$this->params->place_comments) {
			$str = str_replace(array(
				$this->params->comment_start, $this->params->comment_end,
				htmlentities($this->params->comment_start), htmlentities($this->params->comment_end),
				urlencode($this->params->comment_start), urlencode($this->params->comment_end)
			), '', $str);
			$str = preg_replace('#' . preg_quote($this->params->message_start, '#') . '.*?' . preg_quote($this->params->message_end, '#') . '#', '', $str);
		}
	}

	function updateHead(&$html, &$orig_document)
	{
		if (strpos($html, '</head>') === false) {
			return;
		}
		$document = JFactory::getDocument();

		// get line endings
		$lnEnd = $document->_getLineEnd();
		$tab = $document->_getTab();
		$tagEnd = ' />';
		$str = '';

		// Generate link declarations
		foreach ($document->_links as $link) {
			if (!in_array($link, $orig_document->_links)) {
				$str .= $tab . $link . $tagEnd . $lnEnd;
			}
		}

		// Generate stylesheet links
		foreach ($document->_styleSheets as $strSrc => $strAttr) {
			if (!array_key_exists($strSrc, $orig_document->_styleSheets)) {
				$str .= $tab . '<link rel="stylesheet" href="' . $strSrc . '" type="' . $strAttr['mime'] . '"';
				if (!is_null($strAttr['media'])) {
					$str .= ' media="' . $strAttr['media'] . '" ';
				}
				$temp = JArrayHelper::toString($strAttr['attribs']);
				if ($temp) {
					$str .= ' ' . $temp;
				}
				$str .= $tagEnd . $lnEnd;
			}
		}

		// Generate stylesheet declarations
		foreach ($document->_style as $type => $content) {
			if (!in_array($content, $orig_document->_style)) {
				$str .= $tab . '<style type="' . $type . '">' . $lnEnd;

				// This is for full XHTML support.
				if ($document->_mime == 'text/html') {
					$str .= $tab . $tab . '<!--' . $lnEnd;
				} else {
					$str .= $tab . $tab . '<![CDATA[' . $lnEnd;
				}

				$str .= $content . $lnEnd;

				// See above note
				if ($document->_mime == 'text/html') {
					$str .= $tab . $tab . '-->' . $lnEnd;
				} else {
					$str .= $tab . $tab . ']]>' . $lnEnd;
				}
				$str .= $tab . '</style>' . $lnEnd;
			}
		}

		// Generate script file links
		foreach ($document->_scripts as $strSrc => $strType) {
			if (!array_key_exists($strSrc, $orig_document->_scripts)) {
				$str .= $tab . '<script type="' . $strType . '" src="' . $strSrc . '"></script>' . $lnEnd;
			}
		}

		// Generate script declarations
		foreach ($document->_script as $type => $content) {
			if (!in_array($content, $orig_document->_script)) {
				$str .= $tab . '<script type="' . $type . '">' . $lnEnd;

				// This is for full XHTML support.
				if ($document->_mime != 'text/html') {
					$str .= $tab . $tab . '<![CDATA[' . $lnEnd;
				}

				$str .= $content . $lnEnd;

				// See above note
				if ($document->_mime != 'text/html') {
					$str .= $tab . $tab . '// ]]>' . $lnEnd;
				}
				$str .= $tab . '</script>' . $lnEnd;
			}
		}

		foreach ($document->_custom as $custom) {
			if (!in_array($custom, $orig_document->_custom)) {
				$str .= $tab . $custom . $lnEnd;
			}
		}

		JResponse::setBody(str_replace('</head>', $str . "\n" . '</head>', JResponse::getBody()));
	}
}