<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

echo "<h1>".$this->pageTitle."</h1>";?>

<?php 
  if ($this->showSearch())
    $this->display("searchform");
  
  if ($this->showSearchResults()):?>
    <h2>Search results</h2>
    <p><?php echo count($this->walks); ?> walks found</p>
<?php 
  endif;
  
  if ($this->showList())
    $this->display("list");
?>