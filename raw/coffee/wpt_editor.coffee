class wpt_editor
	constructor: ->
		@init_datetime_inputs()
		@init_delete_links()
		
	init_datetime_inputs : ->
		@event_date = jQuery('#wpt_event_editor_event_date').datetimepicker
			defaultValue: wpt_editor_defaults.event_date
		@endtime = jQuery('#wpt_event_editor_enddate').datetimepicker()	
		
		@event_date.change =>
			event_date_value = @event_date.datetimepicker 'getDate'
			if event_date_value?
				event_date_value.setSeconds event_date_value.getSeconds() + wpt_editor_defaults.duration
				@endtime.datetimepicker 'setDate', event_date_value

	init_delete_links : ->
		jQuery('.wpt_event_editor_delete_link').click (e) =>
			if confirm wpt_editor_defaults.confirm_delete_message
				data =
					'action': 'wpt_event_editor_delete_event'
					'event_id': jQuery(e.currentTarget).data 'event_id'
					'nonce': wpt_editor_security.nonce
				jQuery('.wpt_event_editor_event_listing').load ajaxurl, data, =>
					@init_delete_links()
			false

jQuery ->
	new wpt_editor
