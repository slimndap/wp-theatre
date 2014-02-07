class wpt_admin_ticketspage
	constructor: ->
		@.ticketspage = jQuery('select#iframepage').parents 'tr'
		@.integrationstypes = jQuery "input[name='wp_theatre[integrationtype]']"
		if @.ticketspage.length > 0 and @.integrationstypes.length > 0
			@.update() 
			@.integrationstypes.click =>
				@.update()
	update: ->
		integrationtype = jQuery("input[name='wp_theatre[integrationtype]']:checked").val()
		if integrationtype == 'iframe'
			@.ticketspage.show(1000)
		else 
			@.ticketspage.hide(500)

jQuery ->
	wpt_admin_ticketspage = new wpt_admin_ticketspage
	