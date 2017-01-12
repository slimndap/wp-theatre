<?php
	/**
	 * Add feeds for events and productions.
	 *
	 * Adds two new feeds: upcoming_productions and upcoming_events.
	 * Adds a link to both feeds, plus the productions archive feed to header.
	 *
	 * @since 0.7
	 *
	 */
	class WPT_Feeds {
		function __construct() {
			add_action('init', array($this,'init'));
			add_action('wp_head', array($this,'wp_head'));
		}
				
		function init() {
			add_feed('upcoming_productions',array($this,'upcoming_productions'));
			add_feed('upcoming_events',array($this,'upcoming_events'));
		}
			
		function wp_head() {
			$feed = get_post_type_archive_feed_link(WPT_Production::post_type_name);
			$html = array();
			
			$html[] = '<link rel="alternate" type="application/rss+xml" title="'.__('New productions','theatre').'" href="'.$feed.'" />';
			$html[] = '<link rel="alternate" type="application/rss+xml" title="'.__('Upcoming productions','theatre').'" href="'.site_url('/upcoming_productions').'" />';
			$html[] = '<link rel="alternate" type="application/rss+xml" title="'.__('Upcoming events','theatre').'" href="'.site_url('/upcoming_events').'" />';
			echo implode("\n",$html)."\n";
		}
		
		function upcoming_productions() {			
			header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);
			$xml = new DomDocument;
			$xml->loadXML($this->get_upcoming_productions());
			echo $xml->saveXML();
		}
		
		/**
		 * Gets the XML for upcoming productions.
		 * 
		 * @since	0.7
		 * @since	0.15.16	Added escaping to production title and permalink.
		 *					Fixes #220.
		 * @return	string
		 */
		function get_upcoming_productions() {
			global $wp_theatre;
	
			$args = array(
				'upcoming'=>true
			);		
			$productions = $wp_theatre->productions->get($args);
			
			$items = '';
			foreach ($productions as $production) {
				$item = '';
				$item.= '<item>';
				$item.= '<title>'.convert_chars($production->title()).'</title>';
				$item.= '<link>'.convert_chars(get_permalink($production->ID)).'</link>';
				$item.= '<description><![CDATA['.$production->summary().']]></description>';
				$item.= '<content:encoded><![CDATA['.strip_shortcodes($production->post()->post_content).']]></content:encoded>';
				$item.= '</item>';

				$items.=$item;				
			}
			
			
			$feed = '';
			$feed.=	'<rss version="2.0"	xmlns:content="http://purl.org/rss/1.0/modules/content/">';

			$feed.= '<channel>';
			$feed.= '<title>'.get_bloginfo_rss('name').' '.__('Upcoming productions','theatre').'</title>';
			$feed.= '<link>'.get_bloginfo_rss('url').'</link>';
			$feed.= '<description>'.get_bloginfo_rss("description").'</description>';

			$feed.= $items;

			$feed.= '</channel>';

			$feed.=	'</rss>';

			return $feed;

		}
		
		function upcoming_events() {
			header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);
			$xml = new DomDocument;
			$xml->loadXML($this->get_upcoming_events());
			echo $xml->saveXML();
		}
		
		/**
		 * Gets the XML for upcoming events.
		 * 
		 * @since	0.7
		 * @since	0.15.16	Added escaping to production title and permalink.
		 *					Fixes #220.
		 * @return	string
		 */
		function get_upcoming_events() {
			global $wp_theatre;
	
			$args = array(
				'upcoming'=>true
			);		
			$events = $wp_theatre->events->get($args);
			
			$items = '';
			foreach ($events as $event) {
				$item = '';
				$item.= '<item>';
				$item.= '<title>'.convert_chars($event->production()->title()).'</title>';
				$item.= '<link>'.convert_chars(get_permalink($event->production()->ID)).'</link>';
				$item.= '<description><![CDATA['.$event->production()->summary().']]></description>';
				$item.= '<content:encoded><![CDATA['.strip_shortcodes($event->production()->post()->post_content).']]></content:encoded>';
				$item.= '</item>';

				$items.=$item;				
			}
			
			$feed = '';
			$feed.=	'<rss version="2.0"	xmlns:content="http://purl.org/rss/1.0/modules/content/">';

			$feed.= '<channel>';
			$feed.= '<title>'.get_bloginfo_rss('name').' '.__('Upcoming events','theatre').'</title>';
			$feed.= '<link>'.get_bloginfo_rss('url').'</link>';
			$feed.= '<description>'.get_bloginfo_rss("description").'</description>';

			$feed.= $items;

			$feed.= '</channel>';

			$feed.=	'</rss>';

			return $feed;
		}
		
		
	}
?>