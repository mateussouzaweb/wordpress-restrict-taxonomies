=== Restrict Taxonomies ===
Contributors: mateussouzaweb
Tags: restrict, access, admin, administration, cms, taxonomies, taxonomy, taxonomies, taxonomy, post type
Requires at least: 3.0
Tested up to: 3.7.1
Stable tag: 1.0

Restrict the taxonomies that users can view, add, and edit in the admin panel.

== Description ==

*Restrict Taxonomies * is a plugin that allows you to select which taxonomies users can view, add, and edit in the posts edit screen.

This plugin allows you to restrict access based on the user role AND username.

Uses http://codex.wordpress.org/Taxonomies
Based on http://wordpress.org/plugins/restrict-taxonomies/developers/

== Installation ==

1. Upload `restrict-taxonomies` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to <em>Users > Restrict Taxonomies</em> to configure which taxonomies will be restricted for each user and/or role.

== Frequently Asked Questions ==

= Does this work with custom roles I have created? =

Yes!  Roles created through plugins like Members will be listed on plugin

= Will this prevent my regular visitors from seeing posts? =

No.  This plugin only affects logged in users in the admin panel.

= I messed up and somehow prevented the Administrator account from seeing certain taxonomies! =

Restrict Taxonomies is an opt-in plugin.  By default, every role has access to every taxonomy, depending on the capabilities.
If you check a taxonomy box in a certain role, such as Administrator, you will <em>restrict</em> that role to viewing only those taxonomies.

To fix this, go to plugin settings, uncheck <em>all</em> boxes under the Administrator account and save your changes.  You can also click the Reset button to reset all changes to the default configuration.

= How does it work when I've selected taxonomies for a role AND a user? =

Selecting taxonomies for a user will <em>override</em> the taxonomies you've selected for that user's role.

In other words, Restrict Taxonomies allows you complete control over groups of users while also allowing you to selectively change a setting for a single user.

== Changelog ==

**Version 1.0 - Nov 7, 2013**

* Plugin launch!