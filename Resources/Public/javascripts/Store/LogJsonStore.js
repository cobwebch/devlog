Ext.ns("TYPO3.Devlog.Store");

TYPO3.Devlog.initLogJsonStore = function() {
	return new Ext.data.DirectStore({
		// @todo
//		url: '',
		autoLoad: true,
		idProperty: 'uid',
		root: 'data',
		fields: [
			{name: 'uid', type:'int'},
			{name: 'pid', type:'int'},
			{name: 'crdate', type:'date', dateFormat:'timestamp'},
			{name: 'crmsec', type:'date', dateFormat:'timestamp'},
			{name: 'cruser_id', type:'int'},
			{name: 'severity', type:'int'},
			{name: 'extkey', type:'string'},
			{name: 'msg', type:'string'},
			{name: 'location', type:'string'},
			{name: 'line', type:'string'},
			{name: 'data_var', type:'string'}
		]
	});
}
