=== Theater for WordPress ===
Contributors: slimndap
Tags: theatre, stage, venue, events, shows, concerts, tickets, ticketing, sports, performances, calendar, festival, workshops, theater, cinema
Requires at least: 4.0
Tested up to: 4.5
Stable tag: 0.15
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=CZERCBG5SUGQW

A free plugin to publish your events on a WordPress website. Perfect for theaters, music venue, sports clubs, cinemas and festivals.

== Description ==

With the Theater for WordPress plugin it is possible to manage and publish _events_ that have one of more _dates_.

__Features__
- Show events, concerts, movies or matches on your WordPress website.
- Manage your events and dates in the WordPress admin.
- Create custom lists with events with shortcodes or widgets. See the [documentation](https://github.com/slimndap/wp-theatre/wiki/Shortcodes) for an overview.
- Integrate with existing ticketing solutions.
- Work with any theme.
- Add functionality with many [extensions](https://wp.theater/extensions/?utm_source=wordpress.org&utm_medium=web&utm_campaign=readme.txt&utm_content=description).
- Loaded with filters, made with developers in mind.

__Available extensions__

* [Event Duplicator](https://wp.theater/downloads/event-duplicator-for-theater/?utm_source=wordpress.org&utm_medium=web&utm_campaign=readme.txt&utm_content=description) – This __free__ extension adds a new action to the event editor that instantly duplicates the event. Very helpful if your need to enter a lot of events at once.
* [Events Slider](https://wp.theater/downloads/events-slider-theater/?utm_source=wordpress.org&utm_medium=web&utm_campaign=readme.txt&utm_content=description) – Show your events in a touch enabled and responsive slider.
* [Timetable](https://wp.theater/downloads/timetable-for-theater/?utm_source=wordpress.org&utm_medium=web&utm_campaign=readme.txt&utm_content=description) – Show your event showtimes in a clear table layout. Perfect for cinemas and festivals.
* [Kijkwijzer](https://wp.theater/downloads/kijkwijzer-for-theater/?utm_source=wordpress.org&utm_medium=web&utm_campaign=readme.txt&utm_content=description) – Add Kijkwijzer icons to your events.
* [MPAA](https://wp.theater/downloads/mpaa-for-theater/?utm_source=wordpress.org&utm_medium=web&utm_campaign=readme.txt&utm_content=description) – Add Motion Picture Association of America (MPAA) film rating labels to your movies.
* [BBFC](https://wp.theater/downloads/bbfc-for-theater/?utm_source=wordpress.org&utm_medium=web&utm_campaign=readme.txt&utm_content=description) – Add British Board of Film Classification (BBFC) icons to your movies.
* [Ticketmatic](http://slimndap.com/product/ticketmatic-for-wordpress/)
* [ActiveTickets](https://wp.theater/downloads/activetickets-for-wordpress/utm_source=wordpress.org&utm_medium=web&utm_campaign=readme.txt&utm_content=description) - Automatically import your ActiveTickets events and sell them through your website.
* [Veezi](https://wp.theater/downloads/veezi-for-wordpress/utm_source=wordpress.org&utm_medium=web&utm_campaign=readme.txt&utm_content=description) - Automatically import your Veezi films and sell them on your website.


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

Theater for WordPress uses _productions_ to group your _events_. Each production can have one or more events.

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

1. Manage events in the WordPress admin.
2. The event editor.
3. Manage events in the WordPress admin.
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

= 0.15 =

Release Date: March 22nd, 2016

This release focusses on a more streamlined experience when managing your events. Let me know what you think and if you are missing anything in the [forum](https://wordpress.org/support/plugin/theatre).

* Enhancements
    * Renamed 'productions' to 'events'.
    * Simplified the events admin screen.
    * Added a new `{{prices}}` placeholder for productions (0.15.3).
    * Events can now be filtered by tags (0.15.5).
    
* Bugfix
    * The translation setting for the header above events on the productions page was also being used in other places (eg. the admin menu) (0.15.2). Thank you [Bob Morris](https://wordpress.org/support/profile/bob-morris)!
	* Seasons are now hidden from search results (0.15.3).
	* The post slug was not working for events when using bulk edit in the events admin screen (0.15.5).
	* Updates for extension plugins were not working anymore (0.15.6).
	* Event where not always showing the correct end date.
    
= 0.14 =

Release Date: February 2nd, 2016

* Enhancements
    * The plugin is now ready for [language packs hosted on WordPress.org](https://make.wordpress.org/plugins/2015/09/01/plugin-translations-on-wordpress-org/). This makes it much easier for you to contribute a translation of the plugin in your native language. Add you translation [here](https://translate.wordpress.org/projects/wp-plugins/theatre).
    * The `[wpt_events]` shortcode now accepts a `production` parameter to limit the a events list to one or more productions (0.14.4). See the updated [documentation](https://github.com/slimndap/wp-theatre/wiki/Shortcodes#event-listing) for examples.
    

* Bugfixes
    * Fixed some layout glitches on the Theater extensions page (0.14.1).
    * Listings were not working correctly if you combined the `start` and `post__not_in` params (0.14.2).
    * Event imports didn't always clean up properly (0.14.2).
    * Fixed a problem when saving a production with multiple events (0.14.3). Thank you [tomaszkoziara](https://github.com/tomaszkoziara)!
    * Fixed several PHP warnings when no listing page is set in the Theater settings (0.14.3).
    
= 0.13 =

Release Date: September 3rd, 2015

* Enhancements
    * Production listings can now be filtered by start and end dates. See the updated [documentation](https://github.com/slimndap/wp-theatre/wiki/Shortcodes#production-listing).
    * Production listings can now be grouped and paginated by day, month or year.
    * Stripped all unnecessary elements off the event edit screen.
    * Made it possible to alter the behaviour of the tickets lightbox with a filter.
    * You can now browse extensions inside the WordPress admin.

See the [release post](https://wp.theater/theater-for-wordpress-0-13-released/?utm_source=wordpress.org&utm_medium=web&utm_content=changelog&utm_campaign=readme.txt) for examples.

* Bugfixes
	* Fixed a problem in production listings with events that start before 1-1-1970.
	* Visibility of ticket buttons didn't account for timezones.
	* Pagination for listings wasn't working when the listing page was the same as the front page.
	* Weekdays were showing up as question marks when using a multibyte language (eg. Russian).

* Requirements
	* The plugin is no longer tested on WordPress versions prior to 4.0 (0.13.3).

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


== Upgrade Notice ==
= 0.15.8 =
Fixes in the HTML output of the Calendar widget and improves the sanitization of ticket urls.

= 0.15.7 =
Fixes two issues with displaying event dates.

= 0.15.6 =
Fixes a problem with updates for Theater for WordPress extension plugins.

= 0.15.5 =
Adds support for a 'tag' filter in event lists. Fixes a problem when bulk publishing events.

= 0.15.4 =
Small bugfixes and improvements to the events admin screen.

= 0.15.3 =
Added a new `{{prices}}` placeholder for productions.

= 0.15.2 =
Adds context information to fields in listings, useful for styling and context-aware hooks.

= 0.15.1 =
Fixes a problem with the 'date' filter in template placeholders.

= 0.15 =
This release focusses on a more streamlined experience when managing your events.

= 0.14.7 = 
Adds context information to listings, useful for styling and context-aware hooks.

= 0.14.6 =
Adds two new filters to manipulate the appearance of events in lists.

= 0.14.5 =
Internal improvements that can be used by importer extensions. No real need to update if you are not importing events from an external ticketing solution.

= 0.14.4 =
The [wpt_events] shortcode now accepts a 'production' parameter to limit the a events list to one or more productions.