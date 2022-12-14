=== FCP First Screen CSS ===
Contributors: Firmcatalyst
Tags: inline, css, firstscreen, style, web vitals, cls, fcp, defer, dequeue, deregister
Requires at least: 5.8
Tested up to: 6.1
Requires PHP: 7.4
Stable tag: 1.3
Author: Firmcatalyst, Vadim Volkov
Author URI: https://firmcatalyst.com
License: GPL v3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

FCP First Screen CSS inline

== Description ==

Insert the inline CSS to the head of the website, disable existing styles and scripts, defer loading of not-first-screen style, apply to a single post or bulk.

= Features =

* Apply to any single post / page / custom post-type
* Apply to all posts of a particular post-type
* Apply to the blog or the archive page of a post-type
* It minifies the css before printing
* Deregister enqueued styles and scripts by name
* Apply not-first-screen CSS separately
* Defer the not-first-screen CSS loading

= Usage =

* Install and activate the plugin
* Go to the "First Screen CSS" menu item in the left sidebar of your wp-admin
* Add New, insert your CSS
* Pick where to apply and other options

== Installation ==

1. Install the plugin
2. Activate the plugin

== Development ==

You can modify the code for your needs, or suggest improvemens on [GitHub](https://github.com/VVolkov833/first-screen-css). It is pretty transparent and well-commented.

== Frequently Asked Questions ==

Waiting for your questions, which you can ask [here](https://firmcatalyst.com/contact/) or via GitHub.

== Upgrade Notice ==

= 1.3 =

* Added the development mode, visible only to admins
* Added the option to deregister existing styles and scripts
* Added the option to defer the not-first-screen CSS loading

= 1.2 =

* Added the option to deregister enqueued styles by name
* Added the field for non-first-screen css
* Added the exceptions option to the public post types

= 1.1 =

* Excluded Front Page from the bulk options, as it stands out in most cases

= 1.0 =

* Initial release