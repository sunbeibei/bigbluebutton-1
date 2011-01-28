=== BigBlueButton ===
Contributors: blindsidenetworks
Donate link: http://blindsidenetworks.com/integration
Tags: blindsidenetworks, bigbluebutton, opensource, web, conferencing,
Requires at least: 3.0.1
Tested up to: 3.0.1
Stable tag: 1.0.2

This plugin integrates BigBlueButton functionality into Wordpress. 

== Description ==

[BigBlueButton](http://bigbluebutton.org/ "BigBlueButton") is an open source web conferencing system. This plugin integrates BigBlueButton into WordPress allowing bloggers to create and manage meetings rooms to interact with their readers.

**For more information on setting up your own BigBlueButton server or for using an external hosting provider visit [http://bigbluebutton.org/support](http://bigbluebutton.org/support "http://bigbluebutton.org/support")**

== Installation ==

The easiest way to install is to watch this [installation video](http://www.youtube.com/watch?v=8Tle9BEKfFo "installation video") on YouTube. Here's an overview of the installation.

   1. Log in as an admin and click on the Plugins menu on the sidebar.
   1. Click Add new.
   1. In the search bar enter "bigbluebutton" and click search plugins.
   1. When you find the plugin called BigBlueButton by Blindside Networks click the install now link.
   1. Activate the Plugin.
   1. Click on widgets under the Appearance menu.
   1. Find the BigBlueButton Widget. Then click and drag it to either the right, content, or footer windows on the right of the screen depending on where you wish the BigBlueButton widget to appear.
   1. Click on BigBlueButton under the settings menu.
   1. Fill out the URL of where the BigBlueButton server is running (be sure to add /bigbluebutton/ to the end of the URL) and its salt. Then click on save changes.
   1. You are ready to begin creating meetings, and holding conferences.

== Frequently Asked Questions ==


**Why is it giving an error about creating a meeting room?**

Make sure you are using BigBlueButton 0.7.

**What is this error: "Unable to display the meetings. Please check the url of the bigbluebutton server AND check to see if the bigbluebutton server is running."?**

You must make sure that your url ends with "/bigbluebutton/" at the end. 

So as an example:

* Wrong - "http://example.com/"
* Correct - "http://example.com/bigbluebutton/" 

**How can I improve security?**

You should enable the curl extension in php.ini. 

== Changelog ==

= 1.0.2 = 
* Users can now specify for each meeting if attendees must wait for the moderator.
* Added default url and salt of a BigBlueButton test server.
* Code optimization


= 1.0.1 =
* Updated to use version 1.2 of the php api.
* Uses time stamps for the meeting version, which results in better performance.
* Includes some bug fixes.

= 1.0.0 =
* Added the initial files.

== Upgrade Notice ==

= 1.0.2 =
This version enables users to specify for each meeting if attendees must wait for the moderator.

= 1.0.1 =
This version provides better performance, and includes some bug fixes.

= 1.0.0 =
This version is the official release of the bigbluebutton plugin.