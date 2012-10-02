<?php
/**
 * Element: Author
 * Displays a selectbox of authors
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

class JFormFieldNN_Author extends JFormField
{
	public $type = 'Author';

	protected function getInput()
	{
		return JHtml::_('list.users', $this->name, $this->value, 1);
	}
}