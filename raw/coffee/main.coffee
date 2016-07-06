# @codekit-prepend 'wpt_calendar.coffee';

###
wpt_tickets class.

@since	0.?
@since	0.13	Use 'thickbox_args' that are set in WPT_Frontend::enqueue_styles().
###
class wpt_tickets
	constructor: ->
		@.ticket_urls = jQuery('.wp_theatre_event_tickets_url').click (e) =>
			@.tickets e.currentTarget
	tickets: (e) ->
		tickets_url = jQuery e
		
		if tickets_url.hasClass 'wp_theatre_integrationtype__blank'
			window.open tickets_url.attr 'href'
			return false
			
		if tickets_url.hasClass 'wp_theatre_integrationtype_lightbox'
			url = tickets_url.attr 'href'

			if !thickbox_args.disable_width or (jQuery(window).width() >  thickbox_args.disable_width)
			
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
				url_query += 'TB_iframe=true&width='+thickbox_args.width+'&height='+thickbox_args.height
	
				url = url_base + '?' + url_query
				url += '#' + url_anchor if url_anchor

				tb_show '', url
				return false
			
		true

jQuery ->
	wpt_tickets = new wpt_tickets()
	