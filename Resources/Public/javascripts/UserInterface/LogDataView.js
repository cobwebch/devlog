Ext.ns("TYPO3.Devlog.UserInterface");

/**
 * Button of the rootline menu
 * @class TYPO3.Devlog.UserInterface.LogDataView
 * @extends Ext.LogDataView
 */
TYPO3.Devlog.UserInterface.LogDataView = Ext.extend(Ext.DataView, {
	/**
	 * @event TYPO3.Devlog.UserInterface.LogDataView.afterInit
	 * @param {TYPO3.Devlog.UserInterface.LogDataView} a reference to the main area,
	 *
	 * Event triggered after initialization of the main area.
	 */
	
	enableToggle: true,
	initComponent: function() {
		var config = {

			store: TYPO3.Devlog.LogStore,
			tpl: new Ext.XTemplate(
				'<table class="t3-list">',
					'<thead>',
					'<tr class="t3-row-header">',
						'<th class="t3-column t3-cell t3-td-1">',
							'<div class="t3-cell-inner">Date</div>',
						'</th>',
						'<th class="t3-column t3-cell t3-td-2">',
							'<div class="t3-cell-inner">Severity</div>',
						'</th>',
						'<th class="t3-column t3-cell t3-td-3">',
							'<div class="t3-cell-inner">Extension</div>',
						'</th>',
						'<th class="t3-column t3-cell t3-td-4">',
							'<div class="t3-cell-inner">Message</div>',
						'</th>',
						'<th class="t3-column t3-cell t3-td-5">',
							'<div class="t3-cell-inner">Called from</div>',
						'</th>',
						'<th class="t3-column t3-cell t3-td-6">',
							'<div class="t3-cell-inner">Page</div>',
						'</th>',
						'<th class="t3-column t3-cell t3-td-7">',
							'<div class="t3-cell-inner">User</div>',
						'</th>',
//						'<th class="t3-column t3-cell t3-td-8" style="">',
//							'<div class="t3-cell-inner">Extra data</div>',
//						'</th>',
					'</tr>',
					'</thead>',
					'<tpl for=".">',
					'<tr>',
						'<td>{crdate}</td>',
						'<td>{severity}</td>',
						'<td>{extkey}</td>',
						'<td>{msg}</td>',
						'<td>{location} line {line}</td>',
					'</tr>',
					'</tpl>',
				'</table>'
			),
			autoHeight:true,
			multiSelect: true,
			overClass:'x-view-over',
			itemSelector:'div.thumb-wrap',
			emptyText: 'No images to display'
//			width: 'auto'

//			store: TYPO3.Devlog.LogStore,
//			columns: [
//				{id:'crdate', dataIndex:'crdate', header: 'Date', sortable: true},
//				{id:'severity', dataIndex:'severity', header: 'Severity', sortable: true},
//				{id:'extkey', dataIndex:'extkey', header: 'Extension', sortable: true},
//				{id:'msg', dataIndex:'msg', header: 'Message', width: 160, sortable: true},
//				{id:'location', dataIndex:'location', header: 'Called from', sortable: true},
////				{id:'page', header: 'Page', width: 160, sortable: true},
////				{id:'user', header: 'User', width: 160, sortable: true},
//			],
//			stripeRows: true,
//			autoExpandColumn: 'msg',
//			height: 350,
//			width: 'auto'
		};
		Ext.apply(this, config);
		TYPO3.Devlog.UserInterface.LogDataView.superclass.initComponent.call(this);
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

//	getFullPath: function() {
//		return this.menuId + '-' + this.sectionId + '-' + this.menuPath;
//	}

});
Ext.reg('TYPO3.Devlog.UserInterface.LogDataView', TYPO3.Devlog.UserInterface.LogDataView);