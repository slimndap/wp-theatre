class wpt_tickets
	constructor: ->
		@.ticket_urls = jQuery('.wpt_tickets_url').click (e) =>
			@.tickets e.currentTarget
	tickets: (e) ->
		tickets_url = jQuery e
		
		if tickets_url.hasClass 'wp_theatre_integrationtype_new_window'
			window.open tickets_url.attr 'href'
			return false
		if tickets_url.hasClass 'wp_theatre_integrationtype_lightbox'
			url = tickets_url.attr 'href'
			
			if url.indexOf '#'
				tmp_parts = url.split '#'
				url = tmp_parts[0]
				url_anchor = tmp_parts[1]

			if url.indexOf '?'
				url_parts = url.split '?'
				url_base = url_parts[0]
				url_query = url_parts[1]
			else
				url_base = url
			
			url_query += '&' if url_query			
			url_query += 'TB_iframe=true&width=800&height=600'

			url = url_base + '?' + url_query
			url += '#' + url_anchor if url_anchor
									
			tb_show '', url
			return false
			
		true

jQuery ->
	wpt_tickets = new wpt_tickets
	