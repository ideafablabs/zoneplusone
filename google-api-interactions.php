<?php
// Idea Fab Labs Google API Test

// === Google API Calls
$service_account = get_template_directory() . '/functions/groupManager.json';
putenv('GOOGLE_APPLICATION_CREDENTIALS='.$service_account);

//require_once get_template_directory() . '/functions/google-api-php-client/vendor/autoload.php';
require_once IFLZPO_PLUGIN_PATH . 'lib/google-api-php-client/vendor/autoload.php';

// This adds a members email from the Google Apps Member Email List and Calendar Group.
function ifl_member_email_update($data) {
	
	$scope = 'https://www.googleapis.com/auth/admin.directory.group https://www.googleapis.com/auth/calendar';
	$groupName = 'scmembers@ideafablabs.com';

	$client = getAPIClient($service_account,$scope);
	$directory = new Google_Service_Directory($client);
	$googleServiceCalendar = new Google_Service_Calendar($client);
	$iflScCalendarId = 'betaisfun.com_ocasjnpjbfkd6a0ta3k5ojtaho@group.calendar.google.com';

	// Get the email of the member.	
	$email = $data['email'];

	if ($data['status_name'] == "Canceled") {	
	  
		try {
			$delete = $directory->members->delete($groupName,$email);
		} catch (Exception $e) {
			/// Add logaction here.
			sendTestEmail("IFL SC Group Remove Failure",serialize($e));
		}

		if ($email != 'chico@ideafablabs.com') {
			try {
				deleteUserFromCalendar($email, $googleServiceCalendar, $iflScCalendarId);
			} catch (Exception $e) {
				/// Add logaction here.
				sendTestEmail("IFL SC Calendar Remove Failure",serialize($e));
			}
		}

	} 

	// if ($data['status_name'] == "Paused") {}

	if ($data['status_name'] == "Active") {					

		$newMember = new Google_Service_Directory_Member();
		$newMember->setEmail($email);


		// Check if member is already on list...
		try {
			$list = $directory->members->listMembers($groupName);
		} catch (Exception $e) {
			/// add a logaction here.
		}
		
		$user_array = $list['modelData']['members'];

		$exists = false;
	 	foreach($user_array as $user) {  
	 		// echo $user['email'] . "<br />\n";
	        if (in_array($email,$user)) {$exists = true; break;}
	 	}

		// Add email to the directory group.
		if (!$exists) {
			try{
				$insert = $directory->members->insert($groupName,$newMember);
			} catch (Exception $e) {
				sendTestEmail("IFL SC Group Add Failure",serialize($e));	
			}
		}

		// Add email to calendar group.
		if ($email != 'chico@ideafablabs.com') {
			try {
				addUserToCalendar($email, $googleServiceCalendar, $iflScCalendarId);
			} catch (Exception $e) {
				sendTestEmail("IFL SC Calendar Add Failure",serialize($e));
			}
		}
	}

	return true;

}

function sendTestEmail($subject,$body)
{
	$to = 'webmaster@ideafablabs.com';		
	$headers = array('Content-Type: text/html; charset=UTF-8'); 
	wp_mail( $to, $subject, $body, $headers );
}

// Remove members from a group.
/// Needs Exponential Backoff implemented.
function clearDirectoryGroup($groupName) {
	$scope = 'https://www.googleapis.com/auth/admin.directory.group';
	$client = getAPIClient($service_account,$scope);
	
	$directory = new Google_Service_Directory($client);

	$list = $directory->members->listMembers($groupName);

 	$user_array = $list['modelData']['members']; 	

 	foreach($user_array as $user) {  
 		echo $user['email'] . "<br />\n";
 	}

 	$client->setUseBatch(true);
	$batch = new Google\Http_Batch($client);


	foreach($user_array as $user) {           
		// $userdata = new Google_Service_Directory_Member();
        // $userdata->setEmail($user);
  //       $userdata->setRole('MEMBER');
  //       $userdata->setType('USER');
        // $batch->add($service->members->insert($temp_list_name, $userdata));
        
        $batch->add($directory->members->delete($groupName, $user['email']));
	}
	// pr($batch);
	$result = $batch->execute();
	pr($result);
}

function addUserToCalendar($userEmailToInsert, $calendar, $calendarId) {
    // no error if you re-add someone who already exists, but if they do already exist and you add them
    // with a different role, their previous role will be overwritten
    $userRole = "writer";

    $aclScope = new Google_Service_Calendar_AclRuleScope();
    $aclScope->setType("user");
    $aclScope->setValue($userEmailToInsert);

    $rule = new Google_Service_Calendar_AclRule();
    $rule->setRole($userRole);
    $rule->setScope($aclScope);

    $result = $calendar->acl->insert($calendarId, $rule);
}

function deleteUserFromCalendar($userEmailToDelete, $calendar, $calendarId) {
    // you don't have to check whether the user exists, because there's no error if they don't
    $result = $calendar->acl->delete($calendarId, "user:" . $userEmailToDelete);
}

function listAllCalendarUsers($calendar, $calendarId) {
    // print all access control rules = email addresses and roles
    $calUsers = array();
    $acl = $calendar->acl->listAcl($calendarId);

    while (true) {
        foreach ($acl->getItems() as $rule) {
            echo $rule->getId() . ': ' . $rule->getRole() . "<br />\n";
            array_push($calUsers, $rule);
        }
        $pageToken = $acl->getNextPageToken();
        if ($pageToken) {
            $optParams = array('pageToken' => $pageToken);
            $acl = $calendar->acl->listAcl($calendarId, $optParams);
        } else {
            break;
        }
    }
    return $calUsers;
}

// Gets Google API Service Account Credentials.
function getAPIClient($service_account,$scope) {

	$client = new Google_Client();
	$client->setApplicationName("Service Manager");
	$client->useApplicationDefaultCredentials();

	// $client->addScope(Google_Service_Directory_Groups);		
	$client->addScope($scope);
	$client->setAuthConfig($service_account);
	$client->setSubject('chico@ideafablabs.com'); /// authorize sc@ 

	return $client;
}

// When a member is added, trigger setup calls.
// http://support.membermouse.com/customer/portal/articles/1045702-membermouse-wordpress-hooks
// function memberAdded($data)  
// {
//     // perform action 
// }
add_action('mm_member_status_change', 'ifl_member_email_update');
add_action('mm_member_add', 'ifl_member_email_update');




?>