class wpt_editor

	constructor: (@editor) ->
	

		@productions = new wpt_productions @editor
		


class wpt_productions

	constructor: (@editor) ->

		options = 
			valueNames: ['wpt_editor_production_title','wpt_editor_production_excerpt']
			listClass: 'wpt_editor_productions'
			item: 'wpt_editor_production_template'
		@list = new List @editor.attr('id'), options

		@load()

	load : ->
		data =
			'action': 'productions'

		@editor.addClass 'busy'
		jQuery.post wpt_editor_ajax.url, data, (response) =>
			@list.add response
			new wpt_production production for production in @editor.find('.wpt_editor_productions').children()
			@editor.removeClass 'busy'
	

class wpt_production

	constructor:(@production) ->
			
	edit: ->
	
	save: ->
	

jQuery ->
	wpt_editor = new wpt_editor jQuery '#wpt_editor'
