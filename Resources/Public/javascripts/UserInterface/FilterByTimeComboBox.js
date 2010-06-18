Ext.ns("TYPO3.Devlog.UserInterface");

/**
 * Button of the rootline menu
 * @class TYPO3.Devlog.UserInterface.FilterByTime
 * @extends Ext.FilterByTime
 */
TYPO3.Devlog.UserInterface.FilterByTime = Ext.extend(Ext.form.ComboBox, {
	
	/**
	 * Event triggered after initialization of the main area.
	 *
	 * @event TYPO3.Devlog.UserInterface.FilterByTime.afterInit
	 *
	 */
	initComponent: function() {
		var config = {
			store: TYPO3.Devlog.FilterByTimeStore,
			typeAhead: false,
			forceSelection: true,
			triggerAction: 'all',
			editable: false,
			selectOnFocus: true,
			listClass: 'x-combo-list-small',
			mode: 'local',
			valueField: 'key',
			displayField: 'value'
		};
		
		Ext.apply(this, config);
		TYPO3.Devlog.UserInterface.FilterByTime.superclass.initComponent.call(this);
		TYPO3.Devlog.Application.fireEvent('TYPO3.Devlog.UserInterface.afterInit', this);

		this.on(
			'afterrender',
			this.onafterrender,
			this
		);

		this.on(
			'select',
			this.onselect,
			this
		);
	},

	/**
	 * Defines default value
	 *
	 * @access public
	 * @method onafterrender
	 * @return void
	 */
	onafterrender: function() {
		this.setValue(25);
	},

	/**
	 * Defines default value
	 *
	 * @access public
	 * @method onafterrender
	 * @return void
	 */
	onselect: function() {
		var value = this.value - 0; // makes sure it is a number
		TYPO3.Devlog.LogStore.baseParams.limit = value;
		TYPO3.Devlog.UserInterface.container.gridPanel.pagebrowser.pageSize = value
		TYPO3.Devlog.LogStore.load();
	}

});

Ext.reg('TYPO3.Devlog.UserInterface.FilterByTime', TYPO3.Devlog.UserInterface.FilterByTime);