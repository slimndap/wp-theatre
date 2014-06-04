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

class wpt_admin_ticketspage
	constructor: ->
		@.listing_page_type = jQuery "input[name='wpt_listing_page[listing_page_type]']"
		@.listing_page_nav_events = jQuery('#listing_page_nav_events').parents 'tr'
		@.listing_page_nav_productions = jQuery('#listing_page_nav_productions').parents 'tr'
		if @.listing_page_type.length > 0 and @.listing_page_nav_events.length > 0 and @.listing_page_nav_productions.length >0
			@.update() 
			@.listing_page_type.click =>
				@.update()
	update: ->
		listing_page_type = jQuery("input[name='wpt_listing_page[listing_page_type]']:checked").val()
		
		if listing_page_type == 'wp_theatre_prod'
			@.listing_page_nav_events.hide()
			@.listing_page_nav_productions.show()
		else
			@.listing_page_nav_events.show()
			@.listing_page_nav_productions.hide()
			
jQuery ->
	wpt_admin_ticketspage = new wpt_admin_ticketspage

	###
	Enable datepicker in event admin form.
	###

	jQuery('.wp_theatre_datepicker').datetimepicker
		dateFormat : 'yy-mm-dd'
		timeFormat: 'HH:mm:ss'	
		
	###
	Update all connected events when bulk updating productions.
	See: http://codex.wordpress.org/Plugin_API/Action_Reference/bulk_edit_custom_box
	###
	jQuery('#bulk_edit').live 'click', () ->
		# define the bulk edit row
		bulk_row = jQuery '#bulk-edit'
		# get the selected post ids that are being edited
		post_ids = new Array()
		bulk_row.find( '#bulk-titles' ).children().each () ->
			post_ids.push jQuery(@).attr( 'id' ).replace( /^(ttle)/i, '' )
		# get the data
		post_status = bulk_row.find( 'select[name="_status"]' ).val();
		# save the data
		jQuery.ajax
			url: ajaxurl
			type: 'POST'
			async: false
			cache: false
			data:
				action: 'save_bulk_edit_wp_theatre_prod'
				post_ids: post_ids
				post_status: post_status
