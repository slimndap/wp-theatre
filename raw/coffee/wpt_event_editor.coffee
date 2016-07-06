class wpt_event_editor
	constructor: ->
		@init_datetime_inputs()
		@init_create()
		
		@init_actions_callbacks = [@init_delete_links]
		@init_actions()
		
	init_datetime_inputs : ->
	
		@event_date = jQuery '#wpt_event_editor_event_date'
		@enddate = jQuery '#wpt_event_editor_enddate'
	
		@event_date.wpt_datetimepicker
			defaultDate: wpt_event_editor_defaults.event_date
			format : wpt_event_editor_defaults.datetime_format
			step: 15
			lang: wpt_event_editor_defaults.language
			onChangeDateTime: (event_date, input) =>
				if event_date?
					enddate = new Date @enddate.val()	
					if not @enddate.val() or (enddate < event_date)
						enddate = new Date event_date.getTime() + wpt_event_editor_defaults.duration * 1000					
						@enddate.val enddate.dateFormat wpt_event_editor_defaults.datetime_format
			
		@enddate.wpt_datetimepicker	
			format : wpt_event_editor_defaults.datetime_format
			step: 15
		
	init_delete_links : =>
		jQuery('.wpt_event_editor_listing_action_delete').unbind('click').click (e) =>
			if confirm wpt_event_editor_defaults.confirm_delete_message
				data =
					'action': 'wpt_event_editor_delete_event'
					'event_id': jQuery(e.currentTarget).parents('tr').data 'event_id'
					'nonce': wpt_event_editor_security.nonce
				jQuery('.wpt_event_editor_listing').load ajaxurl, data, =>
					@init_actions()
			false

	init_actions : =>
		for callback in @init_actions_callbacks
			callback()

	init_create : ->
		@create = jQuery '.wpt_event_editor_create'
		open = @create.find '.wpt_event_editor_create_open'
		open.click =>
			@create.addClass 'open'
			false

		cancel = @create.find '.wpt_event_editor_create_cancel'
		cancel.click =>
			@create.removeClass 'open'
			false
		
		save = @create.find '.wpt_event_editor_create_save'
		save.click =>
		
			form = jQuery '#post'
		
			data =
				'action': 'wpt_event_editor_create_event'
				'post_data' : form.serialize()
				'nonce': wpt_event_editor_security.nonce
			jQuery('.wpt_event_editor_listing').load ajaxurl, data, =>
				@init_delete_links()
				@create.removeClass 'open'
				@reset_create_form()
			false
	
	reset_create_form : ->
		
		container = @create.find '.wpt_event_editor_create_form'

		data =
			'action': 'wpt_event_editor_reset_create_form'
			'production_id' : jQuery('#post_ID').val()
			'nonce': wpt_event_editor_security.nonce

		container.load ajaxurl, data, =>
			@init_datetime_inputs()
		
jQuery ->
	wpt_event_editor_defaults.editor = new wpt_event_editor()
