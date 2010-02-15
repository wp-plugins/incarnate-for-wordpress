=== Plugin Name ===
Contributors: MIX ONLINE
Donate link: http://www.visitmix.com/
Tags: comments, spam, avatar, incarnate, gravatar
Requires at least: 2.8.0
Tested up to: 2.9.1
Stable tag: 1.2

Incarnate for WordPress brings a rich avatar experience to your comments.  Enter a 
handle from the web and easily select an avatar for a comment from providers like Facebook, 
Twitter, YouTube, MySpace and Gravatar. 

== Description ==
Incarnate for WordPress brings a rich avatar experience to your comments.  Enter a 
handle from the web and easily select an avatar for a comment from providers like Facebook, 
Twitter, YouTube, MySpace and Gravatar. 

== Installation ==
1. Upload the wp-incarnate folder to the `/wp-content/plugins/` directory (this includes wp-incarnate.php, incarnate.js and an images foler).
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Configure the plugin through "Settings"

== Frequently Asked Questions ==

= What if Incarnate doesn't show up in my comment form? =

You might have a custom theme that doesn't exactly match the WordPress guidelines.  If you'd like to use
the plug-in with your theme you'll need to go to the settings page and follow the instructions to install
Incarnate.

= How do I change the look and feel of the Incarnate avatar selection experience? =

You can make changes to the look and feel, but you'll have to edit both .php and .js files. 

There are two places where the Incarnate UI is created.  The first place is the static HTML that gets inserted into the comment form. You can see this HTML on line 399 of wp-incarnate.php.  This HTML string represents the static html comment form. If you do modify this html, take note of the various IDs of the form values. Don't change these!

This HTML snippet works in conjunction with the incarnate.js file, which is where the dropdown of avatars is generated. In particular, it is on line 131 where each div (containing the incarnate image and provider image is displayed) is generated.  

== Screenshots ==

1. Incarnate is a simple form for adding avatars to your comment!

== Changelog ==

= 1.2 =
* Updated automatic configuration - works with tons of themes!
* Settings page is simple and provides help

= 1.1 =
* Fixed path issue with images
* Fixed options page cross-browser issues
* Fixed comment Gravatars that pre-date Incarnate installation

= 1.0 =
* Launching the plug-in for (http://www.visitmix.com/ "MIX ONLINE")

== Upgrade Notice ==

= 1.2 =
Please upgrade to make Incarnate more compatible!

== Power User Help ==
The Incarnate plug-in has two parts. First is the comment form. This will allow a commentor to select an avatar. The second part is the avatar display.
Comment Form

Normally automatic configuration will add the form using JavaScript. Some themes will not work with this. For these you will need to add the comment form manually.

You'll need to open the comments.php file in your theme (this can be done easily through the WordPress theme editor).  Next you'll need to find the comment form and add this line:

<?php if(function_exists('incarnate_for_wordpress_insert_ui')) { incarnate_for_wordpress_insert_ui(); } ?>

Avatar Display

Normally a theme will call "get_avatar" to display the avatar next to each comment. If that's not the case then you need to add the code below inside of each comment.

<?php if(function_exists('incarnate_for_wordpress_insert_avatar')) { incarnate_for_wordpress_insert_avatar($comment); } ?>
