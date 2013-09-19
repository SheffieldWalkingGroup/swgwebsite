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
 		if (!in_array($context, array('com_users.profile', 'com_users.user', 'com_users.registration', 'com_admin.profile')))
 		{
			return true;
		}
		
		
		if (is_object($data))
		{
			$userId = isset($data->id) ? $data->id : 0;
			
			if (!isset($data->swg_extras) and $userId > 0)
			{
				// Load the profile data from the database.
				$db = JFactory::getDbo();
				$db->setQuery(
					'SELECT profile_key, profile_value FROM #__user_profiles' .
					' WHERE user_id = '.(int) $userId." AND profile_key LIKE 'swg_extras.%'" .
					' ORDER BY ordering'
				);
				$results = $db->loadRowList();

				// Check for a database error.
				if ($db->getErrorNum())
				{
					$this->_subject->setError($db->getErrorMsg());
					return false;
				}

				// Merge the profile data.
				$data->swg_extras = array();
				
				foreach ($results as $v)
				{
					$k = str_replace('swg_extras.', '', $v[0]);
					$data->swg_extras[$k] = json_decode($v[1], true);
					if ($data->swg_extras[$k] === null)
					{
						$data->swg_extras[$k] = $v[1];
					}
				}
			}

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
		$name = $form->getName();
		if (!in_array($name, array('com_admin.profile', 'com_users.user', 'com_users.profile', 'com_users.registration')))
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
		
		if ($userId && $result && isset($data['swg_extras']) && (count($data['swg_extras'])))
		{
			try
			{
				//Sanitize the date
				if (!empty($data['swg_extras']['joindate']))
				{
					$date = new JDate($data['swg_extras']['joindate']);
					$data['swg_extras']['joindate'] = $date->format('Y-m-d');
				}

				$db = JFactory::getDbo();
				$db->setQuery(
					'DELETE FROM #__user_profiles WHERE user_id = '.$userId .
					" AND profile_key LIKE 'swg_extras.%'"
				);

				if (!$db->query())
				{
					throw new Exception($db->getErrorMsg());
				}

				$tuples = array();
				$order	= 1;

				foreach ($data['swg_extras'] as $k => $v)
				{
					$tuples[] = '('.$userId.', '.$db->quote('swg_extras.'.$k).', '.$db->quote(json_encode($v)).', '.$order++.')';
				}

				$db->setQuery('INSERT INTO #__user_profiles VALUES '.implode(', ', $tuples));
echo $db->getQuery();

				if (!$db->query())
				{
					throw new Exception($db->getErrorMsg());
				}

			}
			catch (JException $e)
			{
				$this->_subject->setError($e->getMessage());
				return false;
			}
		}
		
	}
}