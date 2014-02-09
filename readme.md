#Theatre for Wordpress

## Events vs. productions

Theatre uses _productions_ to group your individual _events_. 
Each production has it's own page and can have one or more events. 
Events don't have their own pages. They only appear on pages with event listings.

So if you run a theatre then 'The Sound Of Music' is a _production_ and the show this weekend is an _event_.
If you're a musician then your band is a _production_ and your gigs are the _events_.

## Website owners

### Installation

1. Look for 'theatre' in the Wordpress plugin directory.
1. Install the Theatre plugin (by Jeroen Schmit, Slim & Dapper).
1. Activate the plugin.

Your Wordpress admin screen now has a new menu-item in the left column: Theatre.

### Managing your events

Let's add a single event.

Make sure that the _Show events on production page._-option is checked on the _Theatre/Settings_ page.

First create a production:

1. Click on _Theatre/Productions_.
1. Click on _Add new_.
1. Give your production a title, some content and a featured image.
1. Click on _Publish_.
1. Click on _View post_

You are now looking at your first production. It probably looks exactly like any other post or page.

1. Edit the event you just created.
1. In the right column, click on 'New event'.
1. Set the event date, venue and city. Make sure the event date is a date in the future. Optionally, add an URL for the tickets. 
1. Click on _Publish_.
1. Click on the title of your production.
1. Click on _View post_

Your should now see your production with the event details at the bottom!

### Upcoming events

To add a listing with all upcoming events to your Wordpress website:

1. Create a new blank page (eg. 'Upcoming events').
1. Place `[wp_theatre_events]` in the content.
1. Publish the page and view it.
1. Done!

It is also possible to paginate the listing by month by altering the shortcode a bit:

    [wp_theatre_events paged=1]

#### Widgets

Theatre also comes with two widgets to show your upcoming events in the sidebar:

* Theatre Events: a list of upcoming events. 
* Theatre Productions: a list of productions with upcoming events. 

You can limit the size of the lists in the corresponding widget's settings.

### Production pages

Production pages look exactly the same as regular post pages. However, you can add a listing of all the events for the current production to the page. 

You have two options:

* Check 'Show events on production page' on the Theatre settings page in the Wordpress admin. The listing is added to the bottom of the content of the production.
* Add the `[wpt_production_events]` shortcode to the content of the production.

## Theme developers

### Event listings

Theatre comes with two types of event listings:

* A list of all upcoming events.
* A list of upcoming events for a particular production.

#### A list of all upcoming events

You can use a [shortcode](#upcoming-events) to show an event listing in a post or a page.

You can also use the [WP_Theatre](theatre.php) class to add an event listing to your template:

```PHP
WP_Theatre::render_events();
```

Or generate your own listing:

```PHP
$events = WP_Theatre::get_events();
foreach ($events as $event) {
    echo $event->post()->post_title;
    // do other stuff with your event        
}
```

#### A list of upcoming events for a particular production.

The Wordpress admin already has [some options](#production-pages) to add events listings to production pages.

You can also use the [WPT_Production](functions/wpt_production.php) class to add an event listing to your production template:

```PHP
$production = new WPT_Production();
$production->render_events();
```

Or generate your own listing:

```PHP
$production = new WPT_Production();
foreach ($productions->get_events as $event) {
    echo $event->post()->post_title;
    // do other stuff with your event
}
```


## Integrations

### Ticketing

Theatre integrates nicely with existing Ticketing solutions. Available integrations:

* [Ticketmatic](http://slimndap.com/product/ticketmatic-voor-wordpress/)

Need integration with another ticketing system? Drop me an [email](mailto:jeroen@slimndap.com) and I will look into it.
