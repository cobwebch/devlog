Ext.ns("TYPO3.Devlog.Store");

//var store = new Ext.data.JsonStore({
//    url: 'get-images.php',
//    root: 'images',
//    fields: [
//        'name', 'url',
//        {name:'size', type: 'float'},
//        {name:'lastmod', type:'date', dateFormat:'timestamp'}
//    ]
//});
//store.load();

TYPO3.Devlog.initLogStore = function() {
	return new Ext.data.DirectStore({
		paramsAsHash: true,
		autoLoad: true,
		idProperty: 'uid',
		root: 'data',
		directFn: TYPO3.Devlog.Remote.getLogs,
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
