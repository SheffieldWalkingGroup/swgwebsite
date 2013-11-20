<?php
defined('JPATH_BASE') or die;
JLoader::register('Leader', JPATH_SITE."/swg/Models/Leader.php");

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
					$data->swg_extras = array(
						'leaderid' => $result['ID'],
						'leadersetup' => 1,
						
					);
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
		$oldLeader = Leader::getJoomlaUser($userId);
		
		if ($oldLeader == null && !empty($data['swg_extras']['leadersetup']))
		{
			// Create a new leader account
			$leader = new Leader();
			$leader->username = $data['username'];
			$leader->password = "badgerx3"; // TODO: This is temporary
			$leader->surname = substr($data['name'], strrpos($data['name'], " ")+1);
			$leader->forename = substr($data['name'], 0, strrpos($data['name'], " "));
			$leader->email = $data['email'];
			$leader->active = true;
			$leader->joomlaUserID = $userId;
			
			$leader->save();
			
		}
		else if (!empty($data['swg_extras']['leaderid']) && $data['swg_extras']['leaderid'] != $oldLeader->id)
		{
			if ($oldLeader != null)
			{
				// Disconnect from old leader
				$oldLeader->joomlaUserID = null;
				$oldLeader->save();
			}
			
			// Connect to new leader
			$leader = Leader::getLeader($data['swg_extras']['leaderid']);
			$leader->joomlaUserID = $userId;
			$leader->save();
		}
		
		/*$db = JFactory::getDbo();
		// Remove any existing links to this user
		$db->setQuery("UPDATE walkleaders SET joomlauser = null WHERE joomlauser = '$userId'");
		$db->query(); // TODO: Check for success
		// Create the new link
		$db->setQuery("UPDATE walkleaders SET joomlauser = '$userId' WHERE ID = '{$data['swg_extras']['leaderid']}'");
		$db->query();
		*/
	}
}