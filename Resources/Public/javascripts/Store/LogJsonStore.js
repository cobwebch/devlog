Ext.ns("TYPO3.Devlog.Store");

TYPO3.Devlog.initLogJsonStore = function() {
	return new Ext.data.JsonStore({
		storeId: 'logStore',
		autoLoad: true,
		remoteSort: true,
		baseParams: {
			ajaxID: 'LogController::indexAction',
			limit: TYPO3.Devlog.Preferences.pageSize
		},
		proxy: new Ext.data.HttpProxy({
			method: 'GET',
			url: '/typo3/ajax.php'
		}),

		listeners : {
			load: function (element, data) {
				// Decides whether to sort server side or client side
//				if (element.reader.jsonData.total > element.getCount()) {
//					element.remoteSort = true;
//				}
//				else {
//					element.remoteSort = false;
//				}

				// @debug like a double click on the first row
//				var sm = Contact.grid.getSelectionModel();
//				sm.selectFirstRow();
//				Contact.window.edit('single');
			}
		}
	});
}
