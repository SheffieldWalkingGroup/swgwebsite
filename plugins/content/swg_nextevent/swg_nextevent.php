<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport( 'joomla.plugin.plugin' );
class plgContentSWG_NextEvent extends JPlugin {
  
	public function onContentPrepare($context, &$article, &$params, $page = 0)
	{
		// Don't run this plugin when the content is being indexed
		if ($context == 'com_finder.indexer') {
			return true;
		}
		
		// simple performance check to determine whether bot should process further
		if (strpos($article->text, '{swg_nextevent') === false) {
			return true;
		}
		
		// Find the instances of the plugin and any parameters
		preg_match_all("/{swg_nextevent(|[^}]+)?}/", $article->text, $matches, PREG_SET_ORDER);
		foreach ($matches as $instance)
		{
			// Get the arguments of this instance
			// They're in $instance[1], but strip off the leading | first
			$instance[1] = substr($instance[1],1);
			$args = explode("|",$instance[1]);
			if (count($args) < 2)
				continue; // Need at least 2 arguments
			list($type, $field) = $args;
			
			// Set the default text for if no events are found
			if (count($args) >= 3)
				$return = $args[2];
			else
				$return = "None scheduled";
				
			
			// Get the next event of that type
			// TODO: eventFactory method to take strings
			switch (strtolower($type))
			{
				case "walk":
					$factory = SWG::walkInstanceFactory();
					break;
				case "newmembersocial":
					$newMembers = true;
				case "social":
					$factory = SWG::socialFactory();
					$factory->getNormal = !$newMembers;
					$factory->getNewMember = $newMembers;
					break;
				case "weekend":
					$factory = SWG::weekendFactory();
					break;
			}
			
				$events = $factory->getNext(1);
			
			if (!empty($events))
			{
				$event = $events[0];
				switch (strtolower($field))
				{
				case "date":
					$return = date("l jS M", $event->start);
					break;
				case "newmemberstart":
					$return = date("H:i", $event->newMemberStart);
					break;
				default:
					$return = "{{Unknown field type: ".$field."}}";
				}
			}
			
			// Put this text into the article
			$article->text = str_replace($instance[0], $return, $article->text);
		
		}
		
		return true;
		
	}
}