Ext.ns("TYPO3.Devlog.UserInterface");


   var myData = [
        ['3m Co',71.72,0.02,0.03,'9/1 12:00am'],
        ['Alcoa Inc',29.01,0.42,1.47,'9/1 12:00am'],
        ['Altria Group Inc',83.81,0.28,0.34,'9/1 12:00am']
    ];


 // create the data store
    var store = new Ext.data.ArrayStore({
        fields: [
           {name: 'company'},
           {name: 'price', type: 'float'},
           {name: 'change', type: 'float'},
           {name: 'pctChange', type: 'float'},
           {name: 'lastChange', type: 'date', dateFormat: 'n/j h:ia'}
        ]
    });

    // manually load local data
    store.loadData(myData);


/**
 * Button of the rootline menu
 * @class TYPO3.Devlog.UserInterface.LogPanel
 * @extends Ext.LogPanel
 */
TYPO3.Devlog.UserInterface.LogPanel = Ext.extend(Ext.grid.GridPanel, {
	enableToggle: true,

	initComponent: function() {
		var config = {

			store: TYPO3.Devlog.LogStore,
			columns: [
//				{id:'name',header: 'Name', width: 160, sortable: true, dataIndex: 'name'},
				{id:'source',header: 'Company', width: 160, sortable: true},
			],
			stripeRows: true,
//			autoExpandColumn: 'company',
			height: 350,
			width: 'auto',
			title: 'Array Grid'
		};
		Ext.apply(this, config);
		TYPO3.Devlog.UserInterface.LogPanel.superclass.initComponent.call(this);
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
//
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

	getFullPath: function() {
		return this.menuId + '-' + this.sectionId + '-' + this.menuPath;
	}

});
Ext.reg('TYPO3.Devlog.UserInterface.LogPanel', TYPO3.Devlog.UserInterface.LogPanel);