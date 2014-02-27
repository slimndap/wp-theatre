<?php
class WPT_Productions {

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

	function all($args = array(), $PostClass = false) {
		global $wpdb;
		
		$defaults = array(
			'limit' => false,
			WPT_Season::post_type_name => false,
			'grouped' => false,
			'upcoming' => false
		);
		$args = wp_parse_args( $args, $defaults );

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
			WHERE
				productions.post_type='".WPT_Production::post_type_name."'
				AND	productions.post_status= 'publish'
		";

		if ($args['upcoming']) {
			$querystr.= " AND wpt_startdate.meta_value > NOW()";
		}

		if ($args[WPT_Season::post_type_name]) {
			$querystr.= " AND seasons.post_name='".$args[WPT_Season::post_type_name]."'";
		} else {
			$querystr.= " OR sticky.meta_value = 'on'";
		}

		$querystr.= "
			GROUP BY productions.ID
		";
		
		if($args['grouped']) {
			$querystr.= "
				ORDER BY seasons.post_title DESC, sticky.meta_value DESC, wpt_startdate.meta_value ASC
			";			
		} else {
			$querystr.= "
				ORDER BY sticky.meta_value DESC, wpt_startdate.meta_value ASC
			";						
		}

		if ($args['limit']) {
			$querystr.= ' LIMIT 0,'.$args['limit'];
		}

		$posts = $wpdb->get_results($querystr, OBJECT);
		
		$productions = array();
		for ($i=0;$i<count($posts);$i++) {
			$productions[] = new WPT_Production($posts[$i]->ID, $PostClass);
		}
		return $productions;
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
	public function html_listing($args=array()) {
		global $wpdb;

		$defaults = array(
			'paged' => false,
			'grouped' => false,
			'limit' => false,
			'upcoming' => false,
			WPT_Season::post_type_name => false,
		);
		$args = wp_parse_args( $args, $defaults );

		$productions_args = array(
			'limit' => $args['limit'],
			WPT_Season::post_type_name => $args[WPT_Season::post_type_name],
			'grouped' => $args['grouped']
		);
		
		$html = '';
		$html.= '<div class="wpt_productions">';

		if ($args['paged']) {
			$querystr = "
				SELECT seasons.post_name as title
				FROM $wpdb->posts AS productions
				
				JOIN 
					$wpdb->postmeta AS wpt_season 
					ON (wpt_season.post_ID=productions.ID AND wpt_season.meta_key='".WPT_Season::post_type_name."')
				JOIN 
					$wpdb->posts AS seasons 
					ON seasons.ID = wpt_season.meta_value
				
				WHERE 
					productions.post_status='publish'
					AND productions.post_type='".WPT_Production::post_type_name."'
				
				GROUP BY seasons.ID
				ORDER BY seasons.post_title DESC
			";
			$seasons = $wpdb->get_results($querystr, OBJECT);

			if (!empty($_GET[__('season','wp_theatre')])) {
				$page = $_GET[__('season','wp_theatre')];
			} else {
				$page = $seasons[0]->title;				
			}

			$html.= '<nav>';
			foreach($seasons as $season) {

				$url = remove_query_arg(__('season','wp_theatre'));
				$url = add_query_arg( __('season','wp_theatre'), $season->title , $url);
				$html.= '<span>';

				$title = $season->title;
				if ($season->title != $page) {
					$html.= '<a href="'.$url.'">'.$title.'</a>';
				} else {
					$html.= $title;
					
				}
				$html.= '</span>';
			}
			$html.= '</nav>';
			
			$productions_args[WPT_Season::post_type_name] = $page;
		}

		if ($args['upcoming']) {
			$productions = $this->upcoming($productions_args);
		} else {
			$productions = $this->all($productions_args);			
		}
		

		$production_args = array();
		if (isset($args['fields'])) { $production_args['fields'] = $args['fields']; }
		if (isset($args['hide'])) { $production_args['hide'] = $args['hide']; }
		if (isset($args['thumbnail'])) { $production_args['thumbnail'] = $args['thumbnail']; }

		$group = '';
		foreach ($productions as $production) {
			if ($args['grouped']) {
				if ($season = $production->season()) {
					$title = $season->post()->post_title;
				} else {
					$title = __('No season', 'wp_theatre');
				}
				if ($group != $title) {
					$html.= '<h3>'.$title.'</h3>';
					$group = $title;
				}
			}
			$html.=$production->html($production_args);
		}

		$html.= '</div>'; //.wp-theatre_events
		
		return $html;
	}
	
	/**
	 * All upcoming productions.
	 *
	 * Returns an array of all productions that have published events with a startdate in the future.
	 * 
	 * Example:
	 *
	 * $productions = $wp_theatre->productions->upcoming();
	 *
	 * @since 0.4
	 *
	 * @param array $args {
	 *     An array of arguments. Optional.
	 *
	 *     @type int $wp_theatre_season Only return production that are linked to season <$wp_theatre_season>. Default <false>.
	 *     @type bool $grouped Order the list by season, so it can be grouped later. Default <false>.
	 *     @type int $limit Limit the list to $limit productions. Use <false> for an unlimited list. Default <false>.
	 * }
	 * @return mixed An array of WPT_Production objects.
	 */

	function upcoming($args = array(), $PostClass = false) {
		$args['upcoming'] = true;
		return $this->all($args, $PostClass);
	}
		
}
?>