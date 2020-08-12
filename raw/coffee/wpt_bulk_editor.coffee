jQuery ->
	###
	Update all connected events when bulk updating productions.
	See: http://codex.wordpress.org/Plugin_API/Action_Reference/bulk_edit_custom_box
	###
	
	jQuery( document ).on 'click', '#bulk_edit', () ->
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
				action: 'wpt_bulk_editor'
				post_ids: post_ids
				post_status: post_status
				wpt_bulk_editor_ajax_nonce: wpt_bulk_editor_security.nonce

