=== Theatre ===
Contributors: slimndap
Tags: theatre, stage, venue, events, shows, concerts, tickets, ticketing
Requires at least: 3.5
Tested up to: 3.8
Stable tag: trunk
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add event listings to your Wordpress website. Perfect for theaters, music venues, museums, conference centers and performing artists.

== Description ==
This plugin gives you the ability to manage seasons, productions and events in Wordpress and comes with all necessary shortcodes and widgets to show your events on your website.

Theme developers get three new PHP objects (Season, Production and Event) which they can use to further integrate Theatre into their theme. Each PHP object comes with a wide variety of smart methods and can be extended with your own methods. 

It is also possible to extend the functionality with other popular plug-ins.

__International__
Available in English, French and Dutch.

__Scope__

The Theatre plugin is kept as simple as possible so it can be used for a wide variety of event websites. 

___What is included___

* Admin screens for seasons, productions and events.
* Default templates for productions.
* Short codes for listings of productions and events.
* Widgets for listings of productions and events.

___What is not included___

A lot! But most really cool things can easily be achieved by combining the Theatre plugin with other popular plugins:

* Caching - use [W3 Total Cache](http://wordpress.org/plugins/w3-total-cache/)
* Fancy admin screens with extra fields - use [Pods Framework](http://pods.io/) and [Tabify Edit Screen](http://wordpress.org/plugins/tabify-edit-screen/)
* Simple templating - use [Timber](http://jarednova.github.io/timber/)

__What's next?__

* [Documentation](https://github.com/slimndap/wp-theatre/wiki)
* More shortcodes
* More Widgets
* Settings

__Contributors Welcome__

* Submit a [pull request on Github](https://github.com/slimndap/wp-theatre)

__Author__

* [Jeroen Schmit, Slim & Dapper](http://slimndap.com)

== Installation ==

This section describes how to install the plugin and get it working.

1. Install the plugin.
1. Start adding your productions and events using the Theatre-menu or the Theatre dashboard widget.

__Productions and events__

Theatre uses _productions_ to group your _events_. 

Each production has it's own page and can have one or more events. 

Events don't have their own pages. They only appear on pages with event listings.

So if you run a theatre then 'The Sound Of Music' is a _production_ and the show this weekend is an _event_.

If you're a musician then your band is a _production_ and your gigs are the _events_.

__Widgets__

Theater comes with one widget. Place it in your sidebar to show a list of all upcoming events.

__Shortcodes__

Theatre comes with one shortcode:

	[wp_theatre_events]
 
Add it to the content of your post or page to show a list of all upcoming events.

You can also add header above the listing:

	[wp_theatre_events]Upcoming events[/wp_theatre_events]

 
== Changelog ==

= 0.2.5 =
* Added CSS for shortcodes and widgets.

= 0.2.4 =
* Added a dashboard widget.
* Events can have a remark (eg. try-out, premiere, special guests).
* Added a sidebar widget that lists all upcoming events.

= 0.2.3 =
* Support for sold out events
* Custom text on ticket-buttons

= 0.2.2 =
* Support for Dutch language.

= 0.2.1 =
* Theatre now has it's own admin menu
* New settings page

= 0.2 =
* Several smart functions which can be used inside templates.
* Short code for listing of events.

= 0.1 =
* Basic version of the plugin.


