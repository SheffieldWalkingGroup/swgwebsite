<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

JHtml::_('behavior.keepalive');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.tooltip');
?>

<?php if ($this->walk || $this->proposal) $this->display('propose'); else $this->display('list'); ?>
