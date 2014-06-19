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
		@form = @editor.item.find '#wpt_editor_production_form_template'
		
	load : ->
		data =
			'action': 'productions'

		@editor.busy()
		jQuery.post wpt_editor_ajax.url, data, (response) =>
			@editor.list.add response
			@activate()
			@editor.done()
	
	activate: () ->
		@editor.item.find('.actions a').unbind('click').click (e) =>
			action = jQuery(e.currentTarget).parent()
			production = action.parents '.production'
			if action.hasClass 'edit_link' then @edit production
			if action.hasClass 'delete_link' then @delete production
			if action.hasClass 'view_link' then @view production
			false	
		@form.find('a.close').unbind('click').click (e) =>
			@close jQuery(e.currentTarget).parents '.production'
			false
		@form.find('input, textarea').unbind('change').change (e) =>
			@save jQuery(e.currentTarget).parents '.production'
		
	close: (production) ->
		production.removeClass 'edit'
	
	edit: (production) ->
		@editor.item.find('.production.edit').removeClass 'edit'
		production.addClass 'edit'
		id = production.find('.ID').text()
		values = @editor.list.get('ID',id)[0].values()
		
		production.find('.form').append @form
		@form.find('#wpt_editor_production_form_title').val values.title
		@form.find('#wpt_editor_production_form_excerpt').val values.excerpt
		

	delete: (production) ->
		id = production.find('.ID').text()
		values = @editor.list.get('ID',id)[0].values()

		confirm 'Are you sure you want to delete \''+values.title+'\'?'
	view: (production) ->
		window.open production.find('.view_link a').attr 'href'
	
	save: (production) ->
		id = production.find('.ID').text()

		data =
			'action': 'save'
			'ID' :  id
			'title' : @form.find('#wpt_editor_production_form_title').val()
			'excerpt' : @form.find('#wpt_editor_production_form_excerpt').val()
		
		@editor.busy()
		jQuery.post wpt_editor_ajax.url, data, (response) =>
			@editor.list.get('ID',id)[0].values(response)
			@activate()
			@editor.done()
		console.log 'save'
		
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
		@actions()
		
	actions: () ->
		console.log @production.find('.actions .edit_link a')
		@production.find('.actions .edit_link .a').hide()
	edit: ->
	
	save: ->
	

jQuery ->
	wpt_editor = new wpt_editor jQuery '#wpt_editor'
