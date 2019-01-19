<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

if ($this->showSearch())
	$this->display("searchform");

if ($this->showSearchResults()):?>
	<h3>Search results</h3>
	<p><?php echo count($this->walks); ?> walks found</p>
<?php endif;
if ($this->controller->canAdd()):?>
	<p><a href="/volunteer/lead/new-walk">Plan a new walk</a></p>
<?php endif;

if ($this->showList())
{
	$this->display("list");
}
if ($this->controller->canAdd()):?>
	<p><a href="/volunteer/lead/new-walk">Plan a new walk</a></p>
<?php endif;?>
