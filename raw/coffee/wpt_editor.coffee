class wpt_editor
	constructor: ->
		@.activate_datetime_inputs()
		
	activate_datetime_inputs : ->
		jQuery('#wpt_event_editor_event_date').datetimepicker()
		jQuery('#wpt_event_editor_enddate').datetimepicker()

jQuery ->
	new wpt_editor
