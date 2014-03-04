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
				$categories[$cat->slug] = $cat->name;
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
			'season' => false
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
		global $wpdb;

		$defaults = array(
			'limit' => false,
			'upcoming' => false,
			'season' => false,
			'paginateby' => array(),
			'groupby' => false

		);
		$args = wp_parse_args( $args, $defaults );
		
		// translate deprecated 'paged' argument
		if (!empty($args['paged']) && !in_array('season', $args['paginateby'])) {
			$args['paginateby'][] ='season';
		}

		$filters = array(
			'season' => $args['season'],
			'limit' => $args['limit'],
			'upcoming' => $args['upcoming']
		);

		$classes = array();
		$classes[] = "wpt_productions";

		// Thumbnail
		if (!$args['thumbnail']) {
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

		if (in_array('category',$args['paginateby'])) {
			$categories = $this->categories();

			$page = '';
			if (!empty($_GET[__('category','wp_theatre')])) {
				if ($category = get_category_by_slug($_GET[__('category','wp_theatre')])) {
		  			$filters['category'] = $category->term_id;				
				}
			}
			
			$html.= '<nav class="wpt_event_categories">';

			$html.= '<span>';
			if (empty($filters['category'])) {
				$html.= __('All','wp_theatre').' '.__('categories','wp_theatre');
			} else {				
				$url = remove_query_arg(__('category','wp_theatre'));
				$html.= '<a href="'.$url.'">'.__('All','wp_theatre').' '.__('categories','wp_theatre').'</a>';
			}
			$html.= '</span>';
			
			foreach($categories as $slug=>$name) {
				$url = remove_query_arg(__('category','wp_theatre'));
				$url = add_query_arg( __('category','wp_theatre'), $slug , $url);
				$html.= '<span>';
				
				if ($slug != $_GET[__('category','wp_theatre')]) {
					$html.= '<a href="'.$url.'">'.$name.'</a>';
				} else {
					$html.= $name;
					
				}
				$html.= '</span>';
			}
			$html.= '</nav>';
		}

		$production_args = array();
		if (isset($args['fields'])) { $production_args['fields'] = $args['fields']; }
		if (isset($args['hide'])) { $production_args['hide'] = $args['hide']; }
		if (isset($args['thumbnail'])) { $production_args['thumbnail'] = $args['thumbnail']; }

		switch ($args['groupby']) {
			case 'season':
				if (!in_array('season', $args['paginateby'])) {
					$seasons = $this->seasons();
					
					foreach($seasons as  $slug=>$season) {
						$filters['season'] = $slug;
						$productions = $this->get($filters);
						if (!empty($productions)) {
							$html.= '<h3>'.$season->title().'</h3>';
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
							$html.= '<h3>'.$name.'</h3>';
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

		$filters = wp_parse_args( $filters, $this->defaults() );

		$value_parameters = array();

		$querystr = "
			SELECT productions.ID FROM $wpdb->posts AS productions
			
			LEFT OUTER JOIN 
				$wpdb->postmeta AS wpt_events 
				ON (wpt_events.meta_value = productions.ID AND wpt_events.meta_key='".WPT_Production::post_type_name."')
			LEFT OUTER JOIN 
				$wpdb->posts AS events 
				ON (events.ID = wpt_events.post_ID AND events.post_status='publish')
			LEFT OUTER JOIN 
				$wpdb->postmeta AS wpt_startdate 
				ON (
					wpt_startdate.post_ID = events.ID AND wpt_startdate.meta_key='event_date'
					AND wpt_startdate.meta_value > NOW()
				)
			LEFT OUTER JOIN 
				$wpdb->postmeta AS wpt_season 
				ON (wpt_season.post_ID=productions.ID AND wpt_season.meta_key='".WPT_Season::post_type_name."')
			LEFT OUTER JOIN 
				$wpdb->posts AS seasons 
				ON seasons.ID = wpt_season.meta_value	
			LEFT OUTER JOIN 
				$wpdb->postmeta AS sticky ON (
					productions.ID = sticky.post_ID
					AND sticky.meta_key = 'sticky'
					AND sticky.meta_value = 'on'
				)
			LEFT OUTER JOIN 
				$wpdb->term_relationships AS term_relationships ON 
					productions.ID = term_relationships.object_id
			LEFT OUTER JOIN 
				$wpdb->term_taxonomy AS categories ON 
					term_relationships.term_taxonomy_id = categories.term_taxonomy_id
			WHERE
				productions.post_type='".WPT_Production::post_type_name."'
				AND	productions.post_status= 'publish'
		";

		if ($filters['upcoming']) {
			$querystr.= ' AND wpt_startdate.meta_value > NOW()';
		}

		if ($filters['season']) {
			$querystr.= ' AND seasons.post_name=%s';
			$value_parameters[] = $filters['season'];
		}
		
		if ($filters['category']) {
			$querystr.= ' AND term_id = %d';
			$value_parameters[] = $filters['category'];
		}
		
		if (!$filters['season'] && !$filters['category']) {
			$querystr.= " OR sticky.meta_value = 'on'";
		}

		$querystr.= "
			GROUP BY productions.ID
		";
		
		$querystr.= "ORDER BY sticky.meta_value DESC, wpt_startdate.meta_value ASC";						

		if ($filters['limit']) {
			$querystr.= ' LIMIT 0,%d';
			$value_parameters[] = $filters['limit'];
		}

		$querystr = $wpdb->prepare($querystr,$value_parameters);

		$posts = $wpdb->get_results($querystr, OBJECT);
		
		$productions = array();
		for ($i=0;$i<count($posts);$i++) {
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