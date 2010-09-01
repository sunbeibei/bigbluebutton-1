<?php
/* 
Plugin Name: BigBlueButton
Plugin URI: http://blindsidenetworks.com/integration
Version: 1.0.0
Author: Blindside Networks
Author URI: http://blindsidenetworks.com/
Description: Integrates BigBlueButton into a WordPress site

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
   1.0 --  Initial version written by Omar Shammass
                    (email : omar DOT shammas [a t ] g m ail DOT com)
*/

//================================================================================
//------------------Required Libraries and Global Variables-----------------------
//================================================================================
require('php/bbb_api.php');

global $bbb_db_version;

$url_name = 'mt_bbb_url';
$salt_name = 'mt_salt';
$waitForModerator_name = 'mt_waitForModerator';

$meetingVersion_name = 'meetingVersion'; //The name that is used to save the meeting in the bbb server
$meetingID_name = 'meetingID';
$attendeePW_name = 'attendeePW';
$moderatorPW_name = 'moderatorPW';

//================================================================================
//-------------------------BigBlueButtonPlugin Class------------------------------
//================================================================================
if (!class_exists("bigbluebuttonPlugin")) {
	class bigbluebuttonPlugin {
		function bigbluebuttonPlugin() { //constructor
			
		}
		
		//Inserts the plugin pages in the admin panel
		function mt_add_pages() {

			//Add a new submenu under Settings
			$page = add_options_page(__('BigBlueButton','menu-test'), __('BigBlueButton','menu-test'), 'manage_options', 'bbb_general', 'bbb_general_options');

 			//Attaches the plugin's stylesheet to the plugin page just created
			add_action('admin_print_styles-' . $page, 'bbb_admin_styles');

 		}
		
		//Registers the plugin's stylesheet
		function bbb_admin_init() {
			wp_register_style('bbbStylesheet', WP_PLUGIN_URL . '/bigbluebutton/css/bbb_stylesheet.css');
		}
		
		//Registers the bigbluebutton widget
		function widget_bigbluebutton_init(){
		  register_sidebar_widget(__('BigBlueButton'), 'bigbluebutton_sidebar');
		}
		
		//Sets up the bigbluebutton table to store meetings in the wordpress database
		function bbb_db_install () {
			
			global $wpdb;
			global $bbb_db_version;
			$bbb_db_version = "1.0";
			
			//Sets the name of the table
			$table_name = $wpdb->prefix . "bbb_meetingRooms";
			
			if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
				//Checks to see if the versions of the database match, if not then updates the tables
				//to reflect the chances
				$installed_ver = get_option("bbb_db_version");
				if( $installed_ver != $bbb_db_version ) {
					$sql = "CREATE TABLE " . $table_name . " (
					id mediumint(9) NOT NULL AUTO_INCREMENT,
					meetingID text NOT NULL,
					meetingVersion int NOT NULL,
					attendeePW text NOT NULL,
					moderatorPW text NOT NULL,
					UNIQUE KEY id (id)
					);";

					require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
					dbDelta($sql);

					update_option( "bbb_db_version", $bbb_db_version );
				}
			}
		}
			
			//Sets up the bigbluebutton table to store meetings in the wordpress database
		function bbb_db_uninstall () {
			global $wpdb;

			//Delete the options stored in the wordpress db
			delete_option('bbb_db_version');
			delete_option('mt_bbb_url');
			delete_option('mt_salt');
			delete_option('mt_waitForModerator');

			//Sets the name of the table
			$table_name = $wpdb->prefix . "bbb_meetingRooms";
			$wpdb->query("DROP TABLE IF EXISTS $table_name");
		}
		 		
	}//End Class bigbluebuttonPlugin
} 


//================================================================================
//------------------------------------Main----------------------------------------
//================================================================================
if (class_exists("bigbluebuttonPlugin")) {
	$bbb_plugin = new bigbluebuttonPlugin();
}

if (isset($bbb_plugin)) {

	add_action('admin_menu', array(&$bbb_plugin, 'mt_add_pages'), 1);
	
	add_action('admin_init', array(&$bbb_plugin, 'bbb_admin_init'), 1);
	
	add_action("plugins_loaded", array(&$bbb_plugin, 'widget_bigbluebutton_init') ); //Registers the widget
	
	register_activation_hook(__FILE__, array(&$bbb_plugin, 'bbb_db_install') ); //Sets up the databse
	
	register_deactivation_hook(__FILE__, array(&$bbb_plugin, 'bbb_db_uninstall') );//Deletes the database and options
	
	set_error_handler("bbb_warning_handler", E_WARNING);

}


//Adds the plugin stylesheet to wordpress
function bbb_admin_styles(){
	wp_enqueue_style('bbbStylesheet');
}


//================================================================================
//------------------------------Error Handler-------------------------------------
//================================================================================
function bbb_warning_handler($errno, $errstr) {
	//Do Nothing
}

//================================================================================
//---------------------------------Widget-----------------------------------------
//================================================================================
//Inserts a bigbluebutton widget on the siderbar of the blog
function bigbluebutton_sidebar($args) {
	session_start();
	extract($args);
	echo $before_widget;
	echo $before_title;?>BigBlueButton<?php echo $after_title;

	global $wpdb, $url_name, $salt_name, $waitForModerator_name, $meetingID_name, $meetingVersion_name, $attendeePW_name, $moderatorPW_name;
	
	//Read in existing option value from database
	$url_val = get_option($url_name);
	$salt_val = get_option($salt_name);
	
	//Gets all the meetings from wordpress database
	$table_name = $wpdb->prefix . "bbb_meetingRooms";
	$listOfMeetings = $wpdb->get_results("SELECT meetingID, meetingVersion, moderatorPW FROM ".$table_name." ORDER BY meetingID");
		
			
	$dataSubmitted = false;
	$validMeeting = false;
	$meetingExist = false;
	if( isset($_POST['Submit']) && $_POST['Submit'] == 'Join' ) { //The user has submitted his login information
		$dataSubmitted = true;
		$meetingExist = true;
		
		//Read posted values
		$name = $_POST['display_name'];
		$password = $_POST['pwd'];
		$meetingID = $_POST[$meetingID_name];
		
		$found = $wpdb->get_row("SELECT * FROM ".$table_name." WHERE meetingID = '".$meetingID."'");
		if($found->meetingID == $meetingID && ($found->moderatorPW == $password || $found->attendeePW == $password) ){
			$info = BigBlueButton::getMeetingInfoArray( $found->meetingID."[".$found->meetingVersion."]", $found->moderatorPW, $url_val, $salt_val);
			//Analyzes the bigbluebutton server's response
			if(!$info || $info['returncode'] && $info['messageKey'] != 'notFound' && $info['messageKey'] != 'invalidPassword'){ //An error occured so display to the user no meetings are available
				echo "Sorry an error occured while joining the meeting.";
				echo $after_widget;
				return;
			}
			else if( $info['returncode'] && $info['messageKey'] == 'notFound'){	//The meeting exists only in the wordpress db
				
				if (bbb_widget_join_meeting($found, $name, $password, $salt_val, $url_val) ) return;
				
			} 
			else if($info['returncode'] && $info['messageKey'] == 'invalidPassword' || $info['hasBeenForciblyEnded']=='true' ){
				//Finds a version number that has not been used for that meetingID. And creates and joins that meeting
				//This eliminates the waiting for 1 hr to rejoin a meeting
				$originalVersion = $found->meetingVersion;
				$wpdb->update( $table_name, array( $meetingVersion_name => $found->meetingVersion + 1), array( $meetingID_name => $found->meetingID ));
				$found->meetingVersion = $found->meetingVersion + 1;
				for(;;){
					$response = BigBlueButton::getMeetingInfoArray( $found->meetingID."[".$found->meetingVersion."]", $found->moderatorPW, $url_val, $salt_val);
					//Analyzes the bigbluebutton server's response
					if(!$response || $response['returncode'] && $response['messageKey'] != 'notFound' && $response['messageKey'] != 'invalidPassword' ) { //If the meeting was unable to be retrieved due to an error
						echo "Sorry an error occured while joining the meeting.";
						echo $after_widget;
						return;
					}
					else if( $response['returncode'] && $response['messageKey'] == 'notFound'){ //The meeting does not exist in the bbb server, so create it	
						break;
					}
					else if($response['returncode'] && $response['messageKey'] == 'invalidPassword' || $response['hasBeenForciblyEnded'] == true ){
						if($found->meetingVersion >= $originalVersion + 3){
							$wpdb->update( $table_name, array( $meetingVersion_name => $found->meetingVersion + 5), array( $meetingID_name => $found->meetingID ));
							$found->meetingVersion = $found->meetingVersion  + 5;
							$originalVersion = $found->meetingVersion;
						}
						else {
							$wpdb->update( $table_name, array( $meetingVersion_name => $found->meetingVersion + 1), array( $meetingID_name => $found->meetingID ));
							$found->meetingVersion = $found->meetingVersion + 1;
						}
					}
				}
				//creates the meeting and then joins it
				if (bbb_widget_join_meeting($found, $name, $password, $salt_val, $url_val) ) return;
			}
			else{ //The meeting exists in the bbb server
					
				//The meeting the user is trying to join is valid, meaning it has not been forcibly ended and therefore is
				//within the 1 hr period of unavailability.
				if($info['hasBeenForciblyEnded']=='false' ){ 
					
					$bbb_joinURL = BigBlueButton::joinAsViewer($found->meetingID."[".$found->meetingVersion."]", $name,'', $password, $salt_val, $url_val );
					//If the meeting is already running or the moderator is trying to join or a viewer is trying to join and the
					//do not wait for moderator option is set to false then the user is immediately redirected to the meeting
					if ( (BigBlueButton::isMeetingRunning( $found->meetingID."[".$found->meetingVersion."]", $url_val, $salt_val ) && ($found->moderatorPW == $password || $found->attendeePW == $password ) )
						|| $info['moderatorPW'] == $password 
						|| ($info['attendeePW'] == $password && get_option($waitForModerator_name) != 'yes' ) ){
							//If the password submitted is correct then the user gets redirected
							?><script type="text/javascript"> window.location = "<?php echo $bbb_joinURL ?>";</script><?php
							return;
					}
					//If the viewer has the correct password, but the meeting has not yet started they have to wait
					//for the moderator to start the meeting
					else if ($found->attendeePW == $password){
						//Stores the url and salt of the bigblubutton server in the session
						$_SESSION[$url_name] = $url_val;
						$_SESSION[$salt_name] = $salt_val;
						//Displays the javascript to automatically redirect the user when the meeting begins
						bbb_display_redirect_script($bbb_joinURL, $found->meetingID, $found->meetingID."[".$found->meetingVersion."]", $name);
						return;
					}
				}
			}
		}
	}
			

	//Displays the meetings in the wordpress database. 
	foreach ($listOfMeetings as $meeting) {		
		$validMeeting = true;
		break;
	}
	
	//If a valid meeting was found the login form is displayed
	if($validMeeting){
		//Alerts the user if the password they entered does not match
		//the meeting's password
		if($dataSubmitted && !$meetingExist){
				echo "***".$meetingID." no longer exists.***";
		}
		else if($dataSubmitted){
				echo "***Incorrect Password***";
		}
		?>
			<form name="form1" method="post" action="">
				<table>
					<tr>
						<td>Meeting</td>
						<td>
							<select name="<?php echo $meetingID_name; ?>">
								<?php
								foreach ($listOfMeetings as $meeting) {
									echo "<option>".$meeting->meetingID."</option>";
								}
								?>
							</select>
					</tr>
					<tr>
						<td>Name</td>
						<td><INPUT type="text" id="name" name="display_name" size="10"></td>
					</tr>
					<tr>
						<td>Password</td>
						<td><INPUT type="password" name="pwd" size="10"></td>
					</tr>
				</table>
				<INPUT type="submit" name="Submit" value="Join">
			</form>		
		<?php
	}
	else if($dataSubmitted){
		//Alerts the user if the password they entered does not match
		//the meeting's password
		echo "***".$meetingID." no longer exists.***<br />";
		echo "No meeting rooms are currently available to join.";
	}
	else{
		echo "No meeting rooms are currently available to join.";
	}
	
	echo $after_widget;
}

//The meeting that is being joined only exists in the wordpress database, or the meeting being joined was forcibly ended and is unjoinable
//Therefore a meeting is created with the version specified, and then joined.
function bbb_widget_join_meeting($found, $name, $password, $salt_val, $url_val){
	
	global $wpdb, $waitForModerator_name, $meetingID_name, $meetingVersion_name, $attendeePW_name, $moderatorPW_name, $salt_name, $url_name;
		
	if ( $found->moderatorPW == $password || $found->attendeePW == $password ){
		
		//Calls creates the meeting on the bigbluebutton server
		$response = BigBlueButton::createMeetingArray($name, $found->meetingID."[".$found->meetingVersion."]", '', $found->moderatorPW, $found->attendeePW, $salt_val, $url_val, get_option('siteurl'));
		
		//Analyzes the bigbluebutton server's response
		if(!$response || $response['returncode'] == 'FAILED' ) { //The meeting was not created
			echo "Sorry an error occured while joining the meeting.";
			echo $after_widget;
		}
		else{ //The meeting was created, and the user will now be joined
			$bbb_joinURL = BigBlueButton::joinAsViewer($found->meetingID."[".$found->meetingVersion."]", $name,'', $password, $salt_val, $url_val );
			
			//If the meeting is already running then join immediately
			//Check the waitformoderator option. If it states not to wait for moderator then join immediately
			//Otherwise, wait till the meeting has been started by a moderator before joining
			if( get_option($waitForModerator_name) != 'yes' || $found->moderatorPW == $password || BigBlueButton::isMeetingRunning( $found->meetingID."[".$found->meetingVersion."]", $url_val, $salt_val ) ){
				?><script type="text/javascript"> window.location = "<?php echo $bbb_joinURL ?>";</script><?php
			}
			else{
				
				//Stores the url and salt of the bigblubutton server in the session
				$_SESSION[$url_name] = $url_val;
				$_SESSION[$salt_name] = $salt_val;
				
				bbb_display_redirect_script($bbb_joinURL, $found->meetingID, $found->meetingID."[".$found->meetingVersion."]", $name);
			}
		}
		return true;
	}
	return false;
}

//Displays the javascript that handles redirecting a user, when the meeting has started
//the meetingName is the meetingID[$meetingVersion]
function bbb_display_redirect_script($bbb_joinURL, $meetingID, $meetingName, $name){

	?>
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
		<script type="text/javascript" src="<?php echo './wp-content/plugins/bigbluebutton/js/heartbeat.js'; ?>"></script>
		<script type="text/javascript" src="<?php echo './wp-content/plugins/bigbluebutton/js/md5.js'; ?>"></script>
		<script type="text/javascript" src="<?php echo './wp-content/plugins/bigbluebutton/js/jquery.xml2json.js'; ?>"></script>
		<script type="text/javascript">
			$(document).ready(function(){
				$.jheartbeat.set({
					url: './wp-content/plugins/bigbluebutton/php/check.php?meetingID=<?php echo urlencode($meetingName); ?>',
					delay: 5000
				}, function () {
				mycallback();
				});
			});


			function mycallback() {
				// Not elegant, but works around a bug in IE8
				var isMeetingRunning = ($("#HeartBeatDIV").text().search("true") > 0 );

				if (isMeetingRunning) {
					window.location = "<?php echo $bbb_joinURL; ?>";
				}
			}
		</script>

		<table>
			<tbody>
				<tr>
					<td>
						Hi <?php echo $name; ?>,
						<br />
						<br />
						Now waiting for the moderator to start <?php echo $meetingID; ?>.
						<br />
						<center><img align="center" src="<?php echo './wp-content/plugins/bigbluebutton/images/polling.gif'; ?>" /></center>
						<br />
						(Your browser will automatically refresh and join the meeting when it starts.)
					</td>
				</tr>
			</tbody>
		</table>
	<?php
	return;
}


//================================================================================
//---------------------------------BBB Page--------------------------------------
//================================================================================		
//The main page where the user specifies the url of the bbb server and its salt
function bbb_general_options() {

	//Checks to see if the user has the sufficient persmissions and capabilities
    if (!current_user_can('manage_options'))
    {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }

	/* If the bigbluebutton server url and salt are empty then it does not
	display the create meetings, and list meetings sections.*/
    if (bbb_general_settings()){
	
		bbb_create_meetings();

		bbb_list_meetings();
	}

}

//================================================================================
//------------------------------General Settings----------------------------------
//================================================================================		
// The page allows the user specifies the url of the bbb server and its salt
function bbb_general_settings() {

	// Read in existing option value from database
	global $url_name, $salt_name, $waitForModerator_name;
	
	//Displays the title of the page
    echo '<div class="wrap">';
    echo "<h2>BigBlueButton Settings</h2>";
	
	$url_val = get_option($url_name);
	$salt_val = get_option($salt_name);
	$waitForModerator_val = get_option($waitForModerator_name);

	//Obtains the meeting information of the meeting that is going to be terminated
    if( isset($_POST['Submit']) && $_POST['Submit'] == 'Save Changes') {
       
	    //Reads their posted value
        $url_val = $_POST[ $url_name ];
		$salt_val = $_POST[ $salt_name ];
		$waitForModerator_val = $_POST[ $waitForModerator_name ];

		//
		if(strripos($url_val, "/bigbluebutton/") == false){
			if(substr($url_val, -1) == "/"){
				$url_val .= "bigbluebutton/";
			}
			else{
				$url_val .= "/bigbluebutton/";
			}
		}
			
        // Save the posted value in the database
        update_option($url_name, $url_val );
		update_option($salt_name, $salt_val );
		if($waitForModerator_val == 'yes') update_option($waitForModerator_name, 'yes' );
		else update_option($waitForModerator_name, 'no' );

        // Put an settings updated message on the screen
		echo '<div class="updated"><p><strong>Settings saved.</strong></p></div>';

    }

    //Form to update the url of the bigbluebutton server, and it`s salt
    ?>

	<form name="form1" method="post" action="">
		<p>URL of BigBlueButton server:<input type="text" name="<?php echo $url_name; ?>" value="<?php echo $url_val; ?>" size="40"> eg. 'http://example.com/bigbluebutton/'
		</p>		
		<p>Salt of BigBlueButton server:<input type="text" name="<?php echo $salt_name; ?>" value="<?php echo $salt_val; ?>" size="40"> Can be found in /var/lib/tomcat6/webapps/bigbluebutton/WEB-INF/classes/bigbluebutton.properties
		</p>
		<p>Wait for moderator to start meetings:<input type="checkbox" name="<?php echo $waitForModerator_name; ?>" value="yes" <?php if($waitForModerator_val == 'yes' ) echo 'checked="yes"';?>"/>
		</p>
		
		<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="<?php echo 'Save Changes'; ?>" />
		</p>

	</form>
	<hr />

	<?php
	
	//Checks to see if the url and salt are empty. If they are then 
	//the create meetings, and list meetings sections are not displayed
	if($url_val == '' || $salt_val == ''){
		echo '</div>';
		return false;
	}
	
	return true;

}

//================================================================================
//---------------------------------List Meetings----------------------------------
//================================================================================		
// Displays all the meetings available in the bigbluebutton server
function bbb_list_meetings() {
	global $wpdb;
	$table_name = $wpdb->prefix . "bbb_meetingRooms";
	global $url_name, $salt_name, $meetingID_name, $meetingVersion_name, $moderatorPW_name, $current_user, $attendeePW_name;
	
	//Displays the title of the page
    echo "<h2>List of Meeting Rooms</h2>";
	
	$url_val = get_option($url_name);
	$salt_val = get_option($salt_name);
	
	//---------------------------------------------------JOIN-----------------------------------------------
	if( isset($_POST['Submit']) && $_POST['Submit'] == 'Join' ) { //Creates then joins the meeting. If any problems occur the error is displayed
		// Read the posted value and delete
        $meetingID = $_POST[$meetingID_name];
		$meetingVersion = $_POST[$meetingVersion_name];
		$moderatorPW = $_POST[$moderatorPW_name];
		$attendeePW = $_POST[$attendeePW_name];
		
		$originalVersion = $meetingVersion;
		for(;;){
			$response = BigBlueButton::getMeetingInfoArray( $meetingID."[".$meetingVersion."]", $moderatorPW, $url_val, $salt_val);
			//Analyzes the bigbluebutton server's response
			if(!$response){//If the server is unreachable, then prompts the user of the necessary action
				echo '<div class="updated"><p><strong>Unable to join the meeting. Please check the url of the bigbluebutton server AND check to see if the bigbluebutton server is running.</strong></p></div>';
				return;
			}
			else if( $response['returncode'] && $response['messageKey'] != 'notFound' && $response['messageKey'] != 'invalidPassword') {
				if($response['messageKey'] == 'checksumError'){
					echo '<div class="updated"><p><strong>A checksum error occured. Make sure you entered the correct salt.</strong></p></div>';
				}
				else{
					echo '<div class="updated"><p><strong>'.$response['message'].'</strong></p></div>';
				}
				return;
			}
			else if( $response['returncode'] && $response['messageKey'] == 'notFound'){			//The meeting does not exist in the bbb server, so create it	
				break;
			}
			else if( ($response['returncode'] && $response['messageKey'] == 'invalidPassword') || $response['hasBeenForciblyEnded'] == 'true'){
				if((int)$meetingVersion >= (int)$originalVersion + 3){
					$wpdb->update( $table_name, array( $meetingVersion_name => (int)$meetingVersion + 5), array( $meetingID_name => $meetingID ));
					$meetingVersion = (string)((int)$meetingVersion + 5);
					$originalVersion = $meetingVersion;
				}
				else {
					$wpdb->update( $table_name, array( $meetingVersion_name => (int)$meetingVersion + 1), array( $meetingID_name => $meetingID ));
					$meetingVersion = (string)((int)$meetingVersion + 1);
				}
			}
			else{
				break;
			}
		}
		
		//Calls endMeeting on the bigbluebutton server
		$response = BigBlueButton::createMeetingArray($current_user->display_name, $meetingID."[".$meetingVersion."]", '', $moderatorPW, $attendeePW, $salt_val, $url_val, get_option('siteurl') );
		
		//Analyzes the bigbluebutton server's response
		if(!$response){//If the server is unreachable, then prompts the user of the necessary action
			echo '<div class="updated"><p><strong>Unable to join the meeting. Please check the url of the bigbluebutton server AND check to see if the bigbluebutton server is running.</strong></p></div>';
		}
		else if( $response['returncode'] == 'FAILED' ) { //The meeting was not created
			if($response['messageKey'] == 'checksumError'){
				echo '<div class="updated"><p><strong>A checksum error occured. Make sure you entered the correct salt.</strong></p></div>';
			}
			else{
				echo '<div class="updated"><p><strong>'.$response['message'].'</strong></p></div>';
			}
		}
		else{ //The meeting was created, and the user will now be joined
			$bbb_joinURL = BigBlueButton::joinAsViewer($meetingID."[".$meetingVersion."]", $current_user->display_name,'', $moderatorPW, $salt_val, $url_val );
			?><script type="text/javascript"> window.location = "<?php echo $bbb_joinURL ?>";</script><?php
			return;
		}
		
    }
	//---------------------------------------------------END-------------------------------------------------
	else if( isset($_POST['Submit']) && $_POST['Submit'] == 'End' ) { //Obtains the meeting information of the meeting that is going to be terminated
		// Read the posted value and delete
        $meetingID = $_POST[$meetingID_name];
		$meetingVersion = $_POST[$meetingVersion_name];
		$moderatorPW = $_POST[$moderatorPW_name];
		
		//Calls endMeeting on the bigbluebutton server
		$response = BigBlueButton::endMeeting($meetingID."[".$meetingVersion."]", $moderatorPW, $url_val, $salt_val );
			
		//Analyzes the bigbluebutton server's response
		if(!$response){//If the server is unreachable, then prompts the user of the necessary action
			echo '<div class="updated"><p><strong>Unable to terminate the meeting. Please check the url of the bigbluebutton server AND check to see if the bigbluebutton server is running.</strong></p></div>';
		}
		else if( $response['returncode'] == 'SUCCESS' ) { //The meeting was terminated
			echo '<div class="updated"><p><strong>'.$meetingID.' meeting has been terminated.</strong></p></div>';
		}
		else{ //If the meeting was unable to be deleted due to an error
			if($response['messageKey'] == 'checksumError'){
				echo '<div class="updated"><p><strong>A checksum error occured. Make sure you entered the correct salt.</strong></p></div>';
			}
			else{
				echo '<div class="updated"><p><strong>'.$response['message'].'</strong></p></div>';
			}
		}
		
    }
		//---------------------------------------------------DELETE-------------------------------------------------
	else if( isset($_POST['Submit']) && $_POST['Submit'] == 'Delete' ) { //Obtains the meeting information of the meeting that is going to be delete
		// Read the posted value and delete
        $meetingID = $_POST[$meetingID_name];
		$moderatorPW = $_POST[$moderatorPW_name];
		$meetingVersion = $_POST[$meetingVersion_name];
		
		//Calls endMeeting on the bigbluebutton server
		$response = BigBlueButton::endMeeting($meetingID."[".$meetingVersion."]", $moderatorPW, $url_val, $salt_val );
			
		//Analyzes the bigbluebutton server's response
		if(!$response){//If the server is unreachable, then prompts the user of the necessary action
			echo '<div class="updated"><p><strong>Unable to delete the meeting. Please check the url of the bigbluebutton server AND check to see if the bigbluebutton server is running.</strong></p></div>';
		}
		else if( $response['returncode'] != 'SUCCESS' && $response['messageKey'] != 'notFound' ) { //If the meeting was unable to be deleted due to an error
			if($response['messageKey'] == 'checksumError'){
				echo '<div class="updated"><p><strong>A checksum error occured. Make sure you entered the correct salt.</strong></p></div>';
			}
			else{
				echo '<div class="updated"><p><strong>'.$response['message'].'</strong></p></div>';
			}
		}
		else { //The meeting was terminated
			$wpdb->query("DELETE FROM ".$table_name." WHERE meetingID = '".$meetingID."'");
			echo '<div class="updated"><p><strong>'.$meetingID.' meeting has been deleted.</strong></p></div>';
		}
		
    }

	
	//Gets all the meetings from the wordpress db
	$listOfMeetings = $wpdb->get_results("SELECT meetingID, meetingVersion, attendeePW, moderatorPW FROM ".$table_name." ORDER BY meetingID");
	
	//Checks to see if there are no meetings in the wordpress db and if so alerts the user
	if(count($listOfMeetings) == 0){
			echo '<div class="updated"><p><strong>There are no meeting rooms.</strong></p></div>';
			return;
	}
	
	//Iinitiallizes the table 
	$printed = false;
	//Displays the meetings in the wordpress database that have not been created yet. Avoids displaying 
	//duplicate meetings, meaning if the same meeting already exists in the bbb server then it is 
	//not displayed again in this for loop
	foreach ($listOfMeetings as $meeting) {			

		$info = BigBlueButton::getMeetingInfoArray( $meeting->meetingID."[".$meeting->meetingVersion."]", $meeting->moderatorPW, $url_val, $salt_val);
		//Analyzes the bigbluebutton server's response
		if(!$info){//If the server is unreachable, then prompts the user of the necessary action
			echo '<div class="updated"><p><strong>Unable to display the meetings. Please check the url of the bigbluebutton server AND check to see if the bigbluebutton server is running.</strong></p></div>';
			return;
		}
		else if( $info['returncode'] && $info['messageKey'] != 'notFound' && $info['messageKey'] != 'invalidPassword') { //If the meeting was unable to be deleted due to an error
			if($info['messageKey'] == 'checksumError'){
				echo '<div class="updated"><p><strong>A checksum error occured. Make sure you entered the correct salt.</strong></p></div>';
			}
			else{
				echo '<div class="updated"><p><strong>'.$info['message'].'</strong></p></div>';
			}
			return;
		}
		else if( $info['returncode'] && ($info['messageKey'] == 'notFound' || $info['messageKey'] != 'invalidPassword') ){			//The meeting exists only in the wordpress db
			if(!$printed){
				bbb_print_table_header();
				$printed = true;
			}
			
			?>
			<form name="form1" method="post" action="">
				<input type="hidden" name="<?php echo $meetingID_name; ?>" value="<?php echo $meeting->meetingID; ?>">
				<input type="hidden" name="<?php echo $meetingVersion_name; ?>" value="<?php echo $meeting->meetingVersion; ?>">
				<input type="hidden" name="<?php echo $moderatorPW_name; ?>" value="<?php echo $meeting->moderatorPW; ?>">
				<input type="hidden" name="<?php echo $attendeePW_name; ?>" value="<?php echo $meeting->attendeePW; ?>">
				<tr>
					<td><?php echo $meeting->meetingID; ?></td>
					<td><?php echo $meeting->attendeePW; ?></td>
					<td><?php echo $meeting->moderatorPW; ?></td>
					<td>No</td>
					<td>
						<input type="submit" name="Submit" class="button-primary" value="Join" />
						<input type="submit" name="Submit" class="button-primary" value="Delete"/>
					</td>
				</tr>
			</form>
			<?php		
		}
		else{//The meeting exists in the bbb server
		
			if(!$printed){
				bbb_print_table_header();
				$printed = true;
			}
			
			$bbb_joinURL = BigBlueButton::joinAsViewer($meeting->meetingID."[".$meeting->meetingVersion."]", $current_user->display_name,'', $meeting->moderatorPW, $salt_val, $url_val );
			?>
			<form name="form1" method="post" action="">
				<input type="hidden" name="<?php echo $meetingID_name; ?>" value="<?php echo $meeting->meetingID; ?>">
				<input type="hidden" name="<?php echo $meetingVersion_name; ?>" value="<?php echo $meeting->meetingVersion; ?>">
				<input type="hidden" name="<?php echo $moderatorPW_name; ?>" value="<?php echo $meeting->moderatorPW; ?>">
				<input type="hidden" name="<?php echo $attendeePW_name; ?>" value="<?php echo $meeting->attendeePW; ?>">
				<tr>
				
					<td><?php echo $meeting->meetingID; ?></td>
					<td><?php echo $meeting->attendeePW; ?></td>
					<td><?php echo $meeting->moderatorPW; ?></td>
					<td>
						<?php 
						if ($info['running'] == 'true') echo "Yes";
						else echo "No"; 
						?>
					</td>
					<?php  
						if($info['hasBeenForciblyEnded']=='false'){
							?>
								<td>
									<a href ="<?php echo $bbb_joinURL; ?>" class="button-primary" >Join</a>
									<input type="submit" name="Submit" class="button-primary" value="End" />
									<input type="submit" name="Submit" class="button-primary" value="Delete" />
								</td>
							<?php  
						}else{
							?>
							<td>
								<!-- Meeting has ended and is temporarily unavailable. -->
								<input type="submit" name="Submit" class="button-primary" value="Join" />
								<input type="submit" name="Submit" class="button-primary" value="Delete" />
							</td>
							<?php  	
						}
					?>			
				</tr>
			</form>
			<?php
		}
	}
	
	?>
		</table>
		</div>
	<?php
}
		
//Begins the table of list meetings with the number of columns specified
function bbb_print_table_header(){
	?>
	<table class="stats" cellspacing="5">
		<th>
			<tr>
				<td class="hed" colspan="1">Meeting Room Name</td>
				<td class="hed" colspan="1">Attendee Password</td>
				<td class="hed" colspan="1">Moderator Password</td>
				<td class="hed" colspan="1">Running?</td>
				<td class="hedextra" colspan="1">Actions</td>
			</tr>
		</th>
	<?php
}
//================================================================================
//-----------------------------Create a Meeting-----------------------------------
//================================================================================		
//This page allows the user to create a meeting
function bbb_create_meetings() {

	global $url_name, $salt_name, $meetingID_name, $meetingVersion_name, $attendeePW_name, $moderatorPW_name;
	
    //Displays the title of the page
    echo "<h2>Create a Meeting Room</h2>";
	
	$url_val = get_option($url_name);
	$salt_val = get_option($salt_name);
	
	//Obtains the meeting information of the meeting that is going to be created
    if( isset($_POST['Submit']) && $_POST['Submit'] == 'Create' ) {
   
		//Reads the posted values
        $meetingID = $_POST[ $meetingID_name ];
		$attendeePW = $_POST[ $attendeePW_name ];
		$moderatorPW = $_POST[ $moderatorPW_name ];
		
		//Checks to see if the meeting name, attendee password or moderator password was left blank
		if($meetingID == '' || $attendeePW == '' || $moderatorPW == ''){
			//If the meeting name was left blank, the user is prompted to fill it out
			?><div class="updated"><p><strong><?php echo "All fields must be filled."; ?></strong></p></div><?php
		}
		else{
			$alreadyExists = false;
			
			//Checks the meeting to be created to see if it already exists in wordpress database
			global $wpdb;
			$table_name = $wpdb->prefix . "bbb_meetingRooms";
			$listOfMeetings = $wpdb->get_results("SELECT meetingID FROM ".$table_name);
			
			foreach ($listOfMeetings as $meeting) {
				if($meeting->meetingID == $meetingID){
					$alreadyExists = true;
					//Alerts the user to choose a different name
					?><div class="updated"><p><strong><?php echo $meetingID." meeting room already exists. Please select a different name."; ?></strong></p></div><?php
					break;
				}
			}
			
			//If the meeting doesn't exist in the wordpress database then create it
			if(!$alreadyExists){ 
			
				$rows_affected = $wpdb->insert( $table_name, array( 'meetingID' => $meetingID, 'meetingVersion' => 0, 'attendeePW' => $attendeePW, 'moderatorPW' => $moderatorPW) );
				
				//$bbb_joinURL = BigBlueButton::createMeeting($current_user->display_name, $meetingID, '', $moderatorPW, $attendeePW, $salt_val, $url_val, get_option('siteurl'));
				?><div class="updated"><p><strong><?php echo "Meeting Room Created."; ?></strong></p></div><?php
			}
			
			$meetingID = '';
			$attendeePW = '';
			$moderatorPW = '';
				
				
		}		
    }
	
    //Form to create a meeting, the fields are the meeting name, and the optional fields are the attendee password and moderator password
    ?>
	
	<form name="form1" method="post" action="">
		<p><?php echo "Meeting Room Name:"; ?> 
			<input type="text" name="<?php echo $meetingID_name; ?>" value="<?php echo $meetingID; ?>" size="20">
		</p>
		
		<p><?php echo "Attendee Password:"; ?> 
			<input type="text" name="<?php echo $attendeePW_name; ?>" value="<?php echo $attendeePW; ?>" size="20">
		</p>
		
		<p><?php echo "Moderator Password:"; ?> 
			<input type="text" name="<?php echo $moderatorPW_name; ?>" value="<?php echo $moderatorPW; ?>" size="20">
		</p>
		
		<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="<?php echo 'Create'; ?>" />
		</p> 
	</form>
	<hr />

	<?php


}


?>