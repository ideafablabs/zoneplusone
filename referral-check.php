<?php

/// This is currently disabled in favor of the dashboard admin page. I believe the issue was how we were sending emails below.

// ---- GET EVENT TYPE ----
if(!isset($_GET["event_type"])) {
    // event type was not found, so exit
    exit;
} else {
    $eventType = $_GET["event_type"];
}

// ---- ACCESS DATA ----
// member data
$memberId = $_GET["member_id"];
$username = $_GET["username"];
$email = $_GET["email"];
$phone = $_GET["phone"];
$firstName = $_GET["first_name"];
$lastName = $_GET["last_name"];
$daysAsMember = $_GET["days_as_member"];

// custom field data
// You can access custom field data by accessing the get parameter cf_# where # is the
// ID of the custom field
//$exampleCustomData = $_GET["cf_1"];

$referredBy = $_GET["cf_4"];
$referralRedeemed = $_GET["cf_5"];

if ($daysAsMember >= 120 && strlen($referredBy) != 0 && $referralRedeemed != "mm_cb_on") {
    $to = 'jordan@ideafablabs.com';
    $subject = "$referredBy gets a bonus for referring member $firstName $lastName";
    $message = wordwrap("Member $firstName $lastName has now been a member for $daysAsMember days, and was referred by member $referredBy, who has not gotten a bonus yet. Send $referredBy a bonus, and edit the Referral Redeemed custom field for $firstName $lastName accordingly so you don't get this same email next month.", 70, "\r\n");
    $headers = 'From: sc@ideafablabs.com' . "\r\n" . 'Reply-To: sc@ideafablabs.com' . "\r\n" . 'X-Mailer: PHP/' . phpversion();
    //mail( $to, $subject, $message, $headers );
	wp_mail( $to, $subject, $message, $headers );
}

?>
