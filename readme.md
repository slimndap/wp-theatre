# Website owners

## Events vs. productions

Theatre uses _productions_ to group your _events_. 
Each production has it's own page and can have one or more events. 
Events don't have their own pages. They only appear on pages with event listings.

So if you run a theatre then 'The Sound Of Music' is a _production_ and the show this weekend is an _event_.
If you're a musician then your band is a _production_ and your gigs are the _events_.

## Upcoming events

To add a listing with all upcoming events to your Wordpress website:

1. Create a new blank page (eg. 'Upcoming events').
1. Place `[wp_theatre_events]` in the content.
1. Publish the page and view it.
1. Done!

It is also possible to paginate the listing by month by altering the shortcode a bit:

    [wp_theatre_events paged=1]

### Widgets

Theatre also comes with two widgets to show your upcoming events in the sidebar:

* Theatre Events: a list of upcoming events. 
* Theatre Productions: a list of productions with upcoming events. 

You can limit the size of the lists in the corresponding widget's settings.

## Production pages

Production pages look exactly the same as regular post pages. However, you can add a listing of all the events for the current production to the page. 

You have two options:

* Check 'Show events on production page' on the Theatre settings page in the Wordpress admin. The listing is added to the bottom of the content of the production.
* Add the `[wpt_production_events]` shortcode to the content of the production.
# Theme developers

## Event listings

Theatre comes with two types of event listings:

* A list of all upcoming events.
* A list of upcoming events for a particular production.

### A list of all upcoming events

You can use a [shortcode](Wordpress-users) to show an event listing in a post or a page.

You can also use the [WP_Theatre](WP_Theatre) to add an event listing to your template:

    WP_Theatre::render_events();

Or generate your own listing:

    $events = WP_Theatre::get_events();
    foreach ($events as $event) {
        echo $event->post()->post_title;
        // do other stuff with your event        
    }

### A list of upcoming events for a particular production.

The Wordpress admin already has [some options](Wordpress-users#wiki-production-pages) to add events listings to production pages.

You can also use the [WPT_Production](WPT_Production) class to add an event listing to your singe production template:

    $production = new WPT_Production();
    $production->render_events();

Or generate your own listing:

    $production = new WPT_Production();
    foreach ($productions->get_events as $event) {
        echo $event->post()->post_title;
        // do other stuff with your event
    }


