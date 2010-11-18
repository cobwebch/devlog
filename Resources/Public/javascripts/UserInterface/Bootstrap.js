Ext.ns("TYPO3.Devlog.UserInterface");

TYPO3.Devlog.UserInterface.Bootstrap = Ext.apply(new TYPO3.Devlog.Application.AbstractBootstrap, {
	initialize: function() {
		TYPO3.Devlog.Application.on('TYPO3.Devlog.Application.afterBootstrap', this.initContainer, this);
	},
	initContainer: function() {
		TYPO3.Devlog.UserInterface.container = new TYPO3.Devlog.UserInterface.Layout();
	}
});

TYPO3.Devlog.Application.registerBootstrap(TYPO3.Devlog.UserInterface.Bootstrap);