class wpt_editor

	constructor: (@editor) ->
	
		@productions = new wpt_productions @editor

	

class wpt_productions

	constructor: (@editor) ->
		@placeholder = @editor.find '.wpt_editor_productions'
		@productions = []
		@load()
		
	load : ->
		data =
			'action': 'productions'

		jQuery.post wpt_editor_ajax.url, data, (response) =>
			@productions.push new wpt_production production for production in response
			@update()
	
	update: ->
		@placeholder.empty()
		@placeholder.append production.html() for production in @productions
	

class wpt_production

	constructor:(@production) ->
	
	html: ->
		html = '<div class="wpt_editor_production">';

		html+= '<div class="wpt_editor_production_actions">'
		html+= '<a class="wpt_production_editor_view" href="">View</a>'
		html+= '<a class="wpt_production_editor_delete" href="">Delete</a>'
		html+= '<a class="wpt_production_editor_edit" href="">Edit</a>'
		html+= '</div>';
		
		html+= '<div class="wpt_editor_production_meta">'
		html+= '<div class="wpt_editor_production_dates">'+@production['dates']+'</div>'
		html+= '<div class="wpt_editor_production_cities">'+@production['cities']+'</div>'
		html+= '<div class="wpt_editor_production_categories">'+@production['categories']+'</div>'
		html+= '<div class="wpt_editor_production_season">'+@production['season']+'</div>'
		html+= '</div>';
		
		html+= '<h2>'+@production['title']+'</h2>'
		html+= '<div>'+@production['excerpt']+'</div>'
		html+= '<p>'+@production[wpt_editor_ajax.order_key]+'</p>'
		html+= '</div>';
		html
		
	edit: ->
	
	save: ->
	

jQuery ->
	jQuery('.wpt_editor').each ->
		new wpt_editor jQuery @
