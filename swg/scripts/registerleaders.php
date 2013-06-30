<?php

// Get the existing leaders (if active, and no existing Joomla user)
$db = JFactory::getDBO();
$query = $db->getQuery(true);
$query->select('*');
$query->from('walkleaders');
$query->where('active')->where('joomlauser IS NULL')->where("loginid <> ''")->where("Email <> ''");
$db->setQuery($query);
$leaders = $db->loadAssocList();
$created = 0;
$failed = 0;

foreach ($leaders as $leader)
{
	try
	{
		$user = new JUser();
		$userinfo = array(
			"email" => $leader['Email'],
			"name" => $leader['Forename']." ".$leader['Surname'],
			"password" => "badgerx3",
			"username" => $leader['loginid'],
			"swg_extras" => array("leaderid" => $leader['ID']),
		);
		// Get the default new user group, Registered if not specified.
		$userinfo['groups'] = array(1, 2, 13); // NOTE: Hardcoded - public, registered, leaders
		
		$user->bind($userinfo);
		
		if ($user->save())
		{
			$created++;
			
		}
		else
		{
			$failed++;
		}
	}
	catch (Exception $e)
	{
		echo $e->getTraceAsString();
	}
	
	foreach ($user->getErrors() as $error)
	{
		echo $leader['loginid'].": ".$error."<br />";
	}

}
printf("%d created, %d failed",$created,$failed);



die();