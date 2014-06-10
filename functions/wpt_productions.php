<?php
class WPT_Productions extends WPT_Listing {

	/**
	 * Set month and category filters from GET parameters.
	 * @since 0.5
	 */
	function plugins_loaded() {
		if (!empty($_GET[__('season','wp_theatre')])) {
			$this->filters['season'] = $_GET[__('season','wp_theatre')];
		}		
	}

	/**
	 * An array of all categories with upcoming productions.
	 * @since 0.5
	 */
	function categories() {
		$productions = $this->get();		
		$categories = array();
		foreach ($productions as $production) {
			$post_categories = wp_get_post_categories( $production->ID );
			foreach($post_categories as $c){
				$cat = get_category( $c );
				$categories[$cat->term_id] = $cat->name;
			}
		}
		asort($categories);
		
		return $categories;
		
	}

	function defaults() {
		return array(
			'limit' => false,
			'upcoming' => false,
			'category' => false,
			'season' => false,
			'ignore_sticky_posts' => false
		);

	}
	
	/**
	 * A list of productions in HTML.
	 *
	 * Compiles a list of all productions and outputs the result to the browser.
	 * 
	 * Example:
	 *
	 * $args = array('paged'=>true);
	 * $wp_theatre->production->html_listing($args); // a list of all upcoming productions, paginated by season
	 *
	 * @since 0.3.5
	 *
	 * @param array $args {
	 *     An array of arguments. Optional.
	 *
	 *     @type int $wp_theatre_season Only return production that are linked to season <$wp_theatre_season>. Default <false>.
	 *     @type bool $paged Paginate the list by season. Default <false>.
	 *     @type bool $grouped Group the list by season. Default <false>.
	 *     @type bool $upcoming Only include productions with upcoming events. Default <false>.
	 *     @type int $limit Limit the list to $limit productions. Use <false> for an unlimited list. Default <false>.
	 * }
 	 * @return string HTML.
	 */
	public function html($args=array()) {
		global $wp_query;

		$defaults = array(
			'limit' => false,
			'upcoming' => false,
			'category' => false,
			'season' => false,
			'paginateby' => array(),
			'groupby' => false,
			'template' => NULL

		);
		$args = wp_parse_args( $args, $defaults );
		
		$filters = array(
			'category' => $args['category'],
			'season' => $args['season'],
			'limit' => $args['limit'],
			'upcoming' => $args['upcoming']
		);

		$classes = array('wpt_listing','wpt_productions');

		// Thumbnail
		if (!empty($args['template']) && strpos($args['template'],'{{thumbnail}}')===false) { 
			$classes[] = 'wpt_productions_without_thumbnail';
		}

		$html = '';

		if (in_array('season',$args['paginateby'])) {
			$seasons = $this->seasons();

			if (!empty($_GET[__('season','wp_theatre')])) {
				$filters['season'] = $_GET[__('season','wp_theatre')];
			} else {
				$slugs = array_keys($seasons);
				$filters['season'] = $slugs[0];				
			}

			$html.= '<nav>';
			foreach($seasons as $slug=>$season) {

				$url = remove_query_arg(__('season','wp_theatre'));
				$url = add_query_arg( __('season','wp_theatre'), $slug , $url);
				$html.= '<span>';

				$title = $season->title();
				if ($slug == $filters['season']) {
					$html.= $title;
				} else {
					$html.= '<a href="'.$url.'">'.$title.'</a>';					
				}
				$html.= '</span>';
			}
			$html.= '</nav>';
		}

		/*
		 * Categories navigation
		 */
		$html.= $this->filter_pagination('category', $this->categories($filters), $args);
		
		/*
		 * No stickies in filtered, paginated or grouped views
		 */
		if (
			!empty($args['paginateby']) || 
			!empty($args['groupby']) ||
			!empty($args['category']) ||
			!empty($args['season'])
		) {
			$filters['ignore_sticky_posts'] = true;	
		}
		
		$production_args = array();
		if (isset($args['template'])) { $production_args['template'] = $args['template']; }

		switch ($args['groupby']) {
			case 'season':
				if (!in_array('season', $args['paginateby'])) {
					$seasons = $this->seasons();
					foreach($seasons as  $title=>$season) {
						$filters['season'] = $season->ID;
						$productions = $this->get($filters);
						if (!empty($productions)) {
							$html.= '<h3 class="wpt_listing_group season">'.$season->title().'</h3>';
							foreach ($productions as $production) {
								$html.=$production->html($production_args);							
							}
						}
					}
					break;					
				}
			case 'category':
				if (!in_array('category', $args['paginateby'])) {
					$categories = $this->categories();
					foreach($categories as $slug=>$name) {
						if ($category = get_category_by_slug($slug)) {
				  			$filters['category'] = $category->term_id;				
						}
						$productions = $this->get($filters);
						if (!empty($productions)) {
							$html.= '<h3 class="wpt_listing_group category">'.$name.'</h3>';
							foreach ($productions as $production) {
								$html.=$production->html($production_args);							
							}							
						}
					}
					break;					
				}
			default:
				$productions = $this->get($filters);
				foreach ($productions as $production) {
					$html.=$production->html($production_args);							
				}
		}

		$html = '<div class="'.implode(' ',$classes).'">'.$html.'</div>'; 
		
		return $html;
	}
	
	/**
	 * All productions.
	 *
	 * Returns an array of all productions.
	 * 
	 * Example:
	 *
	 * $productions = $wp_theatre->productions->all();
	 *
	 * @since 0.4
	 *
	 * @param array $args {
	 *     An array of arguments. Optional.
	 *
	 *     @type int $wp_theatre_season Only return production that are linked to season <$wp_theatre_season>. No additional sticky productions get added. Default <false>.
	 *     @type bool $grouped Order the list by season, so it can be grouped later. Default <false>.
	 *     @type bool $upcoming Only show productions with upcoming events. Plus sticky productions. Default <false>.
	 *     @type int $limit Limit the list to $limit productions. Use <false> for an unlimited list. Default <false>.
	 * }
	 * @return mixed An array of WPT_Production objects.
	 */

	function load($filters=array()) {
		global $wpdb;
		global $wp_theatre;

		$filters = wp_parse_args( $filters, $this->defaults() );

		$args = array(
			'post_type' => WPT_Production::post_type_name,
			'post_status' => 'publish',
			'meta_query' => array(),
			'order' => 'asc'
		);
		
		if ($filters['season']) {
			$args['meta_query'][] = array (
				'key' => WPT_Season::post_type_name,
				'value' => $filters['season'],
				'compare' => '='
			);
		}
		
		if ($filters['category']) {
			$args['cat'] = $filters['category'];
		}
		
		if ($filters['limit']) {
			$args['posts_per_page'] = $filters['limit'];
		} elseif (
			!$filters['season'] &&
			!$filters['category']
		) { else {
			$args['posts_per_page'] = -1;
		}

		if ($filters['upcoming']) {
			$args['meta_query'][] = array (
				'key' => $wp_theatre->order->meta_key,
				'value' => time(),
				'compare' => '>='
			);
		}

		$posts = get_posts($args);

		// don't forget the stickies!
		if (!$filters['ignore_sticky_posts']) {
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
					$stickies = get_posts( array(
						'post__in' => $sticky_posts,
						'post_type' => WPT_Production::post_type_name,
						'post_status' => 'publish',
						'nopaging' => true
					) );
					foreach ( $stickies as $sticky_post ) {
						array_splice( $posts, $sticky_offset, 0, array( $sticky_post ) );
						$sticky_offset++;
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
	
	function seasons() {
		$productions = $this->get();
		$seasons = array();
		foreach ($productions as $production) {
			if ($production->season()) {
				$seasons[$production->season()->title()] = $production->season();
			}
		}
		krsort($seasons);
		return $seasons;
	}
	
		
}
?>