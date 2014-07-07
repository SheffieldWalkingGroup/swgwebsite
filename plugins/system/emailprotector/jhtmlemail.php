<?php
/**
 * JHtmlEmail File
 *
 * @package         Email Protector
 * @version         1.2.4
 *
 * @author          Peter van Westen <peter@nonumber.nl>
 * @link            http://www.nonumber.nl
 * @copyright       Copyright © 2014 NoNumber All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('JPATH_PLATFORM') or die;

abstract class JHtmlEmail
{
	public static function cloak($mail, $mailto = true, $text = '', $email = true)
	{
		if ($mailto)
		{
			if (!$text)
			{
				$text = $mail;
			}
			$mail = '<a href="mailto:' . $mail . '">' . $text . '</a>';
		}
		return $mail;
	}
}
