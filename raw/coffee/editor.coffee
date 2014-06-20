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
		@seasons()
	
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

	seasons: () ->
		@season_filters = @item.find('.wpt_editor_filters .seasons li a');
		@season_filters.click (e) =>
			filter = jQuery e.currentTarget
			if filter.hasClass 'active'
				filter.removeClass 'active'
				@productions.season()
			else
				filter.addClass 'active'
				@productions.season filter.text()
			false

class wpt_productions

	constructor: (@editor) ->
		@load()
		@form = @editor.item.find '#wpt_editor_production_form_template'
		
	load : ->
		data =
			'action': 'productions'
			'wpt_nonce': wpt_editor_ajax.wpt_nonce

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
		@form.find('input, textarea, select').unbind('change').change (e) =>
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
		@form.find('#wpt_editor_production_form_categories').val values.categories
		@form.find('#wpt_editor_production_form_season').val values.season
		

	delete: (production) ->
		id = production.find('.ID').text()
		values = @editor.list.get('ID',id)[0].values()

		confirm_message = wpt_editor_ajax.confirm_message.replace /%s/g, values.title

		if confirm confirm_message
			data =
				'wpt_nonce': wpt_editor_ajax.wpt_nonce
				'action': 'delete'
				'ID' :  id
			@editor.busy()
			jQuery.post wpt_editor_ajax.url, data, (response) =>
				@editor.list.remove('ID',response)
				@editor.done()

			
	view: (production) ->
		window.open production.find('.view_link a').attr 'href'
	
	save: (production) ->
		id = production.find('.ID').text()

		data =
			'wpt_nonce': wpt_editor_ajax.wpt_nonce
			'action': 'save'
			'ID' :  id
			'title' : @form.find('#wpt_editor_production_form_title').val()
			'excerpt' : @form.find('#wpt_editor_production_form_excerpt').val()
			'categories' : @form.find('#wpt_editor_production_form_categories').val()
			'season' : @form.find('#wpt_editor_production_form_season').val()
		
		@editor.busy()
		jQuery.post wpt_editor_ajax.url, data, (response) =>
			@editor.list.get('ID',id)[0].values(response)
			@activate()
			@editor.done()
		
	category: (category='') ->
		@editor.list.filter (item) ->
			if category==''
				true
			else
				categories = item.values().categories_html
				search = '>'+category+'</li>'
				categories? and categories.indexOf(search) > -1

	season: (season='') ->
		@editor.list.filter (item) ->
			if season==''
				true
			else
				item.values().season_html == season

class wpt_production

	constructor:(@production) ->
		@actions()
		
	actions: () ->
		console.log @production.find('.actions .edit_link a')
		@production.find('.actions .edit_link .a').hide()
	edit: ->
	
	save: ->
	

jQuery ->
	editor = jQuery '#wpt_editor'
	wpt_editor = new wpt_editor jQuery '#wpt_editor' if editor.length 
