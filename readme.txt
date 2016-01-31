=== Theater for WordPress ===
Contributors: slimndap
Tags: theatre, stage, venue, events, shows, concerts, tickets, ticketing, sports, performances, calendar, festival, workshops, theater, cinema
Requires at least: 4.0
Tested up to: 4.3
Stable tag: trunk
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=CZERCBG5SUGQW

A free plugin to publish your events on a WordPress website. Perfect for theaters, clubs, cinemas and festivals.

== Description ==
A WordPress plugin to manage events with all necessary shortcodes and widgets for your theater.


__Shortcodes__

Theater comes with several shortcodes and widgets to show off your events. See the [documentation](https://github.com/slimndap/wp-theatre/wiki/Shortcodes) for an overview.

__Available extensions__

* [Event Duplicator](http://theater.slimndap.com/downloads/event-duplicator-for-theater/?utm_source=wordpress.org&utm_medium=web&utm_campaign=readme.txt&utm_content=description) – This __free__ extension adds a new action to the event editor that instantly duplicates the event. Very helpful if your need to enter a lot of events at once.
* [Events Slider](http://theater.slimndap.com/downloads/events-slider-theater/?utm_source=wordpress.org&utm_medium=web&utm_campaign=readme.txt&utm_content=description) – Show your events in a touch enabled and responsive slider.
* [Timetable](http://theater.slimndap.com/downloads/timetable-for-theater/?utm_source=wordpress.org&utm_medium=web&utm_campaign=readme.txt&utm_content=description) – Show your event showtimes in a clear table layout. Perfect for cinemas and festivals.
* [Kijkwijzer](http://theater.slimndap.com/downloads/kijkwijzer-for-theater/?utm_source=wordpress.org&utm_medium=web&utm_campaign=readme.txt&utm_content=description) – Add Kijkwijzer icons to your events.
* [BBFC](http://theater.slimndap.com/downloads/bbfc-for-theater/?utm_source=wordpress.org&utm_medium=web&utm_campaign=readme.txt&utm_content=description) – Add British Board of Film Classification (BBFC) icons to your movies.
* [Ticketmatic](http://slimndap.com/product/ticketmatic-for-wordpress/)
* [ActiveTickets](http://theater.slimndap.com/downloads/activetickets-for-wordpress/utm_source=wordpress.org&utm_medium=web&utm_campaign=readme.txt&utm_content=description) - Automatically import your ActiveTickets events and sell them through your website.

> <strong>Newsletter</strong><br>
> This plugin is in active development and updated frequently. Please [sign-up for the newsletter](http://theater.slimndap.com/newsletter?utm_source=wordpress.org&utm_medium=web&utm_campaign=readme.txt&utm_content=description) to stay informed about upcoming features and successful showcases.

__Missing features?__

Submit a request on the [forum](http://wordpress.org/support/plugin/theatre).

__Extending Theater for WordPress__

Easy! Write your own plugins that extend Theater for WordPress. Check out my [example](https://github.com/slimndap/wp-theatre-example-extension) plugin on GitHub.

__Contributors welcome__

* Submit a [pull request on GitHub](https://github.com/slimndap/wp-theatre)

== Installation ==

This section describes how to install the plugin and get it working.

__Installation__

1. Go to _Plugins_ → _Add new_.
1. Search for 'Theater'.
1. Look for the _Theater for WordPress_ plugin and click on _Install now_.
1. Wait for the next screen to load and click on _Activate Plugin_.

__Add events__

Theater for Wordpress uses _productions_ to group your _events_. Each production can have one or more events.

Add your first event:

1. Go to _Theater_ → _Productions_ → _Add new_.
1. Enter a title and description for your production.
1. Scroll down and click on _Add a new event_.
1. Enter a start time that is in the future.
1. Enter the other details for your event.
1. Click on _Save event_.
1. Add a featured image (if your theme supports it).
1. Click on _Publish_.

__Show your events__

You can show your upcoming events on a page.

1. Go to _Pages_ → _Add new_.
1. Give your page a title (eg. 'Upcoming events').
1. Click on _Publish_.
1. Go to _Theater_ → _Settings_ → _Display_.
1. Set _Page to show upcoming events on_ to the page that you just created.
1. Set _Position on page_ to _show above content_.
1. Click on _Save Changes_.
1. Go to _Theater_ → _Settings_ → _Style_.
1. Check _Enable built-in Theater stylesheet_.
1. Click on _Save Changes_.

The page that you created now shows your upcoming events.


== Screenshots ==

1. Manage events in the Wordpress admin.
2. The event editor.
3. Manage events in the Wordpress admin
4. Your upcoming events listed on your website.
5. Event details.

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

= 0.13 =

Release Date: September 3rd, 2015

* Enhancements
    * Production listings can now be filtered by start and end dates. See the updated [documentation](https://github.com/slimndap/wp-theatre/wiki/Shortcodes#production-listing).
    * Production listings can now be grouped and paginated by day, month or year.
    * Stripped all unnecessary elements off the event edit screen.
    * Made it possible to alter the behaviour of the tickets lightbox with a filter.
    * You can now browse extensions inside the WordPress admin.

See the [release post](http://theater.slimndap.com/theater-for-wordpress-0-13-released/?utm_source=wordpress.org&utm_medium=web&utm_content=changelog&utm_campaign=readme.txt) for examples.

* Bugfixes
	* Fixed a problem in production listings with events that start before 1-1-1970.
	* Visibility of ticket buttons didn't account for timezones.
	* Pagination for listings wasn't working when the listing page was the same as the front page.
	* Weekdays were showing up as question marks when using a multibyte language (eg. Russian).

* Requirements
	* The plugin is no longer tested for WordPress version prior to 4.0 (0.13.3).

= 0.12 =

Release Date: July 3rd, 2015

* Enhancements
    * You can adjust the slug for production detail pages in the permalink settings. Your URLs can now look like `http://example.com/show/billy-elliot` or `http://example.com/concert/kurt-vile`.
    * New template placeholders for events: `{{starttime}}`, `{{startdate}}`, `{{endtime}}` and `{{enddate}}`. Thanks [jbrandligt](https://github.com/jbrandligt)!
    * You can choose the size for your thumbnail placeholder: `{{thumbnail('large')}}`.
    * The URLs for your ticket pages look nicer, if you use the iframe option: `http://example.com/tickets/billy-elliot/123`.
    * Added new filters to manipulate the output of the Theater Production widget and the Theater Events widget.
    * Updated the German translation. Thanks [henk23](https://github.com/henk23)!
    * Added error messages to the import status. Thanks [Menno](https://www.linkedin.com/in/mennoluitjes)!
	* Made it easier for extensions to add functionality to the event editor (0.12.4).

* Clean up
    * Productions no longer have an archive page on `http://example.com/productions/`. 
    * Removed the Theater dashboard widget since it was kind off useless and slowing the admin interface down.

* Bugfixes
	* Some past events were showing a false tickets status.
	* It was impossible to clear a value for an event field.
	* Events were polluting the 'link to existing content' section on the 'Insert/edit link' dialog (0.12.3).
	* Fixed a timezone problem with the `{{datetime}}` template tag (0.12.6).
	* Fixed a lightbox problem with themes that don't properly support screen-reader texts (0.12.6).
	* Fixed a problem with invalid event end dates (0.12.7).
	* The tickets status was not being saved when creating a new event (0.12.7).
	
= 0.11 =

Release Date: May 30th, 2015

* Enhancements
    * A brand new event editor. See the [release notes](http://www.slimndap.com/theater-for-wordpress-0-11-a-new-event-editor/) for all the info.
    * Support for `post__in` and `post__not_in` in the `[wpt_events]` shortcode. See the [documentation](https://github.com/slimndap/wp-theatre/wiki/Shortcodes) for examples. Thanks [jbrandligt](https://wordpress.org/support/profile/jbrandligt)!
    * Support for custom filters in event listings. See [this post](http://www.slimndap.com/add-venue-and-city-filters-to-event-listings/) for an example.

= 0.10 =

Release Date: December 29th, 2014

* Bugfixes
    * Changed priority of some `save_post`-hooks to avoid conflicts with ACF's `save_post`-hooks. Thanks [paramir](https://github.com/paramir)!
    * Fixed some PHP warnings.

* Enhancements
    * Event listings can be paginated or grouped by year: `[wpt_events paginateby=year]` or `[wpt_events groupby=year]`.
    * Better support for past events. You can now create historic event listings paginated or grouped by day, month, year or category.
    * You can change the order of events and production in listings: `[wpt_events order=desc]` or `[wpt_productions order=desc]`.

* Experimental
    * A new WPT_Importer class to easily create your own import for your ticketing system. Still a work in progress. Play with it, but don't use it on your production website. Always make backups!
    

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

= 0.13.6 =
Adds a new filter for event permalinks.

= 0.13.5 =
Fixed a problem with event templates that use HTML tags and moved Theater javascript to footer to improve loading speed.

= 0.13.4 =
Bugfix: Event editor wasn't saving enddate when creating a new event.

= 0.13.3 =
Bugfix: weekdays were showing up as question marks when using a multibyte language (eg. Russian).

= 0.13.2 =
Adds an extensions page to the Theater menu.

= 0.13.1 =
Fixes related to events that start before 1-1-1970, disappearing ticket buttons and list pagination on the front page.

= 0.13 =
Adds date filtering for production listings.

= 0.12.7 =
Fixes a problem with invalid end dates and the tickets status not being saved.

= 0.12.6 =
Fixes a timezone problem with the {{datetime}} template tag and a lightbox problem with themes that don't properly support screen-reader texts.

= 0.12.5 =
Moved the thumbnail column on the production admin screen behind the title column to better support the new responsive columns of WordPress 4.3. 

= 0.12.4 = 
Made it easier for extensions to add functionality to the event editor.

= 0.12.3 = 
Fix: Removed events from the 'link to existing content' section on the 'Insert/edit link' dialog.

= 0.12.2 =
Fix: Thumbnail sizes were not working in event listings.

= 0.12.1 =
You can now choose the size for your thumbnail placeholder like this: {{thumbnail('large')}}.

= 0.12 =
Adds new placeholders, nicer URLs, removes clutter and fixes some small bugs.

= 0.11.9 =
Added support for custom filters in event listings (eg. venue of city).

= 0.11.8 =
Fixes a problem with the production slug in Dutch websites and adds support for `post__in` and `post__not_in` in the `[wpt_events]` shortcode.

= 0.11.7 =
* Fixes a problem with the importer.

= 0.11.6 =
* Removes a couple of (harmless) php warnings.

= 0.11.5 =
* Create new events for a production without a page reload.

= 0.11.4 =
* Fixes two bugs related to 'scheduled' productions.

= 0.11.3 =
* Fix: The event editor was saving ghost events in the background.

= 0.11.2 =
* Fix: Events without prices ended up having a price of 0.00.
* Fix: Renamed the datetimepicker to avoid conflicts with other datetimepickers.

= 0.11.1 =
* Fix: The new event editor was overwriting disabled event fields.

= 0.11 =
* Fix: Importer now only stores fields for event when the fields are explicitly set.

= 0.10.16 =
* Fix: The 'date' filter for template placeholders wasn't handling timezones correctly.

= 0.10.15 =
* Fix: The 'start' param for event listings wasn't handling timezones correctly.

= 0.10.14 =
* Fix: Past events still showed tickets buttons. Thanks [Menno](https://www.linkedin.com/in/mennoluitjes)!
* Fix: Importer emptied fields that were not part of the import.
* Fix: Tickets button for events wasn't properly showing the ticket status when called directly in PHP. Thanks [paramir](https://github.com/paramir)!
* Improvement: Significally decreased the number of queries used for listings.

= 0.10.13 =
* Fix: Removed a loop when saving a production.

= 0.10.12 =
* Fix: Trashed events were visible on production detail pages. Thanks [Menno](https://www.linkedin.com/in/mennoluitjes)!
* Fix: When saving a production, it now correctly syncs the 'future' post_status to all its events. Thanks [Menno](https://www.linkedin.com/in/mennoluitjes)!
* Fix: Trashed events will remain trashed. Thanks [Menno](https://www.linkedin.com/in/mennoluitjes)!

= 0.10.11 =
* Fix: Navigation for a listing filter was broken on the dedicated listing page.

= 0.10.10 =
* New filters: 
    * `wpt_listing_filter_pagination_option_name`
    * `wpt_listing_filter_pagination_option_url`
    * `wpt_listing_filter_pagination_option_html`
* Fix: Name of the `wpt_event_title`-filter was wrong.
* Fix: Removed an [XSS Vulnerability]:(https://make.wordpress.org/plugins/2015/04/20/fixing-add_query_arg-and-remove_query_arg-usage/).
* Fix: jQuery Date and Time picker wasn't working on websites that use https.

= 0.10.9 =
* Bugfix: The cart wasn't working when using AJAX.
* Bugfix: Different shortcodes for listings sometimes showed the same listing.
* Added a `wpt_event_tickets_status`-filter. Thanks [Menno](https://www.linkedin.com/in/mennoluitjes)!

= 0.10.8 =
* Bugfix: The tickets button was always showing if you chose to show your ticketing screens inside an iframe. Even when there is no tickets url present.
* Added a `wpt_event_template_default`-filter.
* Added a `wpt_production_template_default`-filter.

= 0.10.7 =
* Removed useless microdata from HTML output. Will be replaced by JSON-LD in a future release.
* Added a 'wpt_event_tickets_url_html'-filter.
* Removed some W3TC code that never worked.

= 0.10.6 =
* Bugfix: Event calendar was showing months with historic events. Thanks [kulturbrigad](https://wordpress.org/support/profile/kulturbrigad)!

= 0.10.5 =
* Tweak: Improved the HTML output for custom event fields. 

= 0.10.4 =
* Bugfix: Paginated lists sometimes showed the same content on every page.

= 0.10.3 =
* Bugfix: 'Events' header was showing on the page for a production without events.
* Bugfix: Sticky productions didn't show when 'post__not_in' was used.
* Bugfix: Old events were showing in the list with upcoming events on the production page. 

= 0.10.2 =
* Bugfix: Category pagination stopped working on listing page. Thanks [Oscar](https://wordpress.org/support/profile/ossiesayshi)!
* Bugfix: Seasons were not sorted properly in list views.

= 0.10.1 =
* Bugfix: Events widget was showing old events. Thanks [Epco](http://wordpress.org/support/profile/epco)!
* Bugfix: Time-based pagination (day/month/year) for event listings wasn't respecting the sorting order of the events.

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