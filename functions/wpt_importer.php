<?php
	
	/**
	 * WPT_Importer class.
	 *
	 * Extend this class to write your own importer.
	 * This class is still in development.
	 * Play with it, but don't use it on your production site.
	 * Always make backups!
	 *
	 * @since 	0.10
	 * @since	0.15.24	Events are no longer marked in the database during import.
	 * @since	0.15.27	Added support for preloading previously imported productions and events.
	 */
	class WPT_Importer {
		
		/**
		 * Marked events tracker.
		 * 
		 * @since	0.15.24
		 * @var		array
		 * @access 	private
		 */
		private $marked_events = array();
		
		/**
		 * Preloaded productions tracker.
		 * 
		 * @since	0.15.27
		 * @var 	WPT_Production[]
		 * @access 	private
		 */
		private $productions_by_ref = array();
		
		/**
		 * Preloaded events tracker.
		 * 
		 * @since	0.15.27
		 * @var		WPT_Event[]
		 * @access 	private
		 */
		private $events_by_ref = array();

		protected $data = array();
		protected $stats = array();
		
		/**
		 * Inits the importer.
		 * 
		 * @since	0.10
		 * @since	0.15.19	Added a filter to the arguments.
		 * @since	0.15.20	Removed arguments filter again.
		 *					Use the WPT_Importer::set() method.
		 *					
		 * @uses	WPT_Importer::set() to set the importer properties.
		 *
		 * @param	array	$args
		 * @return void
		 */
		function init($args) {
			
			$defaults = array(
				'slug' => '',
				'name' => '',
				'options' => array(),
				'callbacks' => array(),
			);
			$args = wp_parse_args($args, $defaults);
			
			$this->set( 'slug', $args['slug'] );
			$this->set( 'name', $args['name'] );
			$this->set( 'callbacks', $args['callbacks'] );
			$this->set( 'options', get_option( $this->get('slug') ) );
			$this->set( 'marker', '_'.$this->get('slug').'_marker' );
			$this->set( 'stats', get_option($this->get('slug').'_stats') );
			
			add_action('update_option_'.$this->get('slug'), array($this,'update_options'), 10 ,2);
			add_action('wp_loaded', array( $this, 'handle_import_linked' ));

			add_filter('admin_init',array($this,'add_settings_fields'));
			add_filter('wpt_admin_page_tabs',array($this,'add_settings_tab'));
			add_action($this->get( 'slug' ).'_import', array($this, 'execute' ));
			
			add_action( 'add_meta_boxes', array($this, 'add_production_metabox') );
			add_action(
				'wpt_importer/production/metabox/actions/importer='.$this->get('slug'), 
				array($this, 'add_production_metabox_reimport_button'), 
				10, 
				2
			);
			add_action('wp_loaded', array( $this, 'reimport_production' ));
			
		}
		
		/**
		 * Checks if all requirements for the import are met.
		 *
		 * You should override this method in your child class.
		 * 
		 * @since 0.10
		 *
		 * @access protected
		 * @return bool Returns <true> if all requirements are met. Default: <false>.
		 */
		protected function ready_for_import() {
			return false;
		}
		
		/**
		 * Preloads events by source ref.
		 * 
		 * Use this method to preload all previously imported events during an import.
		 * Doing this directly after reading a feed will add all events that were imported during
		 * previous imports to the @uses WPT_Importer::productions_by_ref property and put all corresponding
		 * meta fields in he WordPress cache.
		 *
		 * @since	0.15.27
		 * @param 	array	$refs	The source refs of events to preload.
		 * @return 	void
		 */
		function preload_events_by_ref( $refs = array() ) {
			
			if ( empty( $refs ) ) {
				return;
			}
			
			$args = array(
				'post_type' => WPT_Event::post_type_name,
				'post_status' => 'any',
				'nopaging' => true,
				'meta_query' => array(
					array(
						'key' => '_wpt_source',
						'value' => $this->get('slug'),
					),
					array(
						'key' => '_wpt_source_ref',
						'value' => $refs,
						'compare' => 'IN',
					),
				),				
			);			

			$posts = get_posts( $args );

			foreach ($posts as $post) {
				$this->events_by_ref[ get_post_meta( $post->ID, '_wpt_source_ref', true) ] = new WPT_Event( $post->ID );
			}
		}

		/**
		 * Preloads productions by source ref.
		 * 
		 * Use this method to preload all previously imported productions during an import.
		 * Doing this directly after reading a feed will add all productions that were imported during
		 * previous imports to the @uses WPT_Importer::productions_by_ref property and put all corresponding
		 * meta fields in he WordPress cache.
		 *
		 * @since	0.15.27
		 * @param 	array	$refs	The source refs of productions to preload.
		 * @return 	void
		 */
		function preload_productions_by_ref( $refs = array() ) {
			
			if ( empty( $refs ) ) {
				return;
			}
			
			$args = array(
				'post_type' => WPT_Production::post_type_name,
				'post_status' => 'any',
				'nopaging' => true,
				'meta_query' => array(
					array(
						'key' => '_wpt_source',
						'value' => $this->get('slug'),
					),
					array(
						'key' => '_wpt_source_ref',
						'value' => $refs,
						'compare' => 'IN',
					),
				),				
			);			

			$posts = get_posts( $args );

			foreach ($posts as $post ) {
				$this->productions_by_ref[ get_post_meta( $post->ID, '_wpt_source_ref', true) ] = new WPT_Production( $post ->ID );
			}
			
		}
		
		/**
		 * Processes the feed for you import.
		 * 
		 * You should override this method in your child class.
		 *
		 * This method does the actual importing and is unique to every importer.
		 * 
		 * @since 0.10
		 *
		 * @see WPT_Importer::execute()
		 *
		 * @access protected
		 * @return bool Returns <true> if the feed is successfully processed. Default: <false>.
		 */
		protected function process_feed() {
			return false;
		}

		
		/**
		 * Outputs the metabox for the importer on the production edit form.
		 * 
		 * The metabox tells the user that the production was imported with the current importer.
		 * It can also hold buttons with action that are specific to this importer.
		 *
		 * @since	0.14.5
		 * @since	0.15.6	Changed 'production' to 'event' in the metabox message.
		 * @param 	WP_Post	$post	The current post (production).
		 */
		function production_metabox($post) {
			ob_start();
			?><p><?php printf(__('This event is imported from %s.', 'theatre'), $this->name); ?></p><?php
			
			$message = ob_get_clean();
			
			/**
			 * Filter the message inside the metabox.
			 * 
			 * @since	0.14.5
			 * @param	string	$message	The message.
			 * @param	WP_Post	$post		The post (production).
			 */
			$message = apply_filters('wpt/importer/production/metabox/message', $message, $post);
			$message = apply_filters('wpt/importer/production/metabox/message/importer='.$this->get('slug'), $message, $post);
			
			echo $message;
			
			$actions = array();
			
			/**
			 * Filter the actions inside the metabox.
			 *
			 * Use this filter to add actions that are specific to this importer.
			 *
			 * Actions use this format:
			 *
			 * 	array(
		     * 		'label' => 'Do something',
		     *		'url' => 'http://slimndap.com',
	         *	);
	         *
	         * Used by @see WPT_Importer::add_production_metabox_reimport_button() to add a button
	         * that re-imports the current production.
			 * 
			 * @since	0.14.5
			 * @param 	array	$actions	The actions.
			 * @param	WP_Post	$post		The post (production).
			 */
			$actions = apply_filters('wpt_importer/production/metabox/actions', $actions, $post);
			$actions = apply_filters('wpt_importer/production/metabox/actions/importer='.$this->get('slug'), $actions, $post);
			
			ob_start();
			?><div class="wpt_importer_production_metabox_actions"><?php
			foreach ($actions as $action) {
				?><a href="<?php echo $action['url'];?>" class="button"><?php echo $action['label'];?></a><?php
			}
			?></div><?php
			echo ob_get_clean();
		}
		
		/**
		 * Adds an error to the importer stats.
		 *
		 * Use this helper function to register an error while processing your feed.
		 * 
		 * @since	0.12.1
		 * @param	string	$error	The error message.
		 * @return	void
		 */
		function add_error($error) {
			if (empty($error)) {
				$error = 'Undefined error.';
			}
			$this->stats['errors'][] = $error;				
		}

		/**
		 * Adds a metabox for this importer to the production edit form.
		 * 
		 * @since	0.14.5
		 * @since	0.15.33	Check if we're actually editing an existing production.
		 *					Fixes #276. 
		 * @param 	string	$post_type	The post type of the current post.
		 */
		function add_production_metabox($post_type) {

			// Bail if the current post is not a production.
			if (WPT_Production::post_type_name!=$post_type) {
				return;
			}

			// Bail when not editing an existing production, but adding a new production.
			if ( empty( $_GET['post'] ) ) {
				return;
			}

			$production_id = intval($_GET['post']);			

			// Show the metabox if the production was imported by this importer.
			$production_metabox_visible = $this->get('slug') == get_post_meta($production_id, '_wpt_source', true);

			/**
			 * Filters the visibility of this metabox.
			 * 
			 * Importers can use this to set their own visibility rules.
			 * 
			 * @since	0.14.5
			 * @param	bool	$production_metabox_visible	Show this metabox?
			 * @param	int		$production_id				The production ID.
			 */
			$production_metabox_visible = apply_filters('wpt/importer/production/metabox/visible', $production_metabox_visible, $production_id);
			$production_metabox_visible = apply_filters('wpt/importer/production/metabox/visible/importer='.$this->get('slug'), $production_metabox_visible, $production_id);
			
			// Bail if visibility is set to false.
			if (!$production_metabox_visible) {
				return;	
			}

			add_meta_box(
                'wpt_importer_'.$this->get('slug'),
                $this->get('name'),
                array( $this, 'production_metabox' ),
                $post_type,
                'side',
                'high'
            );	
            	
        }
        
        /**
         * Adds a re-import action to the metabox on the production edit form.
         * 
         * @since	0.14.5
         * @param 	array	$actions	The current actions inside the metabox.
         * @param 	WP_Post	$post		The post (production).
         * @return	array				The updated actions inside the metabox.
         */
        function add_production_metabox_reimport_button($actions, $post) {
	        
	        $callbacks = $this->get('callbacks');
	        
	        // Bail if no re-import callback is defined.
	        if (empty($callbacks['reimport_production'])) {
		        return $actions;
	        }
	        
	        $url = add_query_arg('wpt_reimport', $this->get('slug'));
	        $url = wp_nonce_url($url, 'wpt_reimport');
	        
	        $actions[] = array(
		        'label' => __('Re-import', 'theatre'),
		        'url' => $url,
	        );
	        
	        return $actions;
        }

		/**
		 * Creates a new event.
		 *
		 * Use this helper function to create a new event while processing your feed.
		 * 
		 * @since 	0.10
		 * @since 	0.11 	Added support for event prices.
		 * @since 	0.11.7	Events now inherit the post_status from the production.
		 *					Fixes https://github.com/slimndap/wp-theatre/issues/129.
		 * @since	0.15.27	The event is now added to @uses WPT_Importer::$events_by_ref after creation.
		 *
		 * @uses	WPT_Importer::get_event_by_ref()
		 * @uses	WPT_Importer::update_event()
		 *
		 * @access protected
		 * @param array $args {
		 *		@type int $production 		The ID of the parent production.
		 *		@type string $venue			The venue of the event.
		 *		@type string $city			The city of the event.
		 *		@type string $tickets_url	The tickets url of the event.
		 *		@type string $event_date	The date of the event.
		 * 		@type string $ref			A unique identifier for the event.
		 *		@type array $prices			The prices of the event.
		 * }
		 * @return WPT_Event				The new event or <false> if there was a problem.
		 */
		function create_event($args) {
			
			$defaults = array(
				'production' => false,
				'venue' => false,
				'city' => false,
				'tickets_url' => false,
				'event_date' => false,
				'ref' => '',
				'prices' => false,
			);
			
			$args = wp_parse_args($args, $defaults);

			$post = array(
				'post_type' => WPT_Event::post_type_name,
			);

			if (false !== $args['production']) {
				$post['post_status'] = get_post_status($args['production']);
			} else {
				$post['post_status'] = 'draft';
			}			

			if ($post_id = wp_insert_post($post)) {
				add_post_meta($post_id, '_wpt_source', $this->get('slug'), true);
				add_post_meta($post_id, '_wpt_source_ref', sanitize_text_field($args['ref']), true);
				
				if (false !== $args['production']) {
					add_post_meta($post_id, WPT_Production::post_type_name, $args['production'], true);				
				}
				if (false !== $args['venue']) {
					add_post_meta($post_id, 'venue', $args['venue'], true);
				}
				if (false !== $args['city']) {
					add_post_meta($post_id, 'city', $args['city'], true);
				}
				if (false !== $args['tickets_url']) {
					add_post_meta($post_id, 'tickets_url', $args['tickets_url'], true);
				}
				if (false !== $args['event_date']) {
					add_post_meta($post_id, 'event_date', $args['event_date'], true);
				}

				if (is_array($args['prices'])) {
					$this->set_event_prices($post_id, $args['prices']);				
				}

				if ( !isset( $this->stats[ 'events_created' ] ) ) {
					$this->stats[ 'events_created' ] = 0;
				}

				$this->stats['events_created']++;
				
				$event = new WPT_Event($post_id);

				// Add the event to the preloaded events.
				$this->events_by_ref[ $args['ref'] ] = $event;
				
				return $event;
				
			} else {
				return false;
			}
		}

		/**
		 * Creates a new production.
		 *
		 * Use this helper function to create new production while processing your feed.
		 * 
		 * @since 0.10
		 *
		 * @access protected
		 * @param array $args {
		 *		@type string $title 	The title of the production.
		 *		@type string $content	The post content for the production.
		 * 		@type string $ref		A unique identifier for the production.
		 * }
		 * @return WPT_Production		The new production or <false> if there was a problem.
		 */
		protected function create_production($args) {
			$defaults = array(
				'title' => '',
				'content' => '',
				'ref' => '',
			);
			
			$args = wp_parse_args($args, $defaults);

			$post = array(
				'post_type' => WPT_Production:: post_type_name,
				'post_title' => $args['title'],
				'post_content' => $args['content'],
				'post_status' => 'draft',
			);

			if ($post_id = wp_insert_post($post)) {
				$ref = sanitize_text_field($args['ref']);
				add_post_meta($post_id, '_wpt_source', $this->get('slug'), true);
				add_post_meta($post_id, '_wpt_source_ref', $ref, true);
				$this->stats['productions_created']++;

				$production = new WPT_Production($post_id);
				$this->productions_by_ref[ $ref ] = $production;
				return $production;
			} else {
				return false;
			}		
		}
		
		
		/**
		 * Gets a property value of the importer.
		 * 
		 * @since	0.15.20
		 * @param 	string	$key		The property key.
		 * @return	mixed			The property value.
		 */
		function get( $key ) {

			$value = '';

			if ( isset( $this->data[ $key ] ) ) {
				$value = $this->data[ $key ];
			}
			
			/**
			 * Filter the property value of the importer.
			 * 
			 * @since	0.15.20
			 * @param	mixed			$value		The property value.
			 * @param	mixed			$key		The property key.
			 * @param	WPT_Importer	$importer	The importer.
			 * @access public
			 */
			$value = apply_filters( 'wpt/importer/get/value', $value, $key, $this);
			
			return $value;
			
		}

		public function __get( $key ) {
			return $this->get( $key );
		}
		
		/**
		 * Gets an event based on the unique identifier.
		 * 
		 * Use this helper function to find a previously imported event while processing your feed.
		 * 
		 * @since 	0.10
		 * @since	0.15.27	Added support for preloaded events.
		 *
		 * @uses WPT_Importer::get_preloaded_event_by_ref() to get a preloaded event.
		 * @uses WPT_Importer::events_by_ref to add an event to the preloaded events.
		 *
		 * @param 	string 		$ref 	A unique identifier for the event.
		 * @return 	WPT_Event 			The event. Returns `false` if no previously imported event was found.
		 */
		function get_event_by_ref($ref) {
			
			if ( $preloaded_event = $this->get_preloaded_event_by_ref( $ref ) ) {
				return $preloaded_event;
			}
			
			$args = array(
				'post_type' => WPT_Event::post_type_name,
				'post_status' => 'any',
				'meta_query' => array(
					array(
						'key' => '_wpt_source',
						'value' => $this->get('slug'),
					),
					array(
						'key' => '_wpt_source_ref',
						'value' => $ref
					),
				),
			);
			$events = get_posts($args);
	
			if (!empty($events[0])) {
				$event = new WPT_Event( $events[0] );
				$this->events_by_ref[ $ref ] = $event;
				return $event;
			} else {
				return false;
			}
			
		}
		
		/**
		 * Gets a preloaded event by source ref.
		 * 
		 * @since	0.15.27
		 * @uses 	WPT_Importer::events_by_ref to retrieve a preloaded event.
		 * @param	string	$ref	The source ref.
		 * @return 	WPT_Event|bool	The preloaded event or <false> if no event was found.
		 */
		function get_preloaded_event_by_ref( $ref ) {

			if ( !isset( $this->events_by_ref[ $ref ] ) ) {
				return false;
			}
			
			return $this->events_by_ref[ $ref ];
		}
		
		/**
		 * Gets a preloaded production by source ref.
		 * 
		 * @since	0.15.27
		 * @uses 	WPT_Importer::productions_by_ref to retrieve a preloaded production.
		 * @param	string				$ref	The source ref.
		 * @return 	WPT_Production|bool			The preloaded production or <false> if no production was found.
		 */
		function get_preloaded_production_by_ref( $ref ) {

			if ( !isset( $this->productions_by_ref[ $ref ] ) ) {
				return false;
			}
			
			return $this->productions_by_ref[ $ref ];
		}
		
		/**
		 * Gets a production based on the unique identifier.
		 * 
		 * Use this helper function to find a previously imported production while processing your feed.
		 * 
		 * @since 	0.10
		 * @since	0.15.27	Added support for preloaded events.
		 *
		 * @uses WPT_Importer::get_preloaded_production_by_ref() to get a preloaded production.
		 * @uses WPT_Importer::productions_by_ref to add a production to the preloaded productions.
		 *
		 * @param 	string 			$ref 	A unique identifier for the production.
		 * @return 	WPT_Production 			The production. Returns `false` if no previously imported production was found.
		 */
		function get_production_by_ref($ref) {

			if ( $preloaded_production = $this->get_preloaded_production_by_ref( $ref ) ) {
				return $preloaded_production;
			}
			
			$args = array(
				'post_type' => WPT_Production::post_type_name,
				'post_status' => 'any',
				'meta_query' => array(
					array(
						'key' => '_wpt_source',
						'value' => $this->get('slug'),
					),
					array(
						'key' => '_wpt_source_ref',
						'value' => $ref
					),
				),
			);
			$productions = get_posts($args);

			if (!empty($productions[0])) {
				$production = new WPT_Production( $productions[0]->ID );
				$this->productions_by_ref[ $ref ] = $production;
				return $production;
			} else {
				return false;
			}
		}
		
		/**
		 * Sets a property value of the importer.
		 * 
		 * @since	0.15.20
		 * @param 	string	$key	The property key.
		 * @param 	mixed 	$value	The property value.
		 * @return 	void
		 */
		function set( $key, $value ) {
			$this->data[ $key ] = $value;
		}
		
		/**
		 * Sets the prices of an event.
		 * 
		 * @since 0.10.7
		 *
		 * @access private
		 * @param int $event_id
		 * @param array $prices
		 * @return void
		 */
		private function set_event_prices($event_id, $prices) {
			
			$current_prices = get_post_meta( $event_id, '_wpt_event_tickets_price');
	
			foreach($prices as $price) {
				if ( !in_array($price, $current_prices)) {
					add_post_meta($event_id,'_wpt_event_tickets_price', $price);											
				}
			}
			
			foreach( $current_prices as $current_price) {
				if (!in_array($current_price, $prices)) {
					delete_post_meta($event_id, '_wpt_event_tickets_price', $current_price);
				}
			}
			
		}
		
		/**
		 * Updates a previously imported event.
		 *
		 * Use this helper function to update existing events while processing your feed.
		 * If no existing event is found then a new one is created.
		 * 
		 * @since 0.10
		 * @since 0.10.7	Method now returns a WPT_Event object.
		 * 					Added support for event prices.
		 * @since 0.10.14	Only updates fields that are explicitly set in $args 
		 * 					by the importer. Fixes #113.
		 *
		 * @see WPT_Importer::get_event_by_ref()
		 * @see WPT_Importer::create_event()
		 *
		 * @access protected
		 * @param array $args {
		 *		@type int $production 		The ID of the parent production.
		 *		@type string $venue			The venue of the event.
		 *		@type string $city			The city of the event.
		 *		@type string $tickets_url	The tickets url of the event.
		 *		@type string $event_date	The date of the event.
		 * 		@type string $ref			A unique identifier for the event.
		 *		@type array $prices			The prices of the event.
		 * }
		 * @return WPT_Event				The new or updated event.
		 *									Or <false> if there was a problem creating a new event.
		 */
		function update_event($args) {

			$defaults = array(
				'production' => false,
				'venue' => false,
				'city' => false,
				'tickets_url' => false,
				'event_date' => false,
				'ref' => '',
				'prices' => false,
			);
			
			$args = wp_parse_args($args, $defaults);

			$event = $this->get_event_by_ref($args['ref']);
			
			if (empty($event)) {
				return $this->create_event($args);
			}
			
			update_post_meta($event->ID, WPT_Production::post_type_name, (string) $args['production']);			
			
			if ($args['venue']!==false) {
				update_post_meta($event->ID, 'venue', $args['venue']);			
			}
			
			if ($args['city']!==false) {
				update_post_meta($event->ID, 'city', $args['city']);
			}
			
			if ($args['tickets_url']!==false) {
				update_post_meta($event->ID, 'tickets_url', $args['tickets_url']);
			}
			if ($args['event_date']!==false) {
				update_post_meta($event->ID, 'event_date', $args['event_date']);
			}
			
			if (is_array($args['prices'])) {
				$this->set_event_prices($event->ID, $args['prices']);
			}

			$marked_events = $this->get('marked_events');
			if ( !is_array( $marked_events ) ) {
				$marked_events = array();
			}

			$this->set('marked_events', array_diff( $marked_events, array( $event->ID ) ) );

			if ( !isset( $this->stats[ 'events_updated' ] ) ) {
				$this->stats[ 'events_updated' ] = 0;
			}
			$this->stats['events_updated']++;
			
			$event = new WPT_Event($event->ID);
			
			$this->events_by_ref[ $args['ref'] ] = $event;
			
			return $event;
			
		}

		/**
		 * Updates the thumbnail of a production from a URL.
		 *
		 * Use this helper function to import thumbnails while processing your feed.
		 * 
		 * @since 0.10
		 *
		 * @access protected
		 * @param int $production_id
		 * @param string $image_url
		 * @param string $image_desc
		 * @return int Post meta ID on success, false on failure.
		 */
		protected function update_production_thumbnail_from_url($production_id, $image_url, $image_desc) {

			require_once(ABSPATH . 'wp-admin/includes/media.php');
			require_once(ABSPATH . 'wp-admin/includes/file.php');
			require_once(ABSPATH . 'wp-admin/includes/image.php');

			$tmp = download_url( $image_url );
			$file_array = array();
			
			// Set variables for storage
			// fix file filename for query strings
			preg_match('/[^\?]+\.(jpg|jpe|jpeg|gif|png)/i', $image_url, $matches);
			$file_array['name'] = basename($matches[0]);
			$file_array['tmp_name'] = $tmp;
			
			// If error storing temporarily, unlink
			if ( is_wp_error( $tmp ) ) {
				@unlink($file_array['tmp_name']);
				$file_array['tmp_name'] = '';
			}
			
			// do the validation and storage stuff
			$thumbnail_id = media_handle_sideload( $file_array, $production_id, $image_desc );

			// If error storing permanently, unlink
			if ( is_wp_error($thumbnail_id) ) {
				@unlink($file_array['tmp_name']);
				return $thumbnail_id;
			}

			return set_post_thumbnail( $production_id, $thumbnail_id );
		}

		/**
		 * Executes the import.
		 *
		 * 1. Mark any previously imported upcoming events.
		 * 2. Process your feed.
		 * 3. If successful: Remove all previously imported upcoming events that are no longer
		 *    present in your feed.
		 *    If unsuccesful: Clean up, unmark all previously imported events.
		 * 4. Save import stats for display on the settings screen.
		 *
		 * @since 	0.10
		 * @since	0.15.15	Fixed missing timezone support for import start and end times.
		 * @since	0.15.24	No longer unmarks events after an unsuccessful import.
		 *					This is no longer neccessary because events are no longer marked in the database.
		 *					Added action hooks right before and after imports.
		 *
		 * @uses 	WPT_Importer::mark_upcoming_events()
		 * @uses 	WPT_Importer::process_feed()
		 * @uses 	WPT_Importer::remove_marked_events()
		 * @uses	WPT_Importer::unmark_events()
		 * @uses 	WPT_Importer::save_stats()
		 *
		 * @return void
		 */
		function execute() {
			$this->stats['start'] = current_time( 'timestamp' );
			$this->stats['events_created'] = 0;
			$this->stats['events_updated'] = 0;
			$this->stats['productions_created'] = 0;
			$this->stats['productions_updated'] = 0;
			$this->stats['errors'] = array();
			
			/**
			 * Fires right before an import starts.
			 *
			 * @since 0.15.24
			 *
			 * @param	WPT_Importer	$importer	The importer object.
			 */
			do_action( 'wpt/importer/execute/before', $this );
			do_action( 'wpt/importer/execute/before/importer='.$this->get( 'slug' ), $this );

			$this->mark_upcoming_events();
			
			if ($this->process_feed()) {
				$this->remove_marked_events();			
			}

			$this->stats['end'] = current_time( 'timestamp' );
			
			$this->save_stats();

			/**
			 * Fires right after an import ends.
			 *
			 * @since 0.15.24
			 *
			 * @param	WPT_Importer	$importer	The importer object.
			 */
			do_action( 'wpt/importer/execute/after', $this );
			do_action( 'wpt/importer/execute/after/importer='.$this->get( 'slug' ), $this );

		}
		
		/**
		 * Executes the re-import of a production.
		 * 
		 * 1. Mark previously imported upcoming events for this production.
		 * 2. Runs the re-import callback function.
		 * 3. If successful: Remove all previously imported upcoming events for this production
		 *    that are no longer present in your feed.
		 *    If unsuccesful: Clean up, unmark all previously imported events for this production.
		 *
		 * @since	0.14.5
		 * @since	0.15.24	No longer unmarks production events after an unsuccessful import.
		 *					This is no longer neccessary because events are no longer marked in the database.
		 *
		 * @param 	int	$production_id	The production ID.
		 */
		function execute_reimport($production_id) {
			$this->mark_production_events($production_id);
			
			$callbacks = $this->get( 'callbacks' );	
			
			$reimport_result = call_user_func_array( $callbacks['reimport_production'], array( $production_id ) );
			
			if ($reimport_result) {
				$this->remove_marked_events();			
			}
			
		}

		/**
		 * Executes the import when the 'Run import now'-link is clicked.
		 * 
		 * Hooked into the `wp_loaded` option.
		 * 
		 * @since 0.10
		 *
		 * @see WPT_Importer::execute()
		 * @see WPT_Importer::init()
		 *
		 * @return void
		 */
		function handle_import_linked() {
			if (
				!empty($_GET['wpt_import']) && 
				($this->get('slug') == $_GET['wpt_import']) &&
				check_admin_referer( 'wpt_import' )
			) {
				$this->execute();
				return;
				wp_redirect( 'admin.php?page=wpt_admin&tab='.$this->get('slug') );
				exit;
			}
		}
		
		/**
		 * Marks all upcoming events of a production.
		 * 
		 * @since	0.14.5
		 * @since	0.15.24 Now uses the new internal marked events tracker.
		 *
		 * @uses	WPT_Importer::set() to add events to the marked events tracker.
		 *
		 * @access  private
		 * @param 	int		$production_id	The production ID.
		 */
		private function mark_production_events($production_id) {
			global $wp_theatre;
			
			$args = array(
				'post_type' => WPT_Event::post_type_name,
				'post_status' => 'any',
				'posts_per_page' => -1,
				'meta_query' => array(
					array(
						'key' => '_wpt_source',
						'value' => $this->get('slug'),
					),
					array(
						'key' => THEATER_ORDER_INDEX_KEY,
						'value' => time(),
						'compare' => '>=',
					),
					array(
						'key' => WPT_Production::post_type_name,
						'value' => $production_id,
						'compare' => '=',						
					)
				),
			);
			
			$events = get_posts($args);

			$this->set('marked_events', wp_list_pluck( $events, 'ID') );
		}
		
		/**
		 * Mark any previously imported upcoming events.
		 * 
		 * @since 	0.10
 		 * @since	0.14.3	Bugfix: Added 'posts_per_page' argument to ensure that all events are marked.
 		 *					Fixes #182.
		 * @since	0.15.24 Now uses the new internal marked events tracker.
		 *
		 * @uses	WPT_Importer::set() to add events to the marked events tracker.
		 * 
		 * @access private
		 * @return void
		 */
		private function mark_upcoming_events() {
			
			global $wp_theatre;
			
			$args = array(
				'post_type' => WPT_Event::post_type_name,
				'post_status' => 'any',
				'posts_per_page' => -1,
				'meta_query' => array(
					array(
						'key' => '_wpt_source',
						'value' => $this->get('slug'),
					),
					array(
						'key' => THEATER_ORDER_INDEX_KEY,
						'value' => time(),
						'compare' => '>=',
					),
				),
			);
			
			$events = get_posts($args);
			
			$this->set('marked_events', wp_list_pluck( $events, 'ID') );
			
		}
		
		/**
		 * Executes the re-import of a production when the 'Re-import'-link is clicked in 
		 * the metabox for this importer on the production edit form.
		 * 
		 * Hooked into the `wp_loaded` option.
		 * 
		 * @since 0.14.5
		 *
		 * @see WPT_Importer::execute_reimport()
		 * @see WPT_Importer::init()
		 *
		 * @return void
		 */
		function reimport_production() {
			
			if (empty($_GET['wpt_reimport'])) {
				return;
			}
			
			if ($this->slug != $_GET['wpt_reimport']) {
				return false;
			}
			
			if (!check_admin_referer( 'wpt_reimport' )) {
				return false;
			}
			
			$callbacks = $this->get( 'callbacks' );			
			if (empty($callbacks['reimport_production'])) {
				return false;
			}
			
			if (empty($_GET['post'])) {
				return false;
			}
			
			$production_id = intval($_GET['post']);
			
			$this->execute_reimport($production_id);
			
		}
		
		/**
		 * Removes all previously imported events that are still marked.
		 * 
		 * @since	0.?
		 * @since	0.15.24 Now uses the new internal marked events tracker.
		 *
		 * @access 	private
		 * @uses	WPT_Importer::get() to get all remaining marked events.
		 * @return 	void
		 */
		private function remove_marked_events() {
			foreach($this->get('marked_events') as $event_id) {
				wp_delete_post($event_id, true);
			}
		}

		/**
		 * Clear all preloaded events.
		 * 
		 * Used by unit tests to clear preloaded events in between tests.
		 *
		 * @since	0.15.27
		 * @uses 	WPT_Importer::events_by_ref to clear all preloaded events.
		 * @return 	void
		 */
		function clear_preloaded_events() {
			$this->events_by_ref = array();
		}
		
		/**
		 * Clear all preloaded productions.
		 * 
		 * Used by unit tests to clear preloaded productions in between tests.
		 *
		 * @since	0.15.27
		 * @uses 	WPT_Importer::productions_by_ref to clear all preloaded productions.
		 * @return 	void
		 */
		function clear_preloaded_productions() {
			$this->productions_by_ref = array();			
		}

		/**
		 * Saves the import stats.
		 *
		 * The import stats are displayed on the setting screen.
		 * 
		 * @since 0.10
		 *
		 * @see WPT_Importer::execute()
		 * @see WPT_Importer::settings_field_last_import()
		 * 
		 * @access private
		 * @return void
		 */
		private function save_stats() {
			update_option($this->get('slug').'_stats', $this->get('stats') );
		}
		
		/**
		 * Schedules the import.
		 *
		 * Removes any previously scheduled imports. 
		 * If no valid $schedule is given then no new import is scheduled.
		 * 
		 * @since 0.10
		 *
		 * @see WPT_Importer::update_options()
		 * @see WPT_Setup::cron_schedules()
		 * 
		 * @access protected
		 * @param string $schedule 	How often the event should reoccur. 
		 							Accepts `hourly`, `twicedaily`, `daily` and
		 							any custom intervals created using the cron_schedules filter 
		 							in wp_get_schedules().
		 * @return bool 			Returns 'true' if the import was succesfully scheduled. 
		 */
		protected function schedule_import($schedule) {
			
			// remove previously scheduled imports
			wp_clear_scheduled_hook($this->get('slug').'_import');
			
			// schedule import
			$schedules = wp_get_schedules();
			if (in_array($schedule,array_keys($schedules))) {
				return (false !== wp_schedule_event( time(), $schedule, $this->get('slug').'_import'));
			} else {
				return false;
			}
			
		}

		/**
		 * Runs after the settings are updated.
		 *
		 * Hooked into the `update_option_$option` action.
		 * We use this to schedule the import after the import schedule is set on the settings page.
		 * 
		 * @since 0.10
		 *
		 * @see WPT_Importer::init()
		 *
		 * @param string $old_value
		 * @param string $value
		 */
		function update_options($old_value,$value) {
			if (isset($value['schedule'])) {
				$this->schedule_import($value['schedule']);
			}
		}
		
		/**
		 * Adds a new tab to the Theater settings screen.
		 *
		 * Hooked into the `wpt_admin_page_tabs` filter.
		 * 
		 * @since 0.10
		 *
		 * @see WPT_Importer::init()
		 *
		 * @param array $tabs The existing tabs on the Theater settings screen.
		 * @return array The tabs, with a new tab added to the end.
		 */
		function add_settings_tab($tabs) {
			$tabs[$this->get('slug')] = $this->get('name');		
			return $tabs;
		}

		/**
		 * Add basic settings to the settings tab of your importer on the Theater settings screen.
		 * 
		 * Hooked into the `admin_init` action.
		 *
		 * @since 0.10
		 *
		 * @see WPT_Importer::init()
		 * @see WPT_Importer::add_settings_tab()
		 *
		 * @return void
		 */
		function add_settings_fields() {
			
			/*
			 * Register a new setting for your importer.
			 * Use the slug of your importer for the name.
			 */
	        register_setting($this->get('slug'), $this->get('slug'));
	
			/*
			 * Create an 'Import' section.
			 * You can add any setting that is specific to your importer to this section.
			 * Add a 'schedule' field.
			 */
	
	        add_settings_section(
	            $this->get('slug').'_settings', // ID
	            __('Import','theatre'), // Title
	            '', // Callback
	            $this->get('slug') // Page
	        );  

	        add_settings_field(
	            'schedule', // ID
	            __('Schedule','theatre'), // Title 
	            array( $this, 'settings_field_schedule' ), // Callback
	            $this->get('slug'), // Page
	            $this->get('slug').'_settings' // Section           
	        );      
	        
			/*
			 * Create an 'Status' section.
			 * You can add any status information specific to your importer to this section.
			 * Add 'status', 'next import' and 'last import' fields.
			 */
	
	        add_settings_section(
	            $this->get('slug').'_status', // ID
	            __('Status','theatre'), // Title
	            '', // Callback
	            $this->get('slug') // Page
	        );  

	        add_settings_field(
	            'status', // ID
	            __('Ready for import','theatre'), // Title 
	            array( $this, 'settings_field_status' ), // Callback
	            $this->get('slug'), // Page
	            $this->get('slug').'_status' // Section           
	        );      
	        
	        add_settings_field(
	            'next import', // ID
	            __('Next import','theatre'), // Title 
	            array( $this, 'settings_field_next_import' ), // Callback
	            $this->get('slug'), // Page
	            $this->get('slug').'_status' // Section           
	        );      
	        
	        add_settings_field(
	            'last import', // ID
	            __('Last import','theatre'), // Title 
	            array( $this, 'settings_field_last_import' ), // Callback
	            $this->get('slug'), // Page
	            $this->get('slug').'_status' // Section           
	        );      
	        
		}
		
		/**
		 * Displays a schedule input for your importer.
		 *
		 * The user can select one of the available cron recurrences to schedule the import for your importer.
		 * If `manual` is selected then no import is scheduled.
		 * 
		 * @since 0.10
		 *
		 * @see WPT_Importer::add_settings_fields()
		 * @see WPT_Setup::cron_schedules()
		 *
		 * @return void
		 */
		function settings_field_schedule() {

			$schedules = wp_get_schedules();

			echo '<select id="schedule" name="'.$this->get('slug').'[schedule]">';
			
			echo '<option value="manual">'.__('Manually','theatre').'</option>';

			$options = $this->get('options');

			foreach($schedules as $name => $value) {

				echo '<option value="'.$name.'"';
				if ($name == $options['schedule']) {
					echo ' selected="selected"';
				}
				echo '>'.$value['display'].'</option>';
				
			}

			echo '</select>';
			
			if ($this->ready_for_import()) {
			
				$import_url = add_query_arg('wpt_import', $this->get('slug'));
				$import_url = wp_nonce_url( $import_url, 'wpt_import' );
	
				echo '<p><a href="'.esc_url($import_url).'">'.__('Run import now','theatre').'</a></>';				
				
			}
		}
	
		/**
		 * Displays the current status of your importer.
		 *
		 * @since 0.10
		 *
		 * @see WPT_Importer::add_settings_fields()
		 * @see WPT_Importer::ready_for_import()
		 *
		 * @return void
		 */
		function settings_field_status() {
			
			echo '<p>';
			
			if ($this->ready_for_import()) {
				_e('Yes','theatre');
			} else {
				_e('No','theatre');
			}

			echo '</p>';

		}
		
		/**
		 * Displays the time for the next scheduled import.
		 *
		 * @since 0.10
		 *
		 * @see WPT_Importer::add_settings_fields()
		 *
		 * @return void
		 */
		function settings_field_next_import() {
			
			if ($timestamp = wp_next_scheduled( $this->get('slug').'_import' )) {
				echo sprintf(__('In %s.','theatre'),human_time_diff($timestamp));
			}
		}
		
		/**
		 * Displays the stats of the last import.
		 *
		 * @since 0.10
		 *
		 * @see WPT_Importer::add_settings_fields()
		 *
		 * @return void
		 */
		function settings_field_last_import() {
			
			$stats = $this->get('stats');
			
			echo '<table>';
			
			echo '<tbody>';
			
			if (!empty($stats['start'])) {
				echo '<tr>';
				echo '<th><strong>'.__('Start','theatre').'</strong></th>';

				echo '<td>'.
					date_i18n(get_option('date_format'), $stats['start']).
					'<br />'.
					date_i18n(get_option('time_format'), $stats['start']).
					'</td>';

				echo '</tr>';

				if (!empty($stats['end'])) {
					echo '<tr>';
					echo '<th>'.__('Duration','theatre').'</th>';
					echo '<td>'.human_time_diff($stats['start'], $stats['end']).'</td>';				
					echo '</tr>';
				}

				/**
				 * Displays import errors, if any errors were registered during processing of the feed.
				 *
				 * @since ?
				 */

				if (!empty($stats['errors'])) {
					$msg = '<p><strong>'.__('Import failed. Please try again, or contact your help desk if the problem persists.','theatre'). '</strong></p>';
					if (defined('WP_DEBUG') && WP_DEBUG === true) {
						foreach ($stats['errors'] as $error) {
							$msg .= '<p>'.$error.'</p>';
						}
					}
					echo '<tr>';
					echo '<th>'.__('Error','theatre').'</th>';
					echo '<td>'.$msg.'</td>';				
					echo '</tr>';
				}
			}

			echo '</tbody>';

			echo '</table>';
		}	
	}