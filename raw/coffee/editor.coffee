class wpt_editor

	constructor: (@item) ->
		@id = @item.attr('id')

		options = 
			valueNames: ['wpt_editor_production_title','wpt_editor_production_excerpt']
			listClass: 'wpt_editor_productions'
			item: 'wpt_editor_production_template'
			searchClass: 'wpt_editor_search'
		@list = new List @id, options

		@productions = new wpt_productions @
		
		@categories()
	
	###
		Set status to busy (show spinner).
	###	
	busy: () ->
		@item.addClass 'busy'

	###
		Set status to done (hide spinner).
	###
	done: () ->
		@item.removeClass 'busy'
		
	categories: () ->
		@category_filters = @item.find('.wpt_editor_filters .categories li a');
		@category_filters.click (e) =>
			filter = jQuery e.currentTarget
			if filter.hasClass 'active'
				filter.removeClass 'active'
				@productions.category()
			else
				filter.addClass 'active'
				@productions.category filter.text()
			false

class wpt_productions

	constructor: (@editor) ->

		@load()

	load : ->
		data =
			'action': 'productions'

		@editor.busy()
		jQuery.post wpt_editor_ajax.url, data, (response) =>
			@editor.list.add response
			new wpt_production production for production in @editor.item.find('.wpt_editor_productions').children()
			@editor.done()
	
	category: (category='') ->
		@editor.list.filter (item) ->
			if category==''
				true
			else
				categories = item.values().categories
				search = '>'+category+'</li>'
				categories? and categories.indexOf(search) > -1

class wpt_production

	constructor:(@production) ->
			
	edit: ->
	
	save: ->
	

jQuery ->
	wpt_editor = new wpt_editor jQuery '#wpt_editor'
