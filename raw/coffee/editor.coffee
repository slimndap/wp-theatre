class wpt_editor

	constructor: (@item) ->
		@productions = new wpt_productions @
		@events = new wpt_events @
		@production_create_form = new wpt_production_create_form @

		@categories()
		@seasons()
		#@datepickers()

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
		
	###
	Reset the editor to the default state.
	
	Close all production forms.
	Reset the production create form.
	###
	reset: () ->
		@productions.close()
		@production_create_form.reset()

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
		@season_filters = @item.find '.wpt_editor_filters .seasons li a'
		@season_filters.click (e) =>
			filter = jQuery e.currentTarget
			if filter.hasClass 'active'
				filter.removeClass 'active'
				@productions.season()
			else
				filter.addClass 'active'
				@productions.season filter.text()
			false

	datepickers: ->
		date = new WPT_Editor_Date()
		@item.find('input[name=event_date_date], input[name=enddate_date]').datepicker
			dateFormat: date.date_format()
			defaultDate: date.object()
			onSelect: (dateText,inst)->
				input = jQuery @
				if input.attr('name') is 'event_date_date'
					enddate = new WPT_Editor_Date()
					enddate.import input, input.parent().find('[name=event_date_time]')
					enddate.datetime += wpt_editor_ajax.default_duration * 1
					
					input.parent().find('[name=enddate_date]').val enddate.date()
					input.parent().find('[name=enddate_time]').val enddate.time()
					
###
Form to create a new production.
###
class wpt_production_create_form

	constructor: (@editor) ->
		@form = @editor.item.find '#wpt_editor_production_form_create'
		@title = @form.find '[name=title]'
		
		###
		Open the form as soon as the title gets the focus.
		Close the production edit form in the productions list.
		###
		@title.focus =>
			@open()
			@editor.productions.close()
		
		###
		Save the new production.
		Block the regular submission of the form.
		###
		@form.find('form').submit =>
			@save()
			false
			
		###
		Close the form when the reset button is clicked.
		###
		@reset_button = @form.find ':reset'
		@reset_button.click =>
			@close()
			
		@init()

	###
	Initialize the form.
	Set the title placeholder based on the contents of the productions list.
	Close the form, ready to receive input.
	###
	init: ->
		if @editor.productions.list.items.length > 0
			@title.attr 'placeholder', wpt_editor_ajax.start_typing
		else 
			@title.attr 'placeholder', wpt_editor_ajax.start_typing_first

		###
		Add a datetime control.
		###
		@datetime = new WPT_Editor_Datetime_Control @form
		
		###
		Add a tickets status control.
		###
		@tickets_status = new WPT_Editor_Status_Control @form

		###
		Add a thumbnail control
		###
		@thumbnail = new WPT_Editor_Thumbnail_Control @form
		
		@close()

	###
	Open the form.
	###
	open : ->
		@form.removeClass 'closed'
		
	###
	Close the form.
	Remove the focus from the title input so it can trigger @open() 
	as soon as it gets the focus again.
	###
	close : ->
		@form.addClass 'closed'
		@title.blur()

	###
	Reset the form to the default state.
	Use the reset button to clear all inputs and close the form.
	###
	reset : ->
		@reset_button.click()


	###
	Create the new production.
	Submit the production and event data to the server.
	Add the new production to the list object.
	###
	save : ->
	
		###
		Collect the event data
		###
		[event_date, enddate] = @datetime.value()

		event_data = 
			'event_date': event_date + wpt_editor_ajax.gmt_offset * 60 * 60
			'enddate': enddate + wpt_editor_ajax.gmt_offset * 60 * 60
			'venue' : @form.find('input[name=venue]').val()
			'city' : @form.find('input[name=city]').val()
			'prices' : @form.find('[name=prices]').val()
			'tickets_url' : @form.find('input[name=tickets_url]').val()
			'tickets_button' : @form.find('input[name=tickets_button]').val()
			'tickets_status' : @tickets_status.value()
			
		###
		Create the production data
		###
		data =
			'wpt_nonce': wpt_editor_ajax.wpt_nonce
			'action': 'save'
			'title' : @form.find('input[name=title]').val()
			'thumbnail' : @thumbnail.value()
			'excerpt' : @form.find('textarea[name=excerpt]').val()
			'categories' : @form.find('select[name=categories\\[\\]]').val()
			'season' : @form.find('select[name=season]').val()
			'events' : [event_data]
		
		console.log data
		
		@editor.busy()
		
		###
		Submit the data to the server.
		###
		jQuery.post wpt_editor_ajax.url, data, (response) =>
			if response?
				###
				When succesful, add the new production to the productions list.
				###
				@editor.productions.list.add response
				
				###
				Re-activate all productions.
				###
				@editor.productions.activate()
				
			
			###
			Re-initialize the productions list.
			The list needs to be activated if this is the first production that was added.
			###
			@editor.productions.init()
			
			###
			Clear the form and close it.
			###
			@reset()
			
			@editor.done()


class wpt_productions

	constructor: (@editor) ->
		options = 
			listClass: 'list'
			item: 'wpt_editor_production_template'
			searchClass: 'wpt_editor_search'
		@list = new List 'wpt_editor_productions', options

		@load()
		@form = @editor.item.find '#wpt_editor_production_form_template'
	
		@init()
	
	init: ->
		if @list.items.length > 0
			@editor.item.find('.wpt_editor_list').addClass 'activated'
		else 
			@editor.item.find('.wpt_editor_list').removeClass 'activated'
		
	
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
			@editor.production_create_form.reset()
			@init()
	
	activate: () ->
		@editor.item.find('.production > .actions a').unbind('click').click (e) =>
			action = jQuery(e.currentTarget).parent()
			production = action.parents '.production'
			if action.hasClass 'edit_link' then @edit production
			if action.hasClass 'delete_link' then @delete production
			if action.hasClass 'view_link' then @view production
			false	
		@form.find('> a.close').unbind('click').click (e) =>
			@close()
			@save()
			false
		@form.find('form').submit (e) ->
			false
		
	close:  ->
		@form.parents('.production').removeClass 'edit'
	
	edit: (production) ->
		@editor.reset()
		@editor.item.find('.production.edit').removeClass 'edit'
		production.addClass 'edit'
		id = production.find('>.hidden .ID').text()
		values = @list.get('ID',id)[0].values()
		
		production.find('.form').append @form
		@form.find('input[name=ID]').val id
		@form.find('input[name=title]').val values.title
		@form.find('textarea[name=excerpt]').val values.excerpt
		@form.find('select[name=categories]').val values.categories
		@form.find('select[name=season]').val values.season
		
		###
		Add a thumbnail control
		###
		@thumbnail = new WPT_Editor_Thumbnail_Control @form

		###
			Load events
		###
		@editor.events.load id


	delete: (production) ->
		id = production.find('>.hidden .ID').text()
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
				@editor.reset()
				@init()

			
	view: (production) ->
		window.open production.find('.view_link a').attr 'href'
	
	save: () ->
		id = @form.find('input[name=ID]').val()

		event_values = (item.values() for item in @editor.events.list.items)

		data =
			'wpt_nonce': wpt_editor_ajax.wpt_nonce
			'action': 'save'
			'ID' : id
			'title' : @form.find('input[name=title]').val()
			'excerpt' : @form.find('textarea[name=excerpt]').val()
			'categories' : @form.find('select[name=categories\\[\\]]').val()
			'season' : @form.find('select[name=season]').val()
			'events' : event_values
		
		@editor.busy()
		jQuery.post wpt_editor_ajax.url, data, (response) =>
			@list.get('ID',id)[0].values(response)
			@activate()
			@init()

			###
				Load events
			###
			@editor.events.load id
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
		
		@form = @editor.productions.form.find '.wpt_editor_event_form'
		@events = @editor.productions.form.find '#wpt_editor_events'
		
	load: (production) ->
		data =
			'action': 'events'
			'production': production
			'wpt_nonce': wpt_editor_ajax.wpt_nonce

		@editor.busy()
		jQuery.post wpt_editor_ajax.url, data, (response) =>
			if response?
				@list.clear()
				@list.add response
				@activate()
			@editor.done()
			
	activate: ->
		@events.find('.actions a').unbind('click').click (e) =>
			action = jQuery(e.currentTarget).parent()
			event = action.parents '.event'
			if action.hasClass 'edit_link' then @edit event
			if action.hasClass 'delete_link' then @delete event
			false

		@form.find('a.close').unbind('click').click (e) =>
			@close()
			false

		@events.find('.add a').unbind('click').click (e) =>
			action = jQuery(e.currentTarget)
			if action.hasClass 'add_link' then @add()
			if action.hasClass 'save_link' then @close()
			if action.hasClass 'cancel_link' then @reset()
			false

	add: () ->
		@reset()
		
		add = @events.find('.add')
		add.prepend @form
		add.addClass 'edit'

	edit: (event) ->
		event.append @form
		
		@reset()
	
		id = event.find('.ID').text()
		values = @list.get('ID',id)[0].values()

		event_date = new WPT_Editor_Date values.event_date - wpt_editor_ajax.gmt_offset * 60 * 60
		@form.find('input[name=event_date_date]').val event_date.date()
		@form.find('input[name=event_date_time]').val event_date.time()
		@form.find('input[name=event_date_date]').datepicker 'setDate', event_date.object()
		
		enddate = new WPT_Editor_Date values.enddate - wpt_editor_ajax.gmt_offset * 60 * 60
		@form.find('input[name=enddate_date]').val enddate.date()
		@form.find('input[name=enddate_time]').val enddate.time()
		@form.find('input[name=enddate_date]').datepicker 'setDate', enddate.object()

		@form.find('input[name=event_id]').val id
		@form.find('input[name=venue]').val values.venue
		@form.find('input[name=city]').val values.city
		@form.find('input[name=tickets_url]').val values.tickets_url
		@form.find('input[name=tickets_button]').val values.tickets_button

		@tickets_status = new WPT_Editor_Status_Control @form
		@tickets_status.value values.tickets_status
		
		@datetime = new WPT_Editor_Datetime_Control @form
		@datetime.value values.event_date - wpt_editor_ajax.gmt_offset*60*60, values.enddate - wpt_editor_ajax.gmt_offset*60*60

		event.addClass 'edit'
		

	delete: (event) ->
		id = event.find('.ID').text()
		
		values = @list.get('ID',id)[0].values()

		title = jQuery(values.datetime_html).find('.wp_theatre_event_date').text()
		title+= ' '
		title+= jQuery(values.datetime_html).find('.wp_theatre_event_time').text()
		confirm_message = wpt_editor_ajax.confirm_message_event.replace /%s/g, title

		if confirm confirm_message
			@list.remove 'ID', id

	reset: () ->
		@events.find('.edit').removeClass 'edit'
		
		###
			Set form inputs to defaults.
		###
		event_date = new WPT_Editor_Date()
		enddate = new WPT_Editor_Date()
		enddate.datetime += wpt_editor_ajax.default_duration * 1

		@form.find('input[name=event_id]').removeAttr 'value'
		@form.find('input[name=event_date_date]').val event_date.date()
		@form.find('input[name=event_date_time]').val event_date.time()
		@form.find('input[name=enddate_date]').val enddate.date()
		@form.find('input[name=enddate_time]').val enddate.time()
		@form.find('input[name=venue]').removeAttr 'value'
		@form.find('input[name=city]').removeAttr 'value'
		@form.find('input[name=tickets_url]').removeAttr 'value'
		@form.find('input[name=tickets_button]').removeAttr 'value'
		
		@activate()
	
	close: () ->
		[event_date, enddate] = @datetime.value()
		
		values =
			event_date: event_date + wpt_editor_ajax.gmt_offset * 60 * 60
			enddate: enddate + wpt_editor_ajax.gmt_offset * 60 * 60
			venue: @form.find('input[name=venue]').val()
			city: @form.find('input[name=city]').val()
			tickets_url: @form.find('input[name=tickets_url]').val()
			tickets_button: @form.find('input[name=tickets_button]').val()
			tickets_status: @tickets_status.value()
			edit_link: '<a href="#">Edit</a>'
			delete_link: '<a href="#">Delete</a>'

		values[wpt_editor_ajax.order_key] = values.event_date

		id = @form.find('input[name=event_id]').val()
		if id? and id isnt ''
			item = @list.get('ID',id)[0]
			item.values values
		else
			@list.add values

		@list.sort wpt_editor_ajax.order_key

		@reset()
		

class WPT_Editor_Date
	constructor:(@datetime) ->
		@datetime ?= wpt_editor_ajax.default_date - wpt_editor_ajax.gmt_offset * 60 * 60
	
	object: () ->
		new Date @datetime * 1000
	
	date: () ->
		jQuery.datepicker.formatDate @date_format(), @object()
		
	time: () ->
		hours = @object().getHours()
		hours = '0' + hours if hours < 10

		minutes = @object().getMinutes()
		minutes = '0' + minutes if minutes < 10
				
		hours + ':' + minutes
		
	date_format: () ->
		format = wpt_editor_ajax.date_format
		translate = 
			'd' : 'dd'
			'D' : 'D'
			'j' : 'd'
			'l' : 'DD'
			'N' : ''
			'S' : ''
			'w' : ''
			'z' : 'o'
			'W' : ''
			'F' : 'MM'
			'm' : 'mm'
			'M' : 'M'
			'n' : 'm'
			't' : ''
			'L' : ''
			'o' : ''
			'Y' : 'yy'
			'y' : 'y'	
		format = format.replace php, javascript for php,javascript of translate
		return format
	
	import: (formatted_date, formatted_time) ->		
		@datetime  = (jQuery.datepicker.formatDate '@',formatted_date.datepicker 'getDate') // 1000

		[hours, minutes] = formatted_time.val().split ':'
		if (jQuery.isNumeric hours) and (jQuery.isNumeric minutes)
			@datetime += hours*60*60 + minutes*60
		return @
		
class WPT_Editor_Datetime_Control
	constructor: (@form) ->
	
		date = new WPT_Editor_Date()		
	
		@event_date_date = @form.find 'input[name=event_date_date]'
		@event_date_time = @form.find 'input[name=event_date_time]'
		@enddate_date = @form.find 'input[name=enddate_date]'
		@enddate_time = @form.find 'input[name=enddate_time]'
		
		@event_date_date.datepicker 'destroy'
		@event_date_date.datepicker
			dateFormat: date.date_format()
			defaultDate: date.object()
			onSelect: (DateText, inst) =>
				enddate = new WPT_Editor_Date()
				enddate.import(@event_date_date, @event_date_time).datetime += wpt_editor_ajax.default_duration * 1
				
				@enddate_date.val enddate.date()
				@enddate_time.val enddate.time()
				
				@sanitize()

				@enddate_date.datepicker 'option', 'minDate', enddate.object()
		
		@enddate_date.datepicker 'destroy'
		@enddate_date.datepicker
			dateFormat: date.date_format()
			defaultDate: date.object()
			minDate: @event_date_date.datepicker 'getDate'
			onSelect: (DateText, inst) =>
				@sanitize()
				
		@event_date_time.change =>
			@sanitize()

		@enddate_time.change =>
			@sanitize()
	
	sanitize: ->
		event_date = new WPT_Editor_Date()
		enddate = new WPT_Editor_Date()

		###
		Time input must be like 00:00.
		###
		@event_date_time.val event_date.import(@event_date_date,@event_date_time).time()
		@enddate_time.val enddate.import(@enddate_date,@enddate_time).time()
		
		###
		Enddate must be later than event_date.
		###
		if enddate.datetime<event_date.datetime
			enddate.datetime = event_date.datetime + wpt_editor_ajax.default_duration*1
			@enddate_date.val enddate.date()
			@enddate_time.val enddate.time()

	value: (event_date, enddate) ->
		if event_date?
			event_date = new WPT_Editor_Date event_date
			@form.find('input[name=event_date_date]').val event_date.date()
			@form.find('input[name=event_date_time]').val event_date.time()
			@form.find('input[name=event_date_date]').datepicker 'setDate', event_date.object()
		else 
			event_date = new WPT_Editor_Date()
			
		if enddate?
			enddate = new WPT_Editor_Date enddate
			@form.find('input[name=enddate_date]').val enddate.date()
			@form.find('input[name=enddate_time]').val enddate.time()
			@form.find('input[name=enddate_date]').datepicker 'setDate', enddate.object()
		else
			enddate = new WPT_Editor_Date()
	
		[
			event_date.import(@event_date_date,@event_date_time).datetime
			enddate.import(@enddate_date,@enddate_time).datetime
		]

class WPT_Editor_Status_Control

	constructor: (@form) ->
		@control = @form.find '.tickets_status_control'
		@select = @control.find 'select[name=tickets_status]'
		@other = @control.find 'input[name=tickets_status_other]'

		@select.change =>
			@sanitize()
	
	sanitize: ->
		if @select.val() is wpt_editor_ajax.tickets_status_other then @other.show().focus() else @other.hide()
		
	value: (tickets_status) ->
		if tickets_status?
			option = @select.find 'option[value='+tickets_status+']'
			if option.length
				alert tickets_status
				@select.val tickets_status
				@other.removeAttr 'value'			
			else 
				@select.val wpt_editor_ajax.tickets_status_other
				@other.val tickets_status
			
		@sanitize()

		if @select.val() is wpt_editor_ajax.tickets_status_other then @other.val() else @select.val()
	
class WPT_Editor_Thumbnail_Control

	constructor: (@form) ->
		@control = @form.find '.thumbnail_control'
		@input = @control.find 'input[name=thumbnail]'
		@img = @control.find 'img'
		
		@control.click =>
			wp.media.editor.send.attachment = (props, attachment) =>
				@input.val attachment.id
				@img.attr 'src', attachment.sizes.thumbnail.url
			wp.media.editor.open @
	
	value: ->
		@input.val()
	
jQuery ->
	editor = jQuery '#wpt_editor'
	wpt_editor = new wpt_editor jQuery '#wpt_editor' if editor.length 
