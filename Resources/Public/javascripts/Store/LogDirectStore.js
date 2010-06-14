Ext.ns("TYPO3.Devlog.Store");

TYPO3.Devlog.initLogDirectStore = function() {
	return new Ext.data.DirectStore({
		paramsAsHash: true,
		autoLoad: true,
		directFn: TYPO3.Devlog.Remote.indexAction
		// @todo check why it does not work with metaData attribute
//		idProperty: 'uid',
//		root: 'records',
//		totalProperty: 'total',
//		fields: [
//			{name: 'uid', type:'int'},
//			{name: 'pid', type:'int'},
//			{name: 'crdate', type:'date', dateFormat:'timestamp'},
//			{name: 'crmsec', type:'date', dateFormat:'timestamp'},
//			{name: 'cruser_id', type:'int'},
//			{name: 'severity', type:'int'},
//			{name: 'extkey', type:'string'},
//			{name: 'msg', type:'string'},
//			{name: 'location', type:'string'},
//			{name: 'line', type:'string'},
//			{name: 'data_var', type:'string'}
//		]
	});
}
