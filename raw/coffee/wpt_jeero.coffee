jQuery ->
	
	$tickets_url = jQuery '#wpt_event_editor_tickets_url'
	
	$tickets_url.after( '<div class="wpt_jeero_suggestion"></div>' )
	$suggestion = $tickets_url.find '+ .wpt_jeero_suggestion'
	$suggestion.hide()
	
	$tickets_url.change ->
		data =
			'action': 'wpt_jeero_suggest'
			'tickets_url': $tickets_url.val()

		$suggestion.load ajaxurl, data, ->
		
			if '' == $suggestion.text()
				$suggestion.hide 100
			else
				$suggestion.show 100
