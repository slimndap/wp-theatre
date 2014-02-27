=== Theatre ===
Contributors: slimndap
Tags: theatre, stage, venue, events, shows, concerts, tickets, ticketing
Requires at least: 3.5
Tested up to: 3.8
Stable tag: trunk
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add events to your Wordpress website. Build a website for your theater, music venue, museum, conference center or your rockband!

== Description ==
This plugin gives you the ability to manage seasons, productions and events in Wordpress and comes with all necessary shortcodes and widgets to show your events on your website.


__Available add-ons__

* [Ticketmatic](http://slimndap.com/product/ticketmatic-voor-wordpress/)

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

== Frequently Asked Questions ==

__All event listings look really weird!__

Make sure that you checked the 'Enable built-in Theatre stylesheet'-option in the Theatre plugin settings.

== Changelog ==

= 0.4 =
* New wpt_productions shortcode.
* Customize whcih fields to show in event and production listings. See [documentation](https://github.com/slimndap/wp-theatre/wiki/Shortcodes).

= 0.3.8 =
* Show event listings above or below content on production page.
* Set your own default text on ticket buttons and above event listings.
* New wpt_event_ticket_button shortcode.

= 0.3.7 =
* Microdata for production listings
* Social meta tags (for Facebook, Twitter & Google+)
* Disable built-in CSS and add custom CSS

= 0.3.6 =
* Currency symbol can be set
* Responsive event listings 

= 0.3.5 =
* Updated Dutch language files
* Improved layout of production listings
* Show tickets pages in an iframe, new window of lightbox/thickbox
* Shopping cart (requires help from a developer)

= 0.3.4 =
* Better in avoiding CSS conflict with themes

= 0.3.3 =
* Microdata for events
* Speed improvements

= 0.3.2 =
* new widget: upcoming productions

= 0.3.1 =
* bugfixes and technical improvements
* better support for bulk-editing productions
* better support for quick-editing productions

= 0.3 =
* bugfix: events with the same date and time were causing conflicts.
* support for my upcoming Ticketmatic extension.

= 0.2.7 =
* 2 extra columns (dates and cities) to productions admin page.
* Grouped and paged event listings.

= 0.2.6 =
* Support for sticky productions.
* Support for French language.

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


