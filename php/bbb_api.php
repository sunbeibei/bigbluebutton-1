<?php
/*
Copyright 2010 Blindside Networks

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

Versions:
   1.0  --  Initial version written by DJP
                   (email: djp [a t ]  architectes DOT .org)
   1.1  --  Updated by Omar Shammas and Sebastian Schneider
                    (email : omar DOT shammas [a t ] g m ail DOT com)
                    (email : seb DOT sschneider [ a t ] g m ail DOT com)
*/

function bbb_wrap_simplexml_load_file($url){

		$ch = curl_init() or die ( curl_error() );
		$timeout = 10;
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		$data = curl_exec( $ch );
		curl_close( $ch );
		return (new SimpleXMLElement($data));
}

/*
@param
$userName = userName AND meetingID (string) 
$welcomeString = welcome message (string)
$modPW = moderator password (string)
$vPW = viewer password (string)
$voiceBridge = voice bridge (integer)
$logout = logout url (url)
*/
// create a meeting and return the url to join as moderator


// TODO::
// create some set methods 
class BigBlueButton {
	
	var $userName = array();
	var $meetingID; // the meeting id
	
	var $welcomeString;
	// the next 2 fields are maybe not needed?!?
	var $modPW; // the moderator password 
	var $attPW; // the attendee pw
	
	var $securitySalt; // the security salt; gets encrypted with sha1
	var $URL; // the url the bigbluebutton server is installed
	var $sessionURL; // the url for the administrator to join the sessoin
	var $userURL;
	
	var $conferenceIsRunning = false;
	// this constructor is used to create a BigBlueButton Object
	// use this object to create servers
	// Use is either 0 arguments or all 7 arguments
public function __construct() {


	$numargs = func_num_args();

	if( $numargs == 0 ) {
	#	echo "Constructor created";
	}
	// pass the information to the class variables
	else if( $numargs >= 6 ) {
		$this->userName = func_get_arg(0);
		$this->meetingID = func_get_arg(1);
		$this->welcomeString = func_get_arg(2);
		$this->modPW = func_get_arg(3);
		$this->attPW = func_get_arg(4);
		$this->securitySalt = func_get_arg(5);
		$this->URL = func_get_arg(6);
		

 		$arg_list = func_get_args();
#debug output for the number of arguments
#    	for ($i = 0; $i < $numargs; $i++) {
#        	echo "Argument $i is: " . $arg_list[$i] . "<br />\n";
#    	}
    
//    $this->createMeeting( $this->userName, $this->meetingID, $this->welcomeString, $this->modPW, $this->attPW, $this->securitySalt, $this->URL );
 //   echo "Object created";
//	 echo '<br />';
	}// end else if
}

public function checkCurl($url) {
	/*
 * Is libcurl available ?
 */
if(function_exists("curl_init()"))
{
	//function bbb_wrap_simplexml_load_file($url)
	//{
		$ch = curl_init() or die ( curl_error() );
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$data = curl_exec( $ch );
		curl_close( $ch );
		return (new SimpleXMLElement($data));
//	} 	
}
else
{
	/*
	 * REQUIREMENT - PHP.INI
	 * allow_url_fopen = On
	*/
	// function bbb_wrap_simplexml_load_file($url)
	// {
	 	return (simplexml_load_file($url));
	// }
}
}

public function createMeeting( $username, $meetingID, $welcomeString, $mPW, $aPW, $SALT, $URL, $logoutURL ) {
	$url_create = $URL."api/create?";
	$url_join = $URL."api/join?";
	$voiceBridge = 70000 + rand(0, 9999);

	$params = 'name='.urlencode($username).'&meetingID='.urlencode($meetingID).'&attendeePW='.$aPW.'&moderatorPW='.$mPW.'&voiceBridge='.$voiceBridge.'&logoutURL='.urlencode($logoutURL);

	if( trim( $welcomeString ) ) 
		$params .= '&welcome='.urlencode($welcomeString);

	$xml = bbb_wrap_simplexml_load_file($url_create.$params.'&checksum='.sha1("create".$params.$SALT) );
	
	if( $xml && $xml->returncode == 'SUCCESS' ) {
		$params = 'meetingID='.urlencode($meetingID).'&fullName='.urlencode($username).'&password='.$mPW.'&logoutURL='.urlencode($logoutURL);
		return ($url_join.$params.'&checksum='.sha1("join".$params.$SALT) );
	}	
	else if( $xml ) {
		return ( $xml->messageKey.' : '.$xml->message );
	}
	else {
		return ('Unable to fetch URL '.$url_create.$params.'&checksum='.sha1("create".$params.$SALT) );
	}
}

public function createMeetingArray( $username, $meetingID, $welcomeString, $mPW, $aPW, $SALT, $URL, $logoutURL ) {
	$url_create = $URL."api/create?";
	$url_join = $URL."api/join?";
	$voiceBridge = 70000 + rand(0, 9999);

	$params = 'name='.urlencode($meetingID).'&meetingID='.urlencode($meetingID).'&attendeePW='.$aPW.'&moderatorPW='.$mPW.'&voiceBridge='.$voiceBridge.'&logoutURL='.urlencode($logoutURL);

	if( trim( $welcomeString ) ) 
		$params .= '&welcome='.urlencode($welcomeString);

	$xml = bbb_wrap_simplexml_load_file($url_create.$params.'&checksum='.sha1("create".$params.$SALT) );
	
	if( $xml ) {
		return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey );
	}
	else {
		return null;
	}
}

// return the url to join a meeting as viewer
public function joinAsViewer( $meetingID, $userName, $welcomeString = '', $aPW, $SALT, $URL ) {
	$url_join = $URL."api/join?";
	$params = 'meetingID='.urlencode($meetingID).'&fullName='.urlencode($userName).'&password='.$aPW;
	return ($url_join.$params.'&checksum='.sha1("join".$params.$SALT) );
}

// getURLisMeetingRunning() -- return an URL that the client can use to poll for whether the given meeting is running
public function getUrlOfRunningMeeting( $meetingID, $URL, $SALT ) {
	$base_url = $URL."api/isMeetingRunning?";
	$params = 'meetingID='.urlencode($meetingID);
	return ($base_url.$params.'&checksum='.sha1("isMeetingRunning".$params.$SALT) );	
}

// isMeetingRunning() -- check the BigBlueButton server to see if the meeting is running (i.e. there is someone in the meeting)
public function isMeetingRunning( $meetingID, $URL, $SALT ) {
	$xml = bbb_wrap_simplexml_load_file( BigBlueButton::getUrlOfRunningMeeting( $meetingID, $URL, $SALT ) );
	if( $xml && $xml->returncode == 'SUCCESS' ) 
		return ( ( $xml->running == 'true' ) ? true : false);
	else
		return ( false );
}

// getURLMeetingInfo() -- return a URL to get MeetingInfo
public function getUrlFromMeetingInfo( $meetingID, $modPW, $URL, $SALT ) {
	$base_url = $URL."api/getMeetingInfo?";
	$params = 'meetingID='.urlencode($meetingID).'&password='.$modPW;
	return ( $base_url.$params.'&checksum='.sha1("getMeetingInfo".$params.$SALT));	
}

 // getMeetingInfo() -- Calls getMeetingInfo to obtain information on a given meeting.
public function getMeetingInfo( $meetingID, $modPW, $URL, $SALT ) {
	$xml = bbb_wrap_simplexml_load_file( BigBlueButton::getUrlFromMeetingInfo( $meetingID, $modPW, $URL, $SALT ) );
	return ( str_replace('</response>', '', str_replace("<?xml version=\"1.0\"?>\n<response>", '', $xml->asXML())));
}

public function getMeetingInfoArray( $meetingID, $modPW, $URL, $SALT ) {
	$xml = bbb_wrap_simplexml_load_file( BigBlueButton::getUrlFromMeetingInfo( $meetingID, $modPW, $URL, $SALT ) );
	if( $xml && $xml->returncode == 'SUCCESS' && $xml->messageKey == null){//The meetings were returned
		return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey );
	}
	else if($xml && $xml->returncode == 'SUCCESS'){ //If there were meetings already created
		return array( 'meetingID' => $xml->meetingID, 'moderatorPW' => $xml->moderatorPW, 'attendeePW' => $xml->attendeePW, 'hasBeenForciblyEnded' => $xml->hasBeenForciblyEnded, 'running' => $xml->running, 'startTime' => $xml->startTime, 'endTime' => $xml->endTime, 'participantCount' => $xml->participantCount, 'moderatorCount' => $xml->moderatorCount, 'attendees' => $xml->attendees );
	}
	else if( $xml ) { //If the xml packet returned failure it displays the message to the user
		return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
	}
	else { //If the server is unreachable, then prompts the user of the necessary action
		return null;
	}

}

 // getMeetingXML() --calls isMeetingRunning to obtain the xml values of the response of the returned URL
public function getMeetingXML( $meetingID, $URL, $SALT ) {
	$xml = bbb_wrap_simplexml_load_file( BigBlueButton::getUrlOfRunningMeeting( $meetingID, $URL, $SALT ) );
	if( $xml && $xml->returncode == 'SUCCESS') 
		return ( str_replace('</response>', '', str_replace("<?xml version=\"1.0\"?>\n<response>", '', $xml->asXML())));
	else
		return 'false';	
}

// getURLMeetings() -- return a URL for listing all running meetings
public function getUrlMeetings($URL, $SALT) { 
	$base_url = $URL."api/getMeetings?";
	$params = 'random='.(rand() * 1000 );
	return ( $base_url.$params.'&checksum='.sha1("getMeetings".$params.$SALT));
}

public function getUrlEndMeeting( $meetingID, $mPW, $URL, $SALT ) {
	$base_url = $URL."api/end?";
	$params = 'meetingID='.urlencode($meetingID).'&password='.$mPW;
	return ( $base_url.$params.'&checksum='.sha1("end".$params.$SALT) );
}

public function endMeeting( $meetingID, $mPW, $URL, $SALT ) {
	$xml = bbb_wrap_simplexml_load_file( BigBlueButton::getUrlEndMeeting( $meetingID, $mPW, $URL, $SALT ) );

        if( $xml ) { //If the xml packet returned failure it displays the message to the user
                return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
        }
        else { //If the server is unreachable, then prompts the user of the necessary action
                return null;
        }

}

// TODO: WRITE AN ITERATOR WHICH GOES OVER WHATEVER IT IS BEING TOLD IN THE API AND LIST INFORMATION
/* we have to define at least 2 variable fields for getInformation to read out information at any position
The first is: An identifier to chose if we look for attendees or the meetings or something else
The second is: An identifier to chose what integrated functions are supposed to be used

@param IDENTIFIER -- needs to be put in for the function to identify the information to print out
		     current values which can be used are 'attendee' and 'meetings'
@param meetingID -- needs to be put in to identify the meeting
@param modPW -- needs to be put in if the users are supposed to be shown or to retrieve information about the meetings
@param URL -- needs to be put in the URL to the bigbluebutton server
@param SALT -- needs to be put in for the security salt calculation

Note: If 'meetings' is used, then only the parameters URL and SALT needs to be used
      If 'attendee' is used, then all the parameters needs to be used
*/
public function getInformation( $IDENTIFIER, $meetingID, $modPW, $URL, $SALT ) {
	// if the identifier is null or '', then return false
	if( $IDENTIFIER == "" || $IDENTIFIER == null ) {
		echo "You need to type in a valid value into the identifier.";
		return false;
	}
	// if the identifier is attendee, call getUsers
	else if( $IDENTIFIER == 'attendee' ) {
		return BigBlueButton::getUsers( $meetingID, $modPW, $URL, $SALT );
	}
	// if the identifier is meetings, call getMeetings
	else if( $IDENTIFIER == 'meetings' ) {
		return BigBlueButton::getMeetings( $URL, $SALT );
	}
	// return nothing
	else {
		return true;
	}
	
}


// return the users in the current conference
/*
@param meetingID -- needs to be put in to identify the meeting
@param modPW -- needs to be put in if the users are supposed to be shown or to retrieve information about the meetings
@param URL -- needs to be put in the URL to the bigbluebutton server
@param SALT -- needs to be put in for the security salt calculation
@param UNAME -- if set to true, the parameter sets 'User name: ' in front of the username; the default value is false
*/

public function getUsers( $meetingID, $modPW, $URL, $SALT, $UNAME = false ) {
	$xml = bbb_wrap_simplexml_load_file( BigBlueButton::getUrlFromMeetingInfo( $meetingID, $modPW, $URL, $SALT ) );
	if( $xml && $xml->returncode == 'SUCCESS' ) {
		ob_start();
		if( count( $xml->attendees ) && count( $xml->attendees->attendee ) ) {
			foreach ( $xml->attendees->attendee as $attendee ) {
				if( $UNAME  == true ) {
					echo "User name: ".$attendee->fullName.'<br />';
				}
				else {
					echo $attendee->fullName.'<br />';
				}
			}
		}
		return (ob_end_flush());
	}
	else {
		return (false);
	}
}

public function getUsersArray( $meetingID, $modPW, $URL, $SALT ) {


        $xml = bbb_wrap_simplexml_load_file( BigBlueButton::getUrlFromMeetingInfo( $meetingID, $modPW, $URL, $SALT ) );

        if( $xml && $xml->returncode == 'SUCCESS' && $xml->messageKey == null ) {//The meetings were returned
                return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
        }
        else if($xml && $xml->returncode == 'SUCCESS'){ //If there were meetings already created

                foreach ($xml->attendees->attendee as $attendee)
                {
                        $users[] = array(  'userID' => $attendee->userID, 'fullName' => $attendee->fullName, 'role' => $attendee->role );
                }

                return $users;

        }
        else if( $xml ) { //If the xml packet returned failure it displays the message to the user
                return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
        }
        else { //If the server is unreachable, then prompts the user of the necessary action
                return null;
        }
}


// getMeetings() -- Calls getMeetings to obtain the list of meetings, then calls getMeetingInfo for each meeting and concatenates the result.
public function getMeetings( $URL, $SALT ) {
	$xml = bbb_wrap_simplexml_load_file( BigBlueButton::getUrlMeetings( $URL, $SALT ) );
	if( $xml && $xml->returncode == 'SUCCESS' ) {
		if( $xml->messageKey )
			return ( $xml->message->asXML() );	
		ob_start();
		echo '<meetings>';
		if( count( $xml->meetings ) && count( $xml->meetings->meeting ) ) {
			foreach ($xml->meetings->meeting as $meeting)
                        {
                                echo '<meeting>';
                                echo BigBlueButton::getMeetingInfo($meeting->meetingID, $meeting->moderatorPW, $URL, $SALT);
                                echo '</meeting>';
                        }
                }
       echo '</meetings>';
                return (ob_get_clean());
        }
        else {
                return (false);
        }
}


public function getMeetingsArray( $URL, $SALT ) {
        $xml = bbb_wrap_simplexml_load_file( BigBlueButton::getUrlMeetings( $URL, $SALT ) );

	if( $xml && $xml->returncode == 'SUCCESS' && $xml->messageKey ) {//The meetings were returned
		return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
	}
	else if($xml && $xml->returncode == 'SUCCESS'){ //If there were meetings already created
	
	        foreach ($xml->meetings->meeting as $meeting)
	        {
#		       $meetings[] = BigBlueButton::getMeetingInfo($meeting->meetingID, $meeting->moderatorPW, $URL, $SALT);
		        $meetings[] = array( 'meetingID' => $meeting->meetingID, 'moderatorPW' => $meeting->moderatorPW, 'attendeePW' => $meeting->attendeePW, 'hasBeenForciblyEnded' => $meeting->hasBeenForciblyEnded, 'running' => $meeting->running );
         	}

	        return $meetings;

	}
	else if( $xml ) { //If the xml packet returned failure it displays the message to the user
		return array('returncode' => $xml->returncode, 'message' => $xml->message, 'messageKey' => $xml->messageKey);
	}
	else { //If the server is unreachable, then prompts the user of the necessary action
		return null;
	}
}

function getServerIP() {
         // get the server url
         $sIP = $_SERVER['SERVER_ADDR'];
         return $serverIP = 'http://'.$sIP.'/bigbluebutton/';
}

}
?>
