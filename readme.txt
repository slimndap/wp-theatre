=== Theater for WordPress ===
Contributors: slimndap
Tags: theatre, stage, venue, events, shows, concerts, tickets, ticketing, sports, performances, calendar, workshops, theater, cinema
Requires at least: 3.8
Tested up to: 4.1
Stable tag: trunk
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Build a website for your theater, music venue, sports team, cinema or your rockband! Add upcoming events and ticketing to your WordPress website. 

== Description ==
A WordPress plugin to manage events with all necessary shortcodes and widgets for your theater.

__Shortcodes__

Theater comes with several shortcodes and widgets to show off your events. See the [documentation](https://github.com/slimndap/wp-theatre/wiki/Shortcodes) for an overview.

__Available add-ons__

* [Ticketmatic](http://slimndap.com/product/ticketmatic-for-wordpress/)

__Missing features?__

Submit a request on the [forum](http://wordpress.org/support/plugin/theatre).

__Extending Theater for WordPress__

Easy! Write your own plugins that extend Theater for WordPress. Check out my [example](https://github.com/slimndap/wp-theatre-example-extension) plugin on GitHub.

__Contributors welcome__

* Submit a [pull request on GitHub](https://github.com/slimndap/wp-theatre)

__Author__

* [Jeroen Schmit, Slim & Dapper](http://slimndap.com)

== Installation ==

This section describes how to install the plugin and get it working.

1. Install the plugin.
1. Start adding your productions and events using the Theater-menu or the Theater dashboard widget.

__Productions and events__

Theater uses _productions_ to group your _events_. 

Each production has it's own page and can have one or more events. 

Events don't have their own pages. They only appear on pages with event listings.

So if you run a theater then 'The Sound Of Music' is a _production_ and the show this weekend is an _event_.

If you are a musician then your band is a _production_ and your gigs are the _events_.

For a sports club your teams are the _productions_ and the games are the _events_.


== Frequently Asked Questions ==

__All event listings look really weird!__

Make sure that you checked the 'Enable built-in Theater stylesheet'-option in the Theater plugin settings.

__I don't see the Theater Calendar widget__

Make sure that you select a page to show your event listings on in the Theater plugin settings.

__I really need feature X. Can you add it?__

If you are missing a feature that you really need for your website you can:

1. submit a feature request on the [forum](http://wordpress.org/support/plugin/theatre) or
1. add it yourself and submit a pull request on [GitHub](https://github.com/slimndap/wp-theatre) or
1. [hire me](mailto:jeroen@slimndap.com).

The new feature may be added directly to the Theater plugin or as an extension so that others may benefit from this as well.

== Changelog ==

= 0.10 =

= 0.9 = 
* Added support for start and end dates in `[wpt_events]` shortcode. You can throw anything at it that strtotime understands! Some examples:
    * `[wpt_events start="now"]`
    * `[wpt_events start="today"]`
    * `[wpt_events start="2014-09-02"]`
    * `[wpt_events end="now"]`
    * `[wpt_events start="2014-09-01" end="2014-10-01"]`
    * `[wpt_events start="now" end="+1 week"]`
    * `[wpt_events start="next Monday" end="next Monday +1 week"]`
* Removed WordPress SEO by Yoast meta box from event admin screens.
* Improved category filtering for production and event listings. You can now use `cat`, `category_name`, `category__and`, `category__in` and `category__not_in` attributes in the `[wpt_events]` and `[wpt_productions]` shortcodes.
* Added productions filtering for production listings. You now use `post__in` and `post__not_in` attributes in the `[wpt_productions]` shortcode.


= 0.8.3 =
* Bugfixes.

= 0.8.2 = 
* New date filter for template placeholder: `{{datetime|date('D j')}}`. Thank you [Epco](http://wordpress.org/support/profile/epco)!
* Show all events for today or tomorrow: `[wpt_events day="today"]` and `[wpt_events day="tomorrow"]`. Thank you [mychelemy](https://github.com/mychelemy)!
* New Theater Production widget. Highlight a single production in your sidebar.

= 0.8 =
* New Theater Calendar widget with upcoming events.
* New calendar shortcode: [wpt_calendar].
* New Theater Categories widget with a list of all categories with upcoming events.
* Dedicated event listing page (with pretty URLs). No shortcode needed!
* Day grouping and pagination for event listings. Very useful for cinema websites.
* Support for custom fields in shortcode templates. Thanks [ydbondt](https://github.com/ydbondt).
* [wpt_production_events] now supports a production ID if used outside of a production detail page: [wpt_production_events production=123].
* Rearranged settings screen.
* Updated Dutch and German (by [pixelfis.ch](http://pixelfis.ch)) translations.
* And [more...](http://www.slimndap.com/event-calendar-better-listings/)

= 0.7 =
* Support for tickets prices, cancelled events.
* Filter listings by season or categories. See the [documentation](https://github.com/slimndap/wp-theatre/wiki/Shortcodes).
* A new German translation (by [pixelfis.ch](http://pixelfis.ch)).
* Wordpress SEO by Yoast support.
* Jetpack Featured Content slider support.
* And [more...](http://slimndap.com/theatre-for-wordpress-0-7-prices-and-categories)

= 0.6 =
* Added support for templates and placeholders in the [wpt_events], [wpt_productions] and [wpt_production_events] shortcodes. See the [documentation](https://github.com/slimndap/wp-theatre/wiki/Shortcodes).
* Sort your productions by date on the production admin page.
* Added an end time to events so events can display a 'duration'.

= 0.5 =
* Production categories/genres.

= 0.4 =
* New wpt_productions shortcode.
* Customize whcih fields to show in event and production listings. See [documentation](https://github.com/slimndap/wp-theatre/wiki/Shortcodes).

= 0.3.8 =
* Show event listings above or below content on production page.
* Set your own default text on ticket buttons and above event listings.
* New wpt_event_ticket_button shortcode.

= 0.3.7 =
* Microdata for production listings.
* Social meta tags (for Facebook, Twitter & Google+).
* Disable built-in CSS and add custom CSS.

= 0.3.6 =
* Currency symbol can be set.
* Responsive event listings .

= 0.3.5 =
* Updated Dutch language files.
* Improved layout of production listings.
* Show tickets pages in an iframe, new window of lightbox/thickbox.
* Shopping cart (requires help from a developer).

= 0.3.4 =
* Better in avoiding CSS conflict with themes.

= 0.3.3 =
* Microdata for events.
* Speed improvements.

= 0.3.2 =
* new widget: upcoming productions.

= 0.3.1 =
* bugfixes and technical improvements.
* better support for bulk-editing productions.
* better support for quick-editing productions.

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
* Support for sold out events.
* Custom text on ticket-buttons.

= 0.2.2 =
* Support for Dutch language.

= 0.2.1 =
* Theater now has it's own admin menu.
* New settings page.

= 0.2 =
* Several smart functions which can be used inside templates.
* Short code for listing of events.

= 0.1 =
* Basic version of the plugin.

== Upgrade Notice ==

= 0.9.6 =
* Bugfix: The {{content}} placeholder was causing an infinite loop on some pages with event listings.
* Bugfix: The output of the {{remark}} placeholder was lacking wrappers divs.

= 0.9.5 =
* Added new filters to manipulate the content of a single production page.

= 0.9.4 = 
* Bugfix: `wpt_loaded` action hooks was fired too early.
* New: filters for the calendar output.

= 0.9.3 = 
* Bugfix: sometimes not all seasons were showing up in a listing.

= 0.9.2 = 
* Introduced some new hooks and filters.

= 0.9.1 =
* Bugfix: production and events listing were not working properly when being grouped by category, month or date.

= 0.8.3. =
* Fixed event creation link for WordPress installs with non-standard folder structures.

= 0.8.2. =
* Fixed event calendar showing months with old events.

= 0.8.1 =
* Event listing sometimes wasn't showing all events.

= 0.8 =
* Major update. Always backup before you upgrade!

= 0.7.6 =
* Fixed sticky productions showing up in the wrong places.

= 0.7.5 =
* Added {{content}} and {{excerpt}} template placeholders for event-listings.
* Added {{content}} template placeholder for production-listings.
* Fixed {{categories}} template placeholder for production-listings.
* Added a template setting to productions and events widgets.

= 0.7.4 =
* Fixed some PHP 5.2 issues that prevented the plugin from activating.
* Small bugfixes.

= 0.7.3 =
* Added support for hidden and custom event tickets statusses.

= 0.7.2 =
* Sticky productions were not showing in production listings.

= 0.6.2 =
* Important: Please deactivate and then reactivate this plugin after the update.

= 0.6.1 =
* Fix: Theater was messing up the admin listings of other post types.

= 0.6 =
* v0.6 requires you to update your shortcodes. Please check the [documentation](https://github.com/slimndap/wp-theatre/wiki/Shortcodes) for the details.