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
//			stripeRows: true,
//			autoExpandColumn: 'msg',
			height: 350,
			width: 'auto',

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
				pageSize: 5,
				prependButtons: true
//				items: [
//					'text 1'
//				]
			})
		};
		
		Ext.apply(this, config);
		TYPO3.Devlog.UserInterface.LogGridPanel.superclass.initComponent.call(this);
		TYPO3.Devlog.Application.fireEvent('TYPO3.Devlog.UserInterface.afterInit', this);

//		this.on('afterrender', function(menu) {
//			console.log(123);
//		});

//		this.enableBubble([
//			'TYPO3.Devlog.UserInterface.buttonPressed',
//			'TYPO3.Devlog.UserInterface.buttonUnpressed'
//		]);

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

	_getColumns: function() {
		var columns = [
			{
				id:'crdate',
				dataIndex:'crdate',
				header: TYPO3.Devlog.Language.crdate,
				sortable: true,
				renderer: Ext.util.Format.dateRenderer('m/d/Y')
			},

			{
				id:'severity',
				dataIndex:'severity',
				header: TYPO3.Devlog.Language.severity,
				sortable: true
			},

			{
				id:'extkey',
				dataIndex:'extkey',
				header: TYPO3.Devlog.Language.extkey,
				sortable: true
			},

			{
				id:'msg',
				dataIndex:'msg',
				header: TYPO3.Devlog.Language.msg,
				width: 160,
				sortable: true
			},

			{
				id:'location',
				dataIndex:'location',
				header: TYPO3.Devlog.Language.location,
				sortable: true
			},

			{
				id:'page',
				header: TYPO3.Devlog.Language.pid,
				width: 160,
				sortable: true
			},

			{
				id:'user',
				header: TYPO3.Devlog.Language.cruser_id,
				width: 160,
				sortable: true
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