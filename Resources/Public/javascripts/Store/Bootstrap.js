Ext.ns("TYPO3.Devlog.Store");

// TODO: DOKU FOR TYPO3.Devlog.Store.viewport;

TYPO3.Devlog.Store.Bootstrap = Ext.apply(new TYPO3.Devlog.Application.AbstractBootstrap, {
	initialize: function() { // TODO: Call like object lifecycle method in FLOW3!
		TYPO3.Devlog.Application.on('TYPO3.Devlog.Application.afterBootstrap', this.initStore, this);
	},
	initStore: function() {
		for (var api in Ext.app.ExtDirectAPI) {
			Ext.Direct.addProvider(Ext.app.ExtDirectAPI[api]);
		}
		TYPO3.Devlog.LogStore = TYPO3.Devlog.initLogDirectStore()
	}
});

TYPO3.Devlog.Application.registerBootstrap(TYPO3.Devlog.Store.Bootstrap);