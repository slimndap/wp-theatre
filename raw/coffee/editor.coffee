class wpt_editor

	constructor: (@item) ->
		@id = @item.attr('id')

		@productions = new wpt_productions @
		@events = new wpt_events @
		
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

		if @productions.list.items.length > 0
			@item.find('.wpt_editor_list').addClass 'activated'
		else 
			@item.find('.wpt_editor_list').removeClass 'activated'
		
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
		options = 
			listClass: 'list'
			item: 'wpt_editor_production_template'
			searchClass: 'wpt_editor_search'
		@list = new List 'wpt_editor_productions', options

		@load()
		@form = @editor.item.find '#wpt_editor_production_form_template'
		
	load : ->
		data =
			'action': 'productions'
			'wpt_nonce': wpt_editor_ajax.wpt_nonce

		@editor.busy()
		jQuery.post wpt_editor_ajax.url, data, (response) =>
			if response?
				@list.add response
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
		@form.find('form').submit (e) ->
			false
		
	close: (production) ->
		production.removeClass 'edit'
	
	edit: (production) ->
		@editor.item.find('.production.edit').removeClass 'edit'
		production.addClass 'edit'
		id = production.find('.ID').text()
		values = @list.get('ID',id)[0].values()
		
		production.find('.form').append @form
		@form.find('input[name=ID]').val id
		@form.find('input[name=title]').val values.title
		@form.find('textarea[name=excerpt]').val values.excerpt
		@form.find('select[name=categories]').val values.categories
		@form.find('select[name=season]').val values.season
		
		###
			Load events
		###
		@editor.events.load id


	delete: (production) ->
		id = production.find('.ID').text()
		values = @list.get('ID',id)[0].values()

		confirm_message = wpt_editor_ajax.confirm_message.replace /%s/g, values.title

		if confirm confirm_message
			data =
				'wpt_nonce': wpt_editor_ajax.wpt_nonce
				'action': 'delete'
				'ID' :  id
			@editor.busy()
			jQuery.post wpt_editor_ajax.url, data, (response) =>
				@list.remove('ID',response)
				@editor.done()

			
	view: (production) ->
		window.open production.find('.view_link a').attr 'href'
	
	save: (production) ->
		id = @form.find('input[name=ID]').val()
		data =
			'wpt_nonce': wpt_editor_ajax.wpt_nonce
			'action': 'save'
			'ID' : id
			'title' : @form.find('input[name=title]').val()
			'excerpt' : @form.find('textarea[name=excerpt]').val()
			'categories' : @form.find('select[name=categories\\[\\]]').val()
			'season' : @form.find('select[name=season]').val()
		
		@editor.busy()
		jQuery.post wpt_editor_ajax.url, data, (response) =>
			@list.get('ID',id)[0].values(response)
			@activate()
			@editor.done()
		
	category: (category='') ->
		@list.filter (item) ->
			if category==''
				true
			else
				categories = item.values().categories_html
				search = '>'+category+'</li>'
				categories? and categories.indexOf(search) > -1

	season: (season='') ->
		@list.filter (item) ->
			if season==''
				true
			else
				item.values().season_html == season

class wpt_events

	constructor:(@editor) ->
		options = 
			listClass: 'list'
			item: 'wpt_editor_event_template'
		@list = new List 'wpt_editor_events', options

	load : (production) ->
		data =
			'action': 'events'
			'production': production
			'wpt_nonce': wpt_editor_ajax.wpt_nonce

		@editor.busy()
		jQuery.post wpt_editor_ajax.url, data, (response) =>
			if response?
				@list.clear()
				@list.add response
			@editor.done()
	

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
