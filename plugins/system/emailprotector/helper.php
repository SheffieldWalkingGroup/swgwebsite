<?php
/**
 * Plugin Helper File
 *
 * @package         Email Protector
 * @version         1.2.4
 *
 * @author          Peter van Westen <peter@nonumber.nl>
 * @link            http://www.nonumber.nl
 * @copyright       Copyright © 2014 NoNumber All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once JPATH_PLUGINS . '/system/nnframework/helpers/text.php';
require_once JPATH_PLUGINS . '/system/nnframework/helpers/protect.php';

/**
 * Plugin that places components
 */
class plgSystemEmailProtectorHelper
{
	private $name = 'Email Protector';
	private $params = null;

	function __construct(&$params)
	{
		$this->params = $params;

		$this->params->atrr_pre = 'data-ep-a' . substr(md5('a' . rand(1000, 9999)), 0, 4);
		$this->params->atrr_post = 'data-ep-b' . substr(md5('b' . rand(1000, 9999)), 0, 4);

		// email@domain.com
		$this->params->regex_email = '([\w\.\-]+\@(?:[a-z0-9\.\-]+\.)+(?:[a-z0-9\-]{2,10}))';

		$this->params->regex = '#' . $this->params->regex_email . '#i';
		$this->params->regex_js = '#<script[^>]*[^/]>.*?</script>#si';
		$this->params->regex_injs = '#([\'"])' . $this->params->regex_email . '\1#i';
		$this->params->regex_link = '#<a\s+((?:[^>]*\s+)?)href\s*=\s*"mailto:(' . $this->params->regex_email . '(?:\?[^"]+)?)"((?:\s+[^>]*)?)>(.*?)</a>#si';
	}

	function onContentPrepare(&$article, &$context)
	{
		if (
		(JFactory::getDocument()->getType() !== 'html'
			&& ($this->params->protect_in_feeds && JFactory::getDocument()->getType() !== 'feed'))
		)
		{
			return;
		}

		if (isset($article->text)
			&& !(
				$context == 'com_content.category'
				&& JFactory::getApplication()->input->get('view') == 'category'
				&& !JFactory::getApplication()->input->get('layout')
			)
		)
		{
			$this->protectEmails($article->text);
		}
		if (isset($article->description))
		{
			$this->protectEmails($article->description);
		}
		if (isset($article->title))
		{
			$this->protectEmails($article->title);
		}
		if (isset($article->created_by_alias))
		{
			$this->protectEmails($article->created_by_alias);
		}
	}

	function onAfterDispatch()
	{
		// PDF
		if (JFactory::getDocument()->getType() == 'pdf')
		{
			$buffer = JFactory::getDocument()->getBuffer('component');
			if (is_array($buffer))
			{
				if (isset($buffer['component'], $buffer['component']['']))
				{
					if (isset($buffer['component']['']['component'], $buffer['component']['']['component']['']))
					{
						$this->protectEmails($buffer['component']['']['component'][''], 0);
					}
					else
					{
						$this->protectEmails($buffer['component'][''], 0);
					}
				}
				else if (isset($buffer['0'], $buffer['0']['component'], $buffer['0']['component']['']))
				{
					if (isset($buffer['0']['component']['']['component'], $buffer['0']['component']['']['component']['']))
					{
						$this->protectEmails($buffer['component']['']['component'][''], 0);
					}
					else
					{
						$this->protectEmails($buffer['0']['component'][''], 0);
					}
				}
			}
			else
			{
				$this->protectEmails($buffer);
			}
			JFactory::getDocument()->setBuffer($buffer, 'component');
			return;
		}

		// only in html or feed
		if (JFactory::getDocument()->getType() !== 'html'
			&& ($this->params->protect_in_feeds && JFactory::getDocument()->getType() !== 'feed')
		)
		{
			return;
		}

		$buffer = JFactory::getDocument()->getBuffer('component');

		if (empty($buffer) || is_array($buffer))
		{
			return;
		}

		if (JFactory::getDocument()->getType() == 'html')
		{
			$style = "
				.cloaked_email span:before {
					content: attr(" . $this->params->atrr_pre . ");
				}
				.cloaked_email span:after {
					content: attr(" . $this->params->atrr_post . ");
				}
			";
			JFactory::getDocument()->addStyleDeclaration('/* START: ' . $this->name . ' styles */ ' . preg_replace('#\n\s*#s', ' ', trim(preg_replace('#\s\s+#s', ' ', $style))) . ' /* END: ' . $this->name . ' styles */');

			/* Below javascript is minified via http://closure-compiler.appspot.com/home
			$script = '
				var emailProtector = ( emailProtector || {} );
				emailProtector.addCloakedMailto = function(id, link){
					var el = document.getElementById(id);
					if(el) {
						var els = el.getElementsByTagName("span");
						var pre = "";
						var post = "";
						for (var i = 0, l = els.length; i < l; i++) {
							pre += els[i].getAttribute("' . $this->params->atrr_pre . '");
							post = els[i].getAttribute("' . $this->params->atrr_post . '") + post;
						}
						el.innerHTML = pre + post;
						if(link) {
							el.parentNode.href= "mailto:" + pre + post;
						}
					}
				}
			';
			*/
			$script = 'var emailProtector=emailProtector||{};emailProtector.addCloakedMailto=function(f,g){var a=document.getElementById(f);if(a){for(var e=a.getElementsByTagName("span"),b="",c="",d=0,h=e.length;d<h;d++)b+=e[d].getAttribute("' . $this->params->atrr_pre . '"),c=e[d].getAttribute("' . $this->params->atrr_post . '")+c;a.innerHTML=b+c;g&&(a.parentNode.href="mailto:"+b+c)}};';
			JFactory::getDocument()->addScriptDeclaration('/* START: ' . $this->name . ' scripts */ ' . preg_replace('#\n\s*#s', ' ', trim($script)) . ' /* END: ' . $this->name . ' scripts */');
		}
		$this->protectEmails($buffer, 'component');

		JFactory::getDocument()->setBuffer($buffer, 'component');
	}

	function onAfterRender()
	{
		// only in html and feeds
		if (JFactory::getDocument()->getType() !== 'html'
			&& ($this->params->protect_in_feeds && JFactory::getDocument()->getType() !== 'feed')
		)
		{
			return;
		}

		$html = JResponse::getBody();
		if ($html == '')
		{
			return;
		}

		// only do stuff in body
		list($pre, $body, $post) = nnText::getBody($html);
		$this->protectEmails($body);
		$html = $pre . $body . $post;

		if (JFactory::getDocument()->getType() == 'html')
		{
			if (strpos($html, 'addCloakedMailto') === false)
			{
				// remove style and script if no emails are found
				$html = preg_replace('#/\* START: ' . $this->name . ' .*?/\* END: ' . $this->name . ' [a-z]* \*/\s*#s', '', $html);
			}
			else
			{
				// correct attribut ids in possible cached modules/content
				$html = preg_replace('# data-ep-a[a-z0-9]{4}=#s', ' ' . $this->params->atrr_pre . '=', $html);
				$html = preg_replace('# data-ep-b[a-z0-9]{4}=#s', ' ' . $this->params->atrr_post . '=', $html);

				NNProtect::removeInlineComments($html, $this->name);
			}
		}

		JResponse::setBody($html);
	}

	function protectEmails(&$str, $area = '')
	{
		if (!is_string($str) || $str == '')
		{
			return;
		}

		// No action needed if no @ is found
		if (strpos($str, '@') === false)
		{
			return;
		}

		if ($this->params->protect_in_js && strpos($str, '</script>') !== false)
		{
			if (preg_match_all($this->params->regex_js, $str, $matches, PREG_SET_ORDER) > 0)
			{
				foreach ($matches as $match)
				{
					$script = $match[0];

					while (preg_match($this->params->regex_injs, $script, $regs, PREG_OFFSET_CAPTURE))
					{
						$protected = str_replace(
							array('.', '@'),
							array(
								$regs[1][0] . ' + ' . 'String.fromCharCode(46)' . ' + ' . $regs[1][0],
								$regs[1][0] . ' + ' . 'String.fromCharCode(64)' . ' + ' . $regs[1][0]
							),
							$regs[0][0]
						);
						$script = substr_replace($script, $protected, $regs[0][1], strlen($regs[0][0]));
					}

					$str = str_replace($match[0], $script, $str);
				}
			}
		}

		$this->protect($str);

		// Do not protect if {emailprotector=off} is found in text
		if (strpos($str, '{emailprotector=off}') !== false || strpos($str, '<!-- EPOFF -->') !== false)
		{
			$str = str_replace(array('<p>{emailprotector=off}</p>', '{emailprotector=off}'), '<!-- EPOFF -->', $str);
			NNProtect::unprotect($str);
			return;
		}

		// Fix derivatives of link code <a href="http://mce_host/ourdirectory/email@domain.com">email@domain.com</a>
		// This happens when inserting an email in TinyMCE, cancelling its suggestion to add the mailto: prefix...
		if (strpos($str, 'mce_host') !== false)
		{
			$str = preg_replace('#"http://mce_host([\x20-\x7f][^<>]+/)#i', '"mailto:', $str);
		}

		// Search for derivatives of link code <a href="mailto:email@domain.com">anytext</a>
		while (preg_match($this->params->regex_link, $str, $regs, PREG_OFFSET_CAPTURE))
		{
			$mail = str_replace('&amp;', '&', $regs[2][0]);
			$protected = $this->protectEmail($mail, $regs[5][0], $regs[1][0], $regs[4][0]);
			$str = substr_replace($str, $protected, $regs[0][1], strlen($regs[0][0]));
		}

		NNProtect::protectHtmlTags($str);

		$pre = '';
		$post = '';
		$len = strlen($str);

		// Split the string for very long strings
		if ($len > 2000)
		{
			$first = strpos($str, '@');

			if ($first === false)
			{
				NNProtect::unprotect($str);
				return;
			}

			$last = strrpos($str, '@');

			$first = max($first - 100, 0);
			$last = min($last + 100, $len);

			$pre = substr($str, 0, $first);
			$post = substr($str, $last);
			$str = substr($str, $first, $last - $first);
		}

		// Search for plain text email@domain.com
		while (preg_match($this->params->regex, $str, $regs, PREG_OFFSET_CAPTURE))
		{
			$protected = $this->protectEmail('', $regs[1][0]);
			$str = substr_replace($str, $protected, $regs[1][1], strlen($regs[1][0]));
		}

		$str = $pre . $str . $post;

		NNProtect::unprotect($str);
	}

	/**
	 * Protects the email address with a series of spans
	 *
	 * @param   string  $mailto The mailto address in the surronding link.
	 * @param   string  $text   Text containing possible emails
	 * @param   boolean $pre    Prepending attributes in <a> tag
	 * @param   boolean $post   Ending attributes in <a> tag
	 *
	 * @return  string  The cloaked email.
	 */
	protected function protectEmail($mailto, $text = '', $pre = '', $post = '')
	{
		$id = 0;

		// In FEEDS

		if (JFactory::getDocument()->getType() == 'feed')
		{
			// Replace with custom text
			if ($this->params->protect_in_feeds == 2)
			{
				return JText::_($this->params->feed_text);
			}

			// Replace with spoofed email
			if (!$text)
			{
				$text = $mailto;
			}

			self::spoofEmails($text, 0);
			return $text;
		}

		// In HTML

		if ($text)
		{
			if ($this->params->spoof)
			{
				self::spoofEmails($text);
			}
			while (preg_match($this->params->regex, $text, $regs, PREG_OFFSET_CAPTURE))
			{
				$id = 'ep_' . substr(md5(rand()), 0, 8);
				$protected = self::createSpans($regs[1][0], $id);
				$text = substr_replace($text, $protected, $regs[1][1], strlen($regs[1][0]));
			}
			if ($id && !$mailto && $this->params->mode == 1)
			{
				return self::createLink($text, $id, $pre, $post);
			}
		}

		if ($this->params->mode && $mailto)
		{
			$id = 'ep_' . substr(md5(rand()), 0, 8);
			if ($text)
			{
				$text .= self::createSpans($mailto, $id, 1);
			}
			else
			{
				$text = self::createSpans($mailto, $id, 1);
				if ($this->params->spoof)
				{
					$id = 'ep_' . substr(md5(rand()), 0, 8);
					self::spoofEmails($mailto);
					$text .= self::createSpans($mailto, $id, 0);
				}
			}

			return self::createLink($text, $id, $pre, $post);
		}

		if ($id)
		{
			return self::createOutput($text, $id);
		}

		return $text;
	}

	/**
	 * Replace @ and dots with [AT] and [DOT]
	 *
	 * @param   string $text Text containing possible emails
	 */
	protected function spoofEmails(&$text, $ishtml = 1)
	{
		while (preg_match($this->params->regex, $text, $regs, PREG_OFFSET_CAPTURE))
		{
			if ($ishtml)
			{
				$replace = array('<small> ' . JText::_('EP_AT') . ' </small>', '<small> ' . JText::_('EP_DOT') . ' </small>');
			}
			else
			{
				$replace = array(' ' . JText::_('EP_AT') . ' ', ' ' . JText::_('EP_DOT') . ' ');
			}

			$email = str_replace(array('@', '.'), $replace, $regs[1][0]);
			$text = substr_replace($text, $email, $regs[1][1], strlen($regs[1][0]));
		}
	}

	/**
	 * Convert text to encoded spans.
	 *
	 * @param   string  $str  Text to convert.
	 * @param   string  $id   ID of the main span.
	 * @param   boolean $hide Hide the spans?
	 *
	 * @return  string   The encoded string.
	 */
	protected function createSpans($str, $id = 0, $hide = 0)
	{
		$split = str_split($str);
		$size = ceil(count($split) / 6);
		$parts = array('', '', '', '', '', '');
		foreach ($split as $i => $c)
		{
			$v = ($c == '@' || (strlen($c) === 1 && rand(0, 2))) ? '&#' . ord($c) . ';' : $c;
			$pos = floor($i / $size);
			$parts[$pos] .= $v;
		}

		$parts = array(
			array($parts['0'], $parts['5']),
			array($parts['1'], $parts['4']),
			array($parts['2'], $parts['3'])
		);

		$html = array();

		$html[] = '<span class="cloaked_email"' . ($id ? ' id="' . $id . '"' : '') . '' . ($hide ? ' style="display:none;"' : '') . '>';
		foreach ($parts as $part)
		{
			$atrr = array(
				$this->params->atrr_pre . '="' . $part['0'] . '"',
				$this->params->atrr_post . '="' . $part['1'] . '"'
			);
			shuffle($atrr);
			$html[] = '<span ' . implode(' ', $atrr) . '>';
		}
		$html[] = '</span></span></span></span>';

		return implode('', $html);
	}

	/**
	 * Create output with comment tag and script
	 *
	 * @param   string $text Inner text.
	 * @param   string $id   ID of the main span.
	 *
	 * @return  string   The html.
	 */
	protected function createOutput($text, $id)
	{
		return '<!--- ' . JText::_('EP_MESSAGE_PROTECTED') . ' --->' . $text
		. '<script type="text/javascript">emailProtector.addCloakedMailto("' . $id . '", 0);</script>';
	}

	/**
	 * Create output with comment tag and script and a link arround the text
	 *
	 * @param   string  $text Inner text.
	 * @param   string  $id   ID of the main span.
	 * @param   boolean $pre  Prepending attributes in <a> tag
	 * @param   boolean $post Ending attributes in <a> tag
	 *
	 * @return  string   The html.
	 */
	protected function createLink($text, $id, $pre = '', $post = '')
	{
		return
			'<a ' . $pre . 'href="javascript:// ' . htmlentities(JText::_('EP_MESSAGE_PROTECTED'), ENT_COMPAT, 'UTF-8') . '"' . $post . '>'
			. $text
			. '</a>'
			. '<script type="text/javascript">emailProtector.addCloakedMailto("' . $id . '", 1);</script>';
	}

	function protect(&$str)
	{
		NNProtect::protectFields($str);
		NNProtect::protectScripts($str);
		NNProtect::protectSourcerer($str);
	}
}
