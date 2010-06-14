Ext.ns("TYPO3.Devlog.UserInterface");

TYPO3.Devlog.UserInterface.Layout = Ext.extend(Ext.Container, {

	initComponent: function() {
		var config = {
			renderTo: 't3-log-grid',
			items: [
			{
				xtype: 'TYPO3.Devlog.UserInterface.LogGridPanel',
				ref: 'logPanel',
				flex: 0
			}
			]
		};
		Ext.apply(this, config);
		TYPO3.Devlog.UserInterface.Layout.superclass.initComponent.call(this);
	}
});