Ext.ns("TYPO3.Devlog.Listing");

/**
 * Button of the rootline menu
 * @class TYPO3.Devlog.Listing.LogGrid
 * @extends Ext.LogGrid
 */
TYPO3.Devlog.Listing.LogGrid = Ext.extend(Ext.grid.GridPanel, {
	
	/**
	 * Event triggered after initialization of the main area.
	 *
	 * @event TYPO3.Devlog.Listing.LogGrid.afterInit
	 *
	 */
	initComponent: function() {

		// Init the row expander plugin
		this._initRowExpander();

		var config = {
			store: TYPO3.Devlog.Store.LogStore,
			columns: this._getColumns(),
			stripeRows: true,
			columnLines: true,
			autoExpandColumn: 'msg',
			height: 350,
			width: 'auto',
			plugins: TYPO3.Devlog.UserInterface.expander,

			// loading mask configuration
			loadMask: {
				msg: TYPO3.Devlog.Language.loading
			},

			// custom  view configuration
			viewConfig: {
//				enableRowBody: true,
//				showPreview: true,
//				getRowClass : function(record, rowIndex, p, store){
//					if(this.showPreview){
//						p.body = '<p>' + record.data.data_var + '</p>';
//						return 'x-grid3-row-expanded';
//					}
//					return 'x-grid3-row-collapsed';
//				}
			},

			// Top Bar
			tbar: [
				{
					xtype: 'TYPO3.Devlog.Listing.TimeList',
					ref: '../timeList'
				},
				{
					xtype: 'TYPO3.Devlog.Listing.SeverityList',
					ref: '../severityList'
				},
				{
					xtype: 'TYPO3.Devlog.Listing.ExtensionList',
					ref: '../extensionList'
				},
				{
					xtype: 'TYPO3.Devlog.Listing.PageList',
					ref: '../pageList'
				},
				'-',
				{
					pressed: false,
					enableToggle: true,
					text: TYPO3.Devlog.Language.expand_all_extra_data,
					cls: 'x-btn-text-icon details',
					toggleHandler: this.ontoggleexpand
				},
				{
					pressed: false,
					enableToggle: true,
					text: TYPO3.Devlog.Language.auto_refresh,
					cls: 'x-btn-text-icon details',
					toggleHandler: this.ontoggleautorefresh
				}

			],

			// Button Bar
			bbar: [
				new Ext.PagingToolbar({
					store: TYPO3.Devlog.Store.LogStore,	   // grid and PagingToolbar using same store
					displayInfo: true,
					pageSize: TYPO3.Devlog.Preferences.pageSize,
					prependButtons: true,
					ref: '../pagebrowser'
				}),
				'->',
				TYPO3.Devlog.Data.LogPeriod
			]
		};
		
		Ext.apply(this, config);
		TYPO3.Devlog.Listing.LogGrid.superclass.initComponent.call(this);
		TYPO3.Devlog.Application.fireEvent('TYPO3.Devlog.Listing.afterInit', this);

		// Adds behaviour when grid is refreshed.
		this.getView().on(
			'refresh',
			this.onrefresh,
			this.getView()
		);

		this.on(
			'afterrender',
			this.onafterrender,
			this
		);
	},

	/**
	 * Hides the "collapse / expand" "+"when no data_var is defined
	 *
	 * @access public
	 * @method onrefresh
	 * @return void
	 */
	onrefresh: function() {
		var numberOfRows = TYPO3.Devlog.Store.LogStore.getCount();

		for (index = 0; index < numberOfRows; index++) {

			var row = this.getRow(index);
			var record = TYPO3.Devlog.Store.LogStore.getAt(index);

			// if no data is found, hides the "+"
			if (!record.data['has_data_var']) {
				// Fetches DOM element
				var expanderButton = Ext.query('div.x-grid3-row-expander', row)[0];
				Ext.get(expanderButton).removeClass('x-grid3-row-expander')
			}
		}

		// Fix a visual bug.
		// If the "+" has already been hit, it will never come back to "+" again by click on the refreh button
		Ext.each(Ext.select('.x-grid3-row-expanded').elements, function(element) {
			Ext.get(element).removeClass('x-grid3-row-expanded').addClass('x-grid3-row-collapse');
		});


		// Binding an event on crdate link to filter log run at the same time
		Ext.select('.devlog-link-crdate').on('click', function() {
			var uid = this.id.replace('devlog-link-generated-', '');
			var record = TYPO3.Devlog.Store.LogStore.getById(uid);
			TYPO3.Devlog.Store.LogStore.baseParams.limit = record.data['crmsec'];
			TYPO3.Devlog.UserInterface.container.logGrid.pagebrowser.pageSize = record.data['crmsec'];
			TYPO3.Devlog.Store.LogStore.load();
			TYPO3.Devlog.UserInterface.container.logGrid.timeList.fireEvent('onclickoncrdatecell');
		});
	},

	/**
	 * Collapse / expand data_var
	 *
	 * @access public
	 * @method ontoggleexpand
	 * @param {Object} button
	 * @param {bool} pressed
	 * @return void
	 */
	ontoggleexpand: function(button, pressed){

		var view = TYPO3.Devlog.UserInterface.container.logGrid.getView();
		var numberOfRows = TYPO3.Devlog.Store.LogStore.getCount();

		for (index = 0; index < numberOfRows; index++) {
			var row = view.getRow(index);
				var expander = Ext.query('div.x-grid3-row-expander', row)[0];
				if (expander) {
					TYPO3.Devlog.Utils.fireEvent('mousedown', expander);
				}
		}
	},

	/**
	 * Auto refresh data
	 *
	 * @access public
	 * @method ontoggleexpand
	 * @param {Object} button
	 * @param {bool} pressed
	 * @return void
	 */
	ontoggleautorefresh: function(button, pressed){
		var task = {
			run: function(){
				// Basic request in Ext
				Ext.Ajax.request({
				   url: '/typo3/ajax.php',
				   params: {
					   ajaxID: 'LogController::getLastLogTime'
				   },
				   success: function(response){
					   if (TYPO3.Devlog.Data.LastLogTime != response.responseText) {
							TYPO3.Devlog.Data.LastLogTime = response.responseText;
							TYPO3.Devlog.Store.LogStore.load();
					   }
				   }
				});
			},
			interval: 1500
		}
		if (pressed) {
			Ext.TaskMgr.start(task);
		}
		else {
			Ext.TaskMgr.stopAll();
		}

	},

	/**
	 * Renders the severity column
	 *
	 * @access private
	 * @method _renderSeverity
	 * @param {int} value: -1 OK, 0 Info, 1 Notice, 2 Warning, 3 Error
	 * @return string
	 */
	_renderSeverity: function(value) {
		var spriteName = 'extensions-devlog-';
		switch (value) {
			case -1 : // OK
				spriteName += 'ok';
				break;
			case 0 : // Info
				spriteName += 'info';
				break;
			case 1 : // Notice
				spriteName += 'notification';
				break;
			case 2 : // Warning
				spriteName += 'warning';
				break;
			case 3 : // Error
				spriteName += 'error';
				break;
		}
		return String.format((spriteName) ? TYPO3.Devlog.Utils.getSpriteIcon(spriteName) : '');
	},

	/**
	 * Renders the "called from" column
	 *
	 * @access private
	 * @method _renderLocation
	 * @param {string} value
	 * @param {Object} parent
	 * @param {Object} record
	 * @return string
	 */
	_renderLocation: function(value, parent, record) {
		return String.format('{0}<br/>' + TYPO3.Devlog.Language.line + ' {1}', value, record.data['line']);
	},

	/**
	 * Renders the "crdate" column
	 *
	 * @access private
	 * @method _renderLocation
	 * @param {string} value
	 * @param {Object} parent
	 * @param {Object} record
	 * @return string
	 */
	_renderCrdate: function(value, parent, record) {
		var format = TYPO3.Devlog.Preferences.dateFormat + ' ' + TYPO3.Devlog.Preferences.timeFormat;
		var result = Ext.util.Format.date(record.data['crdate'], format);
		return '<a href="#" class="devlog-link-crdate" id="devlog-link-generated-' + record.id + '" onclick="return false">' + result + '</a>';
	},

	/**
	 * Returns the configuration array
	 *
	 * @access private
	 * @method _getColumns
	 * @return array
	 */
	_getColumns: function() {
		var columns = [
			TYPO3.Devlog.UserInterface.expander,
			{
				id: 'uid',
				dataIndex: 'uid',
				header: 'UID',
				width: 30,
				sortable: true
			},
			{
				id: 'crdate',
				dataIndex: 'crdate',
				header: TYPO3.Devlog.Language.crdate,
				sortable: true,
				renderer: this._renderCrdate
			},
			{
				id: 'severity',
				dataIndex: 'severity',
				header: TYPO3.Devlog.Language.severity,
				renderer: this._renderSeverity,
				width: 60,
				sortable: true
			},
			{
				id: 'extkey',
				dataIndex: 'extkey_formatted',
				header: TYPO3.Devlog.Language.extkey,
				sortable: true
			},
			{
				id: 'msg',
				dataIndex: 'msg',
				header: TYPO3.Devlog.Language.msg,
				renderer: this._renderMessage
			},
			{
				id: 'location',
				dataIndex: 'location',
				header: TYPO3.Devlog.Language.location,
				width: 200,
				renderer: this._renderLocation
			},
			{
				id: 'page',
				dataIndex: 'pid_formatted',
				header: TYPO3.Devlog.Language.pid,
				width: 50,
				sortable: true
			},
			{
				id: 'user',
				dataIndex: 'cruser_formatted',
				header: TYPO3.Devlog.Language.cruser_id,
				width: 50,
				sortable: true
			},
			{
				id: 'line',
				dataIndex: 'line',
				header: TYPO3.Devlog.Language.line,
				hidden: true
			},
		];
		return columns;
	},

	/**
	 * Initialize the row expander
	 *
	 * @method _initRowExpander
	 * @return void
	 */
	_initRowExpander: function() {
		TYPO3.Devlog.UserInterface.expander = new TYPO3.Devlog.UserInterface.AjaxRowExpander();

		// Row expander without ajax
		//TYPO3.Devlog.UserInterface.expander = new TYPO3.Devlog.UserInterface.RowExpander({
		//		tpl : new Ext.Template(
		//			'{data_var}'
		//		)
		//});
	},

	/**
	 * Resizes the grid to fit the window
	 *
	 * @method onafterrender
	 * @return void
	 */
	onafterrender: function() {
		// 120 is an empiric value... maybe a better way to implement that ;)
		this.setHeight(window.innerHeight - 120);
	}

});

Ext.reg('TYPO3.Devlog.Listing.LogGrid', TYPO3.Devlog.Listing.LogGrid);