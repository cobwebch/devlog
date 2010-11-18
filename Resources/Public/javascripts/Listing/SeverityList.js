Ext.ns("TYPO3.Devlog.Listing");

/**
 * Button of the rootline menu
 * @class TYPO3.Devlog.Listing.SeverityList
 * @extends Ext.SeverityList
 */
TYPO3.Devlog.Listing.SeverityList = Ext.extend(Ext.form.ComboBox, {
	
	/**
	 * Event triggered after initialization of the main area.
	 *
	 * @event TYPO3.Devlog.Listing.SeverityList.afterInit
	 *
	 */
	initComponent: function() {
		var config = {
			store: TYPO3.Devlog.Store.initSeverityListStore(),
			typeAhead: false,
			forceSelection: true,
			triggerAction: 'all',
			editable: false,
			selectOnFocus: true,
			listClass: 'x-combo-list-small',
			plugins: new TYPO3.Devlog.UserInterface.IconCombo(),
			mode: 'local',
			valueField: 'key',
			displayField: 'value',
			iconClsField: 'className'
		};
		
		Ext.apply(this, config);
		TYPO3.Devlog.Listing.SeverityList.superclass.initComponent.call(this);
		TYPO3.Devlog.Application.fireEvent('TYPO3.Devlog.Listing.afterInit', this);

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
		this.setValue('');
	},

	/**
	 * Defines default value
	 *
	 * @access public
	 * @method onafterrender
	 * @return void
	 */
	onselect: function() {
		TYPO3.Devlog.Store.LogStore.baseParams.severity = this.value
		if (this.value == '') {
			delete TYPO3.Devlog.Store.LogStore.baseParams.severity;
		}
		TYPO3.Devlog.Store.LogStore.load();
	}

});

Ext.reg('TYPO3.Devlog.Listing.SeverityList', TYPO3.Devlog.Listing.SeverityList);