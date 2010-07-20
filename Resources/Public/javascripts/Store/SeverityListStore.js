Ext.ns("TYPO3.Devlog.Store");

TYPO3.Devlog.Store.initSeverityListStore = function() {
	return new Ext.data.ArrayStore({
		fields: ['key', 'value'],
		data : TYPO3.Devlog.Data.SeverityList
	});
}
