Ext.ns("TYPO3.Devlog.Store");

TYPO3.Devlog.initLogStore = function() {
	return new Ext.data.DirectStore({
		paramsAsHash: true,
		autoLoad: true,
	//	idProperty: 'source',
		root: 'data',
		directFn: TYPO3.Devlog.Remote.getLogs,
		fields: [
			{name: 'source'},
			{name: 'description'},
			{name: 'datetime'}
		]
	});
}


/**
 * Button of the rootline menu
 * @class TYPO3.Devlog.Store.LogPanel
 * @extends Ext.LogPanel
 */


Ext.reg('TYPO3.Devlog.Store.LogPanel', TYPO3.Devlog.Store.LogPanel);