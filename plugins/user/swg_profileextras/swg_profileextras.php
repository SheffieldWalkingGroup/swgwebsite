<?php
defined('JPATH_BASE') or die;

/**
 * Adds any extras onto user profiles.
 * Currently this just means connecting leader records to Joomla users.
 * Only admins can make the connection, not the users.
 */
class plgUserSWG_ProfileExtras extends JPlugin
{
	function onContentPrepareData($context, $data)
	{
		if ($context != "com_users.user")
			return true;
		
		if (is_object($data))
		{
			$userId = isset($data->id) ? $data->id : 0;

			if (!isset($data->swg_extras) and $userId > 0)
			{
				$db = JFactory::getDbo();
				$db->setQuery("SELECT ID FROM walkleaders WHERE joomlauser = '$userId' LIMIT 1");
				$result = $db->loadAssoc();
				
				if (!empty($result))
				{
					$data->swg_extras = array('leaderid' => $result['ID']);
				}
			}
		}
	}
	function onContentPrepareForm(JForm $form, $data)
	{
		if ($form->getName() != "com_users.user")
			return true;
	
		// Add the extra fields
		JForm::addFormPath(dirname(__FILE__) . '/profiles');
		$form->loadFile('profile', false);
		
		return true;
	
	}
	
	function onUserAfterSave($data, $isNew, $result, $error)
	{
		$userId = JArrayHelper::getValue($data, 'id', null, 'int');
		
		$db = JFactory::getDbo();
		// Remove any existing links to this user
		$db->setQuery("UPDATE walkleaders SET joomlauser = null WHERE joomlauser = '$userId'");
		$db->query(); // TODO: Check for success
		// Create the new link
		$db->setQuery("UPDATE walkleaders SET joomlauser = '$userId' WHERE ID = '{$data['swg_extras']['leaderid']}'");
		$db->query();
		
	}
}