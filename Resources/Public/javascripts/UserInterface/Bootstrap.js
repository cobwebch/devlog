Ext.ns("TYPO3.Devlog.UserInterface");

// TODO: DOKU FOR TYPO3.Devlog.UserInterface.container;

TYPO3.Devlog.UserInterface.Bootstrap = Ext.apply(new TYPO3.Devlog.Application.AbstractBootstrap, {
	initialize: function() { // TODO: Call like object lifecycle method in FLOW3!
		TYPO3.Devlog.Application.on('TYPO3.Devlog.Application.afterBootstrap', this.initContainer, this);
	},
	initContainer: function() {
		TYPO3.Devlog.UserInterface.container = new TYPO3.Devlog.UserInterface.Layout();
	}
});

TYPO3.Devlog.Application.registerBootstrap(TYPO3.Devlog.UserInterface.Bootstrap);