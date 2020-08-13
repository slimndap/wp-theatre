# @codekit-prepend 'wpt_bulk_editor.coffee'
# @codekit-prepend 'wpt_event_editor.coffee'
# @codekit-prepend 'wpt_production_permalink.coffee'

class wpt_admin_ticketspage
	constructor: ->
		@.ticketspage = jQuery('select#iframepage').parents 'tr'
		@.integrationstypes = jQuery "input[name='wpt_tickets[integrationtype]']"
		if @.ticketspage.length > 0 and @.integrationstypes.length > 0
			@.update() 
			@.integrationstypes.click =>
				@.update()
	update: ->
		integrationtype = jQuery("input[name='wpt_tickets[integrationtype]']:checked").val()
		if integrationtype == 'iframe'
			@.ticketspage.show(1000)
		else 
			@.ticketspage.hide(500)
			
