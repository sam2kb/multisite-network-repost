=== Multisite Network Repost ===
Contributors: sam2kb
Tags: network, multisite, publish to network, copy posts, duplicate posts, clone posts, sync posts
Donate link: https://paypal.me/sam2kb
Author: Alex Kay
Author URI: https://wittyfinch.com
Requires at least: 5.0
Tested up to: 5.4
PHP Version: 7.0
Stable tag: 1.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Repost your stories to selected sites in the multisite network, preserving attachments, custom fields, categories, tags etc.


== Description ==

Repost your stories to selected sites in the multisite network, preserving attachments, custom fields, categories, tags etc. At this time the plugin only clones the post when it's published. It's a one way, one time operation and after that the posts are not connected or synced in any way.

Depending on your WordPress setup, post attachments and featured images may or may not work in the target sites. Please read next to get a shared media library support for your multisite system.

== Please Note ==

This plugin works best alongside [Multisite Global Media](https://github.com/bueltge/multisite-global-media) plugin. If both plugins are installed, all attachments and featured images are properly linked in the target site. The actual files stay in one place and don't get cloned saving your disk space. This is an optimal setup in most situations.


== Need more features? ==

This is a basic proof of concept plugin, please let me know if you want to see more features. You can also hire me to customize it for your needs.

* Support for custom fields from other plugins
* Support for drafts and private posts
* Automatic deletion from target sites if the original is deleted
* Keep the changes synced between the parent post and its clones


== Installation ==

1. Upload `multisite-network-repost` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. It is recommended that you also install [Multisite Global Media](https://github.com/bueltge/multisite-global-media) plugin for proper attachment linking
1. Enjoy


== Screenshots ==


== Changelog ==

= 1.0 =
* This version is the initial release of Multisite Network Repost