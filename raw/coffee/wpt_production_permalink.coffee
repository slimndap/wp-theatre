jQuery ->
	custom_base_input = jQuery 'input[name=wpt_production_permalink_custom_base]'
	custom_base_input.focus ->
		custom_base_radio = jQuery 'input[name=wpt_production_permalink_base][value=custom]'
		custom_base_radio.prop 'checked', true