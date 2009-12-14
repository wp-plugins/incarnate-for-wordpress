=== Plugin Name ===
Contributors: MIX ONLINE
Donate link: http://www.visitmix.com/
Tags: comments, spam, avatar, incarnate, gravatar
Requires at least: 2.8.0
Tested up to: 2.8.6
Stable tag: trunk

Incarnate for WordPress brings a rich avatar experience to your comments.  Enter a 
handle from the web and easily select an avatar for a comment from providers like Facebook, Twitter, YouTube, MySpace and Gravatar. 

== Description ==
Incarnate for WordPress brings a rich avatar experience to your comments.  Enter a 
handle from the web and easily select an avatar for a comment from providers like Facebook, Twitter, YouTube, MySpace and Gravatar. 


== Installation ==
1. Upload the wp-incarnate folder to the `/wp-content/plugins/` directory (this includes wp-incarnate.php, incarnate.js and an images foler).
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Configure the plugin through "Settings"

== Frequently Asked Questions ==

= What if Incarnate doesn't show up in my comment form? =

You might have a custom theme that doesn't exactly match the WordPress guidelines.  If you'd like to use
the plug-in with your theme you'll need to use the "Power User" code from the settings page and add it
to your template using the instructions there.

= How do I change the look and feel of the Incarnate avatar selection experience? =

You can make changes to the look and feel, but you'll have to edit both .php and .js files. 

There are two places where the Incarnate UI is created.  The first place is the static HTML that gets inserted into the comment form. You can see this HTML on line 399 of wp-incarnate.php.  This HTML string represents the static html comment form. If you do modify this html, take note of the various IDs of the form values. Don't change these!

This HTML snippet works in conjunction with the incarnate.js file, which is where the dropdown of avatars is generated. In particular, it is on line 131 where each div (containing the incarnate image and provider image is displayed) is generated.  

== Screenshots ==

1. Incarnate is a simple form for adding avatars to your comment!

== Changelog ==

= 1.0 =
* Launching the plug-in for (http://www.visitmix.com/ "MIX ONLINE")

== Power User Help ==
You may need to include the custom template tags if you don't automatically see the Incarnate UI.  This is accomplished
by adding ...
