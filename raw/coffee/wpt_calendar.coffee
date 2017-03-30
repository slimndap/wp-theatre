class wpt_calendar

	###
	Manage the navigation of all WPT_Calendar blocks.
	@since 	0.8
	@since	0.15.23	No longer sets the initial active month.
					@see WPT_Calendar::html().
	###

	constructor: (@calendar) ->
		@calendar = jQuery @calendar
		
		@calendar.addClass 'navigate'
		
		jQuery(@calendar).find('tfoot a').click (e) =>
			@.navigate e.currentTarget
	
	###
	Handle prev/next.
	@since 0.8
	###
	
	navigate: (e) ->
		href = jQuery(e).attr('href');
		@calendar.find('.active').removeClass 'active'
		@calendar.find('caption a[href="'+href+'"]').parents('.wpt_month').addClass 'active'
		false;

jQuery ->
	jQuery('.wpt_calendar').each ->
		new wpt_calendar(@)