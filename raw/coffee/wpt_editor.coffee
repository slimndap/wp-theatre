class wpt_editor
	constructor: ->
		@init_datetime_inputs()
		@init_delete_links()
		
	init_datetime_inputs : ->
	
		@event_date = jQuery '#wpt_event_editor_event_date'
		@enddate = jQuery '#wpt_event_editor_enddate'
	
		@event_date.datetimepicker
			defaultDate: wpt_editor_defaults.event_date
			format : wpt_editor_defaults.datetime_format
			step: 15
			onChangeDateTime: (event_date, input) =>
				if event_date?
					enddate = new Date @enddate.val()	
					if not @enddate.val() or (enddate < event_date)
						enddate = new Date event_date.getTime() + wpt_editor_defaults.duration * 1000					
						@enddate.val enddate.dateFormat wpt_editor_defaults.datetime_format
			
		@enddate.datetimepicker	
			format : wpt_editor_defaults.datetime_format
			step: 15
		
	init_delete_links : ->
		jQuery('.wpt_event_editor_listing_action_delete').click (e) =>
			if confirm wpt_editor_defaults.confirm_delete_message
				data =
					'action': 'wpt_event_editor_delete_event'
					'event_id': jQuery(e.currentTarget).parents('tr').data 'event_id'
					'nonce': wpt_editor_security.nonce
				jQuery('.wpt_event_editor_event_listing').load ajaxurl, data, =>
					@init_delete_links()
			false

jQuery ->
	new wpt_editor
