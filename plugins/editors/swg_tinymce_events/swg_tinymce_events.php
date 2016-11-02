<?php

defined('_JEXEC') or die;
require_once(JPATH_PLUGINS."/editors/tinymce/tinymce.php");
/**
 * TinyMCE with simplified options for use in events
 */
class PlgEditorSWG_TinyMCE_events extends PlgEditorTinymce
{
	
	/**
	 * Initialises the Editor.
	 *
	 * @return  string  JavaScript Initialization string
	 *
	 * @since 1.5
	 */
	public function onInit()
	{
		$language = JFactory::getLanguage();

		$mode = (int) $this->params->get('mode', 1);
		$theme	= 'modern';
		$skin	= $this->params->get('skin', '0');

		switch ($skin)
		{
			case '0':
			default:
				$skin = 'skin : "lightgray",';
		}

		$entity_encoding	= $this->params->get('entity_encoding', 'raw');

		$langMode			= $this->params->get('lang_mode', 0);
		$langPrefix			= $this->params->get('lang_code', 'en');

		if ($langMode)
		{
			if (file_exists(JPATH_ROOT . "/media/editors/tinymce/langs/" . $language->getTag() . ".js"))
			{
				$langPrefix = $language->getTag();
			}
			elseif (file_exists(JPATH_ROOT . "/media/editors/tinymce/langs/" . substr($language->getTag(), 0, strpos($language->getTag(), '-')) . ".js"))
			{
				$langPrefix = substr($language->getTag(), 0, strpos($language->getTag(), '-'));
			}
			else
			{
				$langPrefix = "en";
			}
		}

		$text_direction = 'ltr';

		if ($language->isRTL())
		{
			$text_direction = 'rtl';
		}

		$use_content_css	= $this->params->get('content_css', 1);
		$content_css_custom	= $this->params->get('content_css_custom', '');

		/*
		 * Lets get the default template for the site application
		 */
		$db		= JFactory::getDbo();
		$query	= $db->getQuery(true)
			->select('template')
			->from('#__template_styles')
			->where('client_id=0 AND home=' . $db->quote('1'));

		$db->setQuery($query);
		$template = $db->loadResult();

		$content_css = '';

		$templates_path = JPATH_SITE . '/templates';

		// Loading of css file for 'styles' dropdown
		if ( $content_css_custom )
		{
			// If URL, just pass it to $content_css
			if (strpos($content_css_custom, 'http') !== false)
			{
				$content_css = 'content_css : "' . $content_css_custom . '",';
			}

			// If it is not a URL, assume it is a file name in the current template folder
			else
			{
				$content_css = 'content_css : "' . JUri::root() . 'templates/' . $template . '/css/' . $content_css_custom . '",';

				// Issue warning notice if the file is not found (but pass name to $content_css anyway to avoid TinyMCE error
				if (!file_exists($templates_path . '/' . $template . '/css/' . $content_css_custom))
				{
					$msg = sprintf(JText::_('PLG_TINY_ERR_CUSTOMCSSFILENOTPRESENT'), $content_css_custom);
					JLog::add($msg, JLog::WARNING, 'jerror');
				}
			}
		}
		else
		{
			// Process when use_content_css is Yes and no custom file given
			if ($use_content_css)
			{
				// First check templates folder for default template
				// if no editor.css file in templates folder, check system template folder
				if (!file_exists($templates_path . '/' . $template . '/css/editor.css'))
				{
					// If no editor.css file in system folder, show alert
					if (!file_exists($templates_path . '/system/css/editor.css'))
					{
						JLog::add(JText::_('PLG_TINY_ERR_EDITORCSSFILENOTPRESENT'), JLog::WARNING, 'jerror');
					}
					else
					{
						$content_css = 'content_css : "' . JUri::root() . 'templates/system/css/editor.css",';
					}
				}
				else
				{
					$content_css = 'content_css : "' . JUri::root() . 'templates/' . $template . '/css/editor.css",';
				}
			}
		}

		$relative_urls = $this->params->get('relative_urls', '1');

		if ($relative_urls)
		{
			// Relative
			$relative_urls = "true";
		}
		else
		{
			// Absolute
			$relative_urls = "false";
		}

		$newlines = $this->params->get('newlines', 0);

		if ($newlines)
		{
			// Break
			$forcenewline = "force_br_newlines : true, force_p_newlines : false, forced_root_block : '',";
		}
		else
		{
			// Paragraph
			$forcenewline = "force_br_newlines : false, force_p_newlines : true, forced_root_block : 'p',";
		}

		$invalid_elements	= $this->params->get('invalid_elements', 'script,applet,iframe');
		$extended_elements	= $this->params->get('extended_elements', '');

		// Advanced Options
		$html_height		= $this->params->get('html_height', '550');
		$html_width			= $this->params->get('html_width', '750');

		// Image advanced options
		$image_advtab = $this->params->get('image_advtab', 1);

		if ($image_advtab)
		{
			$image_advtab = "true";
		}
		else
		{
			$image_advtab = "false";
		}

		// The param is true false, so we turn true to both rather than showing vertical resize only
		$resizing = $this->params->get('resizing', '1');

		if ($resizing || $resizing == 'true')
		{
			$resizing = 'resize: "both",';
		}
		else
		{
			$resizing = 'resize: false,';
		}
		
		// SWG Begin customised options

		$toolbar1_add = array(
			'styleselect', '|', 'bold', 'italic', 'subscript','superscript', '|',
			'undo', 'redo', '|',
			'link', 'unlink','|',
			'bullist', 'numlist', '|',
			'outdent', 'indent', '|',
			'table', 'image', 
		);
		$toolbar2_add = array();
		$toolbar3_add = array();
		$toolbar4_add = array();
		$elements = array();
		$plugins = array('autolink', 'lists', 'link', 'paste', 'image', 'preview', 'table', 'tabfocus', 'importcss');
		
		// Prepare config variables
		$plugins = implode(',', $plugins);
		$elements = implode(',', $elements);

		// Prepare config variables
		$toolbar1 = implode(' ', $toolbar1_add);
		$toolbar2 = implode(' ', $toolbar2_add);
		$toolbar3 = implode(' ', $toolbar3_add);
		$toolbar4 = implode(' ', $toolbar4_add);

		// See if mobileVersion is activated
		$mobileVersion = $this->params->get('mobile', 0);

		$load = "\t<script type=\"text/javascript\" src=\"" .
				JUri::root() . $this->_basePath .
				"/tinymce.min.js\"></script>\n";

		/**
		 * Shrink the buttons if not on a mobile or if mobile view is off.
		 * If mobile view is on force into simple mode and enlarge the buttons
		**/
		if (!$this->app->client->mobile)
		{
			$smallButtons = 'toolbar_items_size: "small",';
		}
		elseif ($mobileVersion == false)
		{
			$smallButtons = '';
		}
		else
		{
			$smallButtons = '';
			$mode = 0;
		}

		$return = $load .
		"\t<script type=\"text/javascript\">
		tinyMCE.init({
			// General
			directionality: \"$text_direction\",
			language : \"$langPrefix\",
			mode : \"specific_textareas\",
			autosave_restore_when_empty: false,
			$skin
			theme : \"$theme\",
			schema: \"html5\",
			element_format: \"html\",
			menubar: false,
			selector: \"textarea.mce_editable\",
			// Cleanup/Output
			inline_styles : true,
			gecko_spellcheck : true,
			entity_encoding : \"$entity_encoding\",
			extended_valid_elements : \"$elements\",
			$forcenewline
			$smallButtons
			invalid_elements : \"$invalid_elements\",
			style_formats: [
				{title: 'Plain text', block: 'p'},
				{title: 'Box out', block: 'p', classes: 'boxout'}
			],
			// Plugins
			plugins : \"$plugins\",
			// Toolbar
			toolbar1: \"$toolbar1\",
			toolbar2: \"$toolbar2\",
			toolbar3: \"$toolbar3\",
			toolbar4: \"$toolbar4\",
			removed_menuitems: \"newdocument\",
			// URL
			relative_urls : $relative_urls,
			remove_script_host : false,
			document_base_url : \"" . JUri::root() . "\",
			rel_list : [
				{title: 'Alternate', value: 'alternate'},
				{title: 'Author', value: 'author'},
				{title: 'Bookmark', value: 'bookmark'},
				{title: 'Help', value: 'help'},
				{title: 'License', value: 'license'},
				{title: 'Lightbox', value: 'lightbox'},
				{title: 'Next', value: 'next'},
				{title: 'No Follow', value: 'nofollow'},
				{title: 'No Referrer', value: 'noreferrer'},
				{title: 'Prefetch', value: 'prefetch'},
				{title: 'Prev', value: 'prev'},
				{title: 'Search', value: 'search'},
				{title: 'Tag', value: 'tag'}
			],
			// Layout
			$content_css
			importcss_append: true,
			// Advanced Options
			$resizing
			image_advtab: $image_advtab,
			height : \"$html_height\",
			width : \"$html_width\",

		});
		</script>";
		
		return $return;
	}

}
