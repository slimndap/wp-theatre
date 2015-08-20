<?php
/*
 * Manages production listings.
 *
 * Uses this class to compile lists of productions or fully formatted HTML listings of productions.
 *
 * @since 0.5
 * @since 0.10	Complete rewrite, while maintaining backwards compatibility.
 */
 
class WPT_Productions extends WPT_Listing {

	/**
	 * Adds the page selectors for seasons and categories to the public query vars.
	 *
	 * This is needed to make `$wp_query->query_vars['wpt_category']` work.
	 *
	 * @since 0.10
	 *
	 * @param array $vars	The current public query vars.
	 * @return array		The new public query vars.
	 */
	public function add_query_vars($vars) {
		$vars[] = 'wpt_season';
		$vars[] = 'wpt_category';
		return $vars;
	}

	/**
	 * Gets an array of all categories with productions.
	 *
	 * @since 0.5
	 * @since 0.10		Renamed method from `categories()` to `get_categories()`.
 	 * @since 0.10.2	Now returns the slug instead of the term_id as the array keys.
 	 * @since 0.10.14	Significally decreased the number of queries used.
	 *
	 * @param 	array $filters	See WPT_Productions::get() for possible values.
	 * @return 	array 			Categories.
	 */
	function get_categories() {
		$productions = $this->get();
		$production_ids = wp_list_pluck($productions, 'ID');
		$terms = wp_get_object_terms($production_ids, 'category');
		
		$categories = array();

		foreach ($terms as $term) {
			$categories[$term->slug] = $term->name;
		}
		
		asort($categories);
		return $categories;		
	}

	/**
	 * Gets the CSS classes for a production listing.
	 *
	 * @see WPT_Listing::get_classes_for_html()
	 *
	 * @since 0.10
	 * 
	 * @access 	protected
	 * @param 	array $args 	See WPT_Productions::get_html() for possible values. Default: array().
	 * @return 	array 			The CSS classes.
	 */
	protected function get_classes_for_html($args=array()) {

		// Start with the default classes for listings.
		$classes = parent::get_classes_for_html();

		$classes[] = 'wpt_productions';
		
		// Thumbnail
		if (!empty($args['template']) && strpos($args['template'],'{{thumbnail}}')===false) { 
			$classes[] = 'wpt_productions_without_thumbnail';
		}
			
		/**
		 * Filter the CSS classes.
		 * 
		 * @since 0.10
		 *
		 * @param 	array $classes 	The CSS classes.
		 * @param 	array $args 	The $args that are being used for the listing.
		 */
		$classes = apply_filters('wpt_productions_classes', $classes, $args);
		
		return $classes;
	}
	
	/**
	 * Gets a list of productions in HTML for a page.
	 * 
	 * @since 0.10
	 *
	 * @see WPT_Productions::get_html_grouped();
	 * @see WPT_Productions::get_html_for_season();
	 * @see WPT_Productions::get_html_for_category();
	 *
	 * @access protected
	 * @param 	array $args 	See WPT_Productions::get_html() for possible values.
	 * @return 	string			The HTML.
	 */
	protected function get_html_for_page($args=array()) {
		global $wp_query;
		
		/*
		 * Check if the user used the page navigation to select a particular page.
		 * Then revert to the corresponding WPT_Events::get_html_for_* method.
		 * @see WPT_Events::get_html_page_navigation().
		 */
		 
		if (!empty($wp_query->query_vars['wpt_season']))
			return $this->get_html_for_season($wp_query->query_vars['wpt_season'], $args);
			
		if (!empty($wp_query->query_vars['wpt_category'])) 
			return $this->get_html_for_category($wp_query->query_vars['wpt_category'], $args);
			
		/*
		 * The user didn't select a page.
		 * Show the full listing.
		 */
		return $this->get_html_grouped($args);
	}

	/**
	 * Gets a list of events in HTML for a single season.
	 * 
	 * @since 0.10
	 *
	 * @see WPT_Productions::get_html_grouped();
	 *
	 * @access private
	 * @param 	int $season_id	ID of the season.
	 * @param 	array $args 	See WPT_Productions::get_html() for possible values.
	 * @return 	string			The HTML.
	 */
	private function get_html_for_season($season_id, $args=array()) {
		$args['season'] = $season_id;				
		return $this->get_html_grouped($args);
	}
	
	/**
	 * Gets a list of productions in HTML.
	 * 
	 * The productions can be grouped inside a page by setting $groupby.
	 * If $groupby is not set then all productions are show in a single, ungrouped list.
	 *
	 * @since 0.10
	 *
	 * @see WPT_Production::html();
	 * @see WPT_Productions::get_html_for_season();
	 * @see WPT_Productions::get_html_for_category();
	 *
	 * @access 	protected
	 * @param 	array $args 	See WPT_Productions::get_html() for possible values.
	 * @return 	string			The HTML.
	 */
	private function get_html_grouped($args=array()) {

		$args = wp_parse_args($args, $this->default_args_for_html);
		
		/*
		 * Get the `groupby` setting and remove it from $args.
		 * $args can now be passed on to any of the other `get_html_*`-methods safely
		 * without the risk of creating grouped listings within grouped listings.
		 */
		$groupby = $args['groupby'];
		$args['groupby'] = false;

		$html = '';
		switch ($groupby) {
			case 'season':
				$seasons = $this->get_seasons($args);
				foreach($seasons as $season_id=>$season_title) {
					if ($season_html = $this->get_html_for_season($season_id, $args)) {
						$html.= '<h3 class="wpt_listing_group season">';
						$html.= apply_filters('wpt_listing_group_season',$season_title,$season_id);
						$html.= '</h3>';
						$html.= $season_html;
					}
				}
				break;					
			case 'category':
				$categories = $this->get_categories($args);
				foreach($categories as $cat_id=>$name) {
					if ($cat_html = $this->get_html_for_category($cat_id, $args)) {
						$html.= '<h3 class="wpt_listing_group category">';
						$html.= apply_filters('wpt_listing_group_category',$name,$cat_id);
						$html.= '</h3>';
						$html.= $cat_html;						
					}
				}
				break;					
			default:
				/*
				 * No stickies in paginated or grouped views
				 */
				if (
					!empty($args['paginateby']) || 
					!empty($args['groupby'])
				) {
					$args['ignore_sticky_posts'] = true;	
				}
		
				$productions = $this->get($args);
				$productions = $this->preload_productions_with_events($productions);
				$html_group = '';
				foreach ($productions as $production) {
					$production_args = array();
					if (!empty($args['template'])) {
						$production_args = array('template'=>$args['template']);
					}
					$html_group.= $production->html($production_args);
				}
				
				/**
				 * Filter the HTML for a group in a listing.
				 * 
				 * @since	0.12.7
				 * @param	string	$html_group	The HTML for this group.
				 * @param	array	$args		The arguments for the HTML of this listing.
				 */
				$html_group = apply_filters('wpt/productions/html/grouped/group', $html_group, $args);
				
				$html.= $html_group;
										
		}
		return $html;
	}

	/**
	 * Gets a fully formatted listing of productions in HTML.
	 *
	 * The list of productions is compiled using filter-arguments that are part of $args.
	 * See WPT_Productions::get() for possible values.
	 *
	 * The productions can be shown on a single page or be cut up into multiple pages by setting
	 * $paginateby. If $paginateby is set then a page navigation is added to the top of
	 * the listing.
	 *
	 * The productions can be grouped inside the pages by setting $groupby.
	 * 
	 * @since 0.5
	 * @since 0.10	Moved parts of this method to seperate reusable methods.
	 *				Renamed method from `html()` to `get_html()`.
	 *				Rewrote documentation.
	 *
	 * @see WPT_Listing::get_html()
	 * @see WPT_Productions::get_html_pagination()
	 * @see WPT_Productions::get_html_for_page()
	 *
	 * @param array $args {
	 * 		An array of arguments. Optional.
	 *
	 *		These can be any of the arguments used in the $filters of WPT_Productions::get(), plus:
	 *
	 *		@type array		$paginateby	Fields to paginate the listing by.
	 *									@see WPT_Productions::get_html_pagination() for possible values.
	 *									Default <[]>.
	 *		@type string	$groupby 	Field to group the listing by. 
	 *									@see WPT_Productions::get_html_grouped() for possible values.
	 *									Default <false>.
	 * 		@type string	$template	Template to use for the individual productions.
	 *									Default <NULL>.
	 * }
 	 * @return string HTML.
	 */
	public function get_html($args=array()) {

		$html = parent::get_html($args);
		
		/**
		 * Filter the formatted listing of productions in HTML.
		 * 
		 * @since 0.10
		 *
		 * @param 	string $html 	The HTML.
		 * @param 	array $args 	The $args that are being used for the listing.
		 */
		$html = apply_filters('wpt_productions_html', $html, $args);

		return  $html;
	}

	/**
	 * Gets a list of productions in HTML for a single category.
	 * 
	 * @since 0.10
 	 * @since 0.10.2	Category now uses slug instead of term_id.
	 *
	 * @see WPT_Productions::get_html_grouped();
	 *
	 * @access private
	 * @param 	string $category_slug	Slug of the category.
	 * @param 	array $args 			See WPT_Productions::get_html() for possible values.
	 * @return 	string					The HTML.
	 */
	private function get_html_for_category($category_slug, $args=array()) {
		if ($category = get_category_by_slug($category_slug)) {
  			$args['cat'] = $category->term_id;				
		}
		
		return $this->get_html_grouped($args);
	}
	
	/**
	 * Gets the page navigation for an event listing in HTML.
	 *
	 * @see WPT_Listing::filter_pagination()
	 * @see WPT_Events::get_days()
	 * @see WPT_Events::get_months()
	 * @see WPT_Events::get_categories()
	 *
	 * @since 0.10
	 * 
	 * @access protected
	 * @param 	array $args 	The arguments being used for the event listing. 
	 *							See WPT_Events::get_html() for possible values.
	 * @return 	string			The HTML for the page navigation.
	 */
	protected function get_html_page_navigation($args=array()) {
		global $wp_query;

		$html = '';

		// Seasons navigation		
		if (
			(!empty($args['paginateby']) && in_array('season', $args['paginateby'])) || 
			!empty($wp_query->query_vars['wpt_season'])
		) {
			$html.= $this->filter_pagination('season', $this->get_seasons($args), $args);
		}

		// Categories navigation		
		if (
			(!empty($args['paginateby']) && in_array('category', $args['paginateby'])) || 
			!empty($wp_query->query_vars['wpt_category'])
		) {
			$html.= $this->filter_pagination('category', $this->get_categories($args), $args);
		}

		return $html;		
	}

	/**
	 * Gets all productions between 'start' and 'end'.
	 * 
	 * @access 	private
	 * @since	0.13
	 * @param 	string 	$start	The start time. Can be anything that strtotime understands.
	 * @param 	string 	$end	The end time. Can be anything that strtotime understands.
	 * @return 	array			The productions.
	 */
	private function get_productions_by_date($start=false, $end=false) {
		global $wp_theatre;
		$productions = array();
		if ($start || $end) {
			$events_args = array(
				'start' => $start,
				'end' => $end,
			);
			$events = $wp_theatre->events->get($events_args);
			
			foreach ($events as $event) {
				$productions[] = $event->production()->ID;
			}
			
			$productions = array_unique($productions);	
		}
		return $productions;
	}

	/**
	 * Gets an array of all categories with productions.
	 *
	 * @since Unknown
	 * @since 0.10	Renamed method from `seasons()` to `get_seasons()`.
	 *
	 * @param 	array $filters	See WPT_Productions::get() for possible values.
	 * @return 	array 			An array of WPT_Season objects.
	 */
	public function get_seasons() {
		$productions = $this->get();
		$seasons = array();
		foreach ($productions as $production) {
			if ($production->season()) {
				$seasons[$production->season()->ID] = $production->season()->title();
			}
		}
		arsort($seasons);
		return $seasons;
	}
	
	/**
	 * Gets a list of productions.
	 * 
	 * @since 	0.5
	 * @since 	0.10	Renamed method from `load()` to `get()`.
	 * 					Added 'order' to $args.
	 * @since	0.13	Support for 'start' and 'end'.
	 *
	 * @param array $args {
	 *		string $order. 			See WP_Query.
	 *		int $season. 			Only return productions that are linked to $season.
	 *		int $limit. 			See WP_Query.
	 *		$post__in. 				See WP_Query.
	 * 		$post__not_in. 			See WP_Query.
	 * 		$cat. 					See WP_Query.
	 * 		$category_name. 		See WP_Query.
	 *  	category__and. 			See WP_Query.
	 * 		category__in. 			See WP_Query.
	 * 		category__not_in. 		See WP_Query.
	 * 		ignore_sticky_posts. 	See WP_Query.
	 * }
	 * @return array 	An array of WPT_Production objects.
	 */
	public function get($filters=array()) {
		global $wp_theatre;

		$defaults = array(
			'order' => 'asc',
			'limit' => false,
			'post__in' => false,
			'post__not_in' => false,
			'upcoming' => false,
			'start' => false,
			'end' => false,
			'cat' => false,
			'category_name' => false,
			'category__and' => false,
			'category__in' => false,
			'category__not_in' => false,
			'season' => false,
			'ignore_sticky_posts' => false
		);
		$filters = wp_parse_args( $filters, $defaults );

		$args = array(
			'post_type' => WPT_Production::post_type_name,
			'post_status' => 'publish',
			'meta_query' => array(),
			'order' => $filters['order'],
		);
		
		if ($filters['post__in']) {
			$args['post__in'] = $filters['post__in'];
		}
		
		if ($filters['post__not_in']) {
			$args['post__not_in'] = $filters['post__not_in'];
		}
		
		if ($filters['season']) {
			$args['meta_query'][] = array (
				'key' => WPT_Season::post_type_name,
				'value' => $filters['season'],
				'compare' => '='
			);
		}
		
		if ($filters['cat']) {
			$args['cat'] = $filters['cat'];
		}
		
		if ($filters['category_name']) {
			$args['category_name'] = $filters['category_name'];
		}
		
		if ($filters['category__and']) {
			$args['category__and'] = $filters['category__and'];
		}
		
		if ($filters['category__in']) {
			$args['category__in'] = $filters['category__in'];
		}
		
		if ($filters['category__not_in']) {
			$args['category__not_in'] = $filters['category__not_in'];
		}
		
		if ($filters['limit']) {
			$args['posts_per_page'] = $filters['limit'];
		} else {
			$args['posts_per_page'] = -1;
		}

		if (
			$filters['upcoming'] &&
			!$filters['start'] &&
			!$filters['end']
		) {
			$filters['start'] = 'now';
		}

		if ($filters['start'] || $filters['end']) {
			$productions_by_date = $this->get_productions_by_date($filters['start'], $filters['end']);
			if (empty($args['post__in'])) {
				$args['post__in'] = $productions_by_date;							
			} else {
				$args['post__in'] = array_intersect(
					$args['post__in'], 
					$productions_by_date
				);
			}
		}

		/**
		 * Filter the $args before doing get_posts().
		 *
		 * @since 0.9.2
		 *
		 * @param array $args The arguments to use in get_posts to retrieve productions.
		 */
		$args = apply_filters('wpt_productions_load_args',$args);
		$args = apply_filters('wpt_productions_get_args',$args);

		$posts = get_posts($args);

		/*
		 * Add sticky productions.
		 * Unless in filtered, paginated or grouped views.
		 */
		if (
			!$filters['ignore_sticky_posts'] &&
			empty($args['cat']) &&
			empty($args['category_name']) &&
			empty($args['category__and']) &&
			empty($args['category__in']) &&
			!$filters['post__in'] &&
			!$filters['season'] &&
			$args['posts_per_page'] < 0
		) {
			$sticky_posts = get_option( 'sticky_posts' );

			if (!empty($sticky_posts)) {
				$sticky_offset = 0;

				foreach($posts as $post) {
					if (in_array($post->ID,$sticky_posts)) {
						$offset = array_search($post->ID, $sticky_posts);
						unset($sticky_posts[$offset]);
					}
				}

				if (!empty($sticky_posts)) {
					/*
					 * Respect $args['post__not_in']. Remove them from $sticky_posts.
					 * We can't just add it to the `$sticky_args` below, because
					 * `post__not_in` is overruled by `post__in'.
					 */
					if (!empty($args['post__not_in'])) {
						foreach ($args['post__not_in'] as $post__not_in_id) {
							if (in_array($post__not_in_id,$sticky_posts)) {
								$offset = array_search($post__not_in_id, $sticky_posts);
								unset($sticky_posts[$offset]);
							}
						}
					}

					/*
					 * Continue if there are any $sticky_posts left.
					 */
					if (!empty($sticky_posts)) {						
						$sticky_args = array(
							'post__in' => $sticky_posts,
							'post_type' => WPT_Production::post_type_name,
							'post_status' => 'publish',
							'nopaging' => true
						);
						
						/*
						 * Respect $args['category__not_in'].
						 */
						if (!empty($args['category__not_in'])) {
							$sticky_args['category__not_in'] = $args['category__not_in'];
						}
						
						$stickies = get_posts($sticky_args);
						foreach ( $stickies as $sticky_post ) {
							array_splice( $posts, $sticky_offset, 0, array( $sticky_post ) );
							$sticky_offset++;
						}			                              
					}

				}
			}
		}
		
		$productions = array();
		for ($i=0;$i<count($posts);$i++) {
			$key = $posts[$i]->ID;
			$productions[] = new WPT_Production($posts[$i]->ID);
		}
		
		
		return $productions;
	}
	
	/**
	 * Preloads productions with their events.
	 *
	 * Sets the events of a each production in a list of productions with a single query.
	 * This dramatically decreases the number of queries needed to show a listing of productions.
	 * 
	 * @since 	0.10.14
	 * @access 	private
	 * @param 	array	$productions	An array of WPT_Production objects.
	 * @return 	array					An array of WPT_Production objects, with the events preloaded.
	 */
	private function preload_productions_with_events($productions) {
		global $wp_theatre;
		$production_ids = array();
		
		foreach ($productions as $production) {
			$production_ids[] = $production->ID;			
		}
		
		$production_ids = array_unique($production_ids);
		
		$events = get_posts(
			array(
				'post_type' => WPT_Event::post_type_name,
				'posts_per_page' => -1,
				'post_status' => 'publish',
				'meta_query' => array (
					array (
						'key' => WPT_Production::post_type_name,
						'value' => array_unique($production_ids),
						'compare' => 'IN',
					),
					array(
						'key' => $wp_theatre->order->meta_key,
						'value' => time(),
						'compare' => '>='						
					)
				),
				'order' => 'ASC',
			)	
		);
		
		$productions_with_keys = array();
		
		foreach ($events as $event) {
			$production_id = get_post_meta( $event->ID, WPT_Production::post_type_name, true );
			if (!empty($production_id)) {
				$productions_with_keys[$production_id][] = new WPT_Event($event);
			}
		}
		
		for ($i=0; $i<count($productions);$i++) {
			if (in_array($productions[$i]->ID, array_keys($productions_with_keys)) ) {
				$productions[$i]->events = $productions_with_keys[$productions[$i]->ID];
			}
		}
		 return $productions;
	}

	/**
	 * @deprecated 0.10
	 * @see WPT_Productions::get_categories()
	 */
	public function categories($filters=array()) {
		_deprecated_function('WPT_Productions::categories()', '0.10', 'WPT_Productions::get_categories()');
		return $this->get_categories($filters);
	}
	
	/**
	 * @deprecated 0.10
	 * @see WPT_Productions::get_seasons()
	 */
	public function seasons($filters=array()) {
		_deprecated_function('WPT_Productions::get_seasons()', '0.10', 'WPT_Productions::get_seasons()');
		return $this->get_seasons($filters);
	}
	
		
}
?>