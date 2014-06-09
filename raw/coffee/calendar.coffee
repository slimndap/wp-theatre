class wpt_calendar
	constructor: (@calendar) ->
		@calendar = jQuery @calendar
		
		@calendar.addClass 'navigate'
		@calendar.children().first().addClass 'active'
		
		jQuery(@calendar).find('tfoot a').click (e) =>
			@.navigate e.currentTarget
			
	navigate: (e) ->
		href = jQuery(e).attr('href');
		@calendar.find('.active').removeClass 'active'
		@calendar.find('caption a[href="'+href+'"]').parents('.wpt_month').addClass 'active'
		false;

jQuery ->
	jQuery('.wpt_calendar').each ->
		new wpt_calendar(@)