Ext.ns("TYPO3.Devlog.UserInterface");

/**
 * Button of the rootline menu
 * @class TYPO3.Devlog.UserInterface.LogGridPanel
 * @extends Ext.LogGridPanel
 */
TYPO3.Devlog.UserInterface.LogGridPanel = Ext.extend(Ext.grid.GridPanel, {
	
	/**
	 * Event triggered after initialization of the main area.
	 *
	 * @event TYPO3.Devlog.UserInterface.LogGridPanel.afterInit
	 *
	 */
	initComponent: function() {
		var config = {
			store: TYPO3.Devlog.LogStore,
			columns: this._getColumns(),
			stripeRows: true,
			columnLines: true,
			autoExpandColumn: 'msg',
			height: 350,
			width: 'auto',
			
			viewConfig: {
				enableRowBody: true,
				showPreview: true,
				getRowClass : function(record, rowIndex, p, store){
					if(this.showPreview){
						p.body = '<p>record.data.excerpt</p>';
						return 'x-grid3-row-expanded';
					}
					return 'x-grid3-row-collapsed';
				}
			},

			// Top Bar
			tbar: [
				{
					xtype: 'button',
					text: 'button'
				},
			],

			// Button Bar
			bbar: new Ext.PagingToolbar({
				store: TYPO3.Devlog.LogStore,       // grid and PagingToolbar using same store
				displayInfo: true,
				pageSize: TYPO3.Devlog.Preferences.pageSize,
				prependButtons: true
			})
		};
		
		Ext.apply(this, config);
		TYPO3.Devlog.UserInterface.LogGridPanel.superclass.initComponent.call(this);
		TYPO3.Devlog.Application.fireEvent('TYPO3.Devlog.UserInterface.afterInit', this);

//		this.on('afterrender', function(menu) {
//			console.log(123);
//		});

//		this.on(
//			'toggle',
//			this.onToogleAction,
//			this
//		);

//		this.on(
//			'mouseover',
//			this.onMouseoverAction,
//			this
//		);
	},

	// private
	/**
	 * Renders the severity column
	 *
	 * @param {int} value: -1 OK, 0 Info, 1 Notice, 2 Warning, 3 Error
	 * @return string
	 */
	_renderSeverity: function(value) {
//		return String.format("{0}",(value==1)?'Yes': 'No');
		return value;
	},

	/**
	 * Renders the "called from" column
	 *
	 * @param {int} value: -1 OK, 0 Info, 1 Notice, 2 Warning, 3 Error
	 * @param {Object} parent
	 * @param {Object} record
	 * @return string
	 */
	_renderLocation: function(value, parent, record) {
		return String.format('{0}<br/>' + TYPO3.Devlog.Language.line + ' {1}', value, record.data['line']);
	},

	/**
	 * Returns the configuration array
	 *
	 * @method _getColumns
	 * @return array
	 */
	_getColumns: function() {
		var columns = [
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
				renderer: Ext.util.Format.dateRenderer(TYPO3.Devlog.Preferences.dateFormat + ' ' + TYPO3.Devlog.Preferences.timeFormat)
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
				dataIndex: 'extkey',
				header: TYPO3.Devlog.Language.extkey,
				sortable: true
			},
			{
				id: 'msg',
				dataIndex: 'msg',
				header: TYPO3.Devlog.Language.msg,
				renderer: this._renderMessage,
				sortable: true
			},
			{
				id: 'location',
				dataIndex: 'location',
				header: TYPO3.Devlog.Language.location,
				width: 200,
				renderer: this._renderLocation,
				sortable: true
			},
			{
				id: 'page',
				dataIndex: 'pid',
				header: TYPO3.Devlog.Language.pid,
				width: 50,
				sortable: true
			},
			{
				id: 'user',
				dataIndex: 'cruser_formated',
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
	}
	
//	/**
//	 * @method onToogleAction
//	 * @param {object} button
//	 * @param {bool} pressed
//	 * @return void
//	 */
//	onToogleAction: function(button, pressed) {
//		if (pressed) {
//			this._onButtonPress(button);
//		} else {
//			this._onButtonUnpress(button);
//		}
//	},

//	_onButtonPress: function(button) {
//		button.ownerCt.items.each(function(item) {
//			if (button.leaf) {
//				if (item.menuLevel === button.menuLevel && item !== button && item.itemId !== 'F3-arrow') {
//					item.el.fadeOut({
//						duration: .4,
//						endOpacity: .5
//					});
//				}
//			} else {
//				if (item.menuLevel === button.menuLevel && item !== button && item.itemId !== 'F3-arrow') {
//					item.el.fadeOut({
//						duration: .4,
//						callback: function() {
//							if (item.pressed) {
//								item.toggle(false);
//							}
//							item.hide();
//						}
//					});
//				}
//				if (item.menuPath.indexOf(button.menuPath + '-') === 0) {
//					item.el.fadeIn({
//						duration: .4,
//						callback: function() {
//							item.show();
//						}
//					});
//				}
//			}
//		}, this);
//		button.fireEvent('TYPO3.Devlog.UserInterface.buttonPressed', this);
//	},
//
//	_onButtonUnpress: function(button) {
//		button.ownerCt.items.each(function(item) {
//			if (button.leaf) {
//				if (item.menuLevel === button.menuLevel && item !== button && item.itemId !== 'F3-arrow') {
//					item.el.fadeIn({
//						duration: .4,
//						startOpacity: .5
//					});
//				}
//			} else {
//				if (item.menuLevel === button.menuLevel && item !== button) {
//					item.el.fadeIn({
//						duration: .4,
//						callback: function() {
//							item.show();
//						}
//					});
//				}
//				if (item.menuPath.indexOf(button.menuPath + '-') === 0) {
//					item.el.fadeOut({
//						duration: .4,
//						callback: function() {
//							if (item.pressed) {
//								item.toggle(false);
//							}
//							item.hide();
//						}
//					});
//				}
//			}
//		}, this);
//		button.fireEvent('TYPO3.Devlog.UserInterface.buttonUnpressed', this);
//	},

//	getFullPath: function() {
//		return this.menuId + '-' + this.sectionId + '-' + this.menuPath;
//	}

});

Ext.reg('TYPO3.Devlog.UserInterface.LogGridPanel', TYPO3.Devlog.UserInterface.LogGridPanel);