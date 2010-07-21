Ext.ns("TYPO3.Devlog.Listing");

/**
 * Button of the rootline menu
 * @class TYPO3.Devlog.Listing.PageList
 * @extends Ext.PageList
 */
TYPO3.Devlog.Listing.PageList = Ext.extend(Ext.form.ComboBox, {
	
	/**
	 * Event triggered after initialization of the main area.
	 *
	 * @event TYPO3.Devlog.Listing.PageList.afterInit
	 *
	 */
	initComponent: function() {
		var config = {
			store: TYPO3.Devlog.Store.initPageListStore(),
			typeAhead: false,
			forceSelection: true,
			triggerAction: 'all',
			editable: false,
			selectOnFocus: true,
			listClass: 'x-combo-list-small',
			plugins: new TYPO3.Devlog.UserInterface.IconCombo(),
			width: 'auto',
			mode: 'local',
			valueField: 'key',
			displayField: 'value',
			iconClsField: 'className'
		};
		
		Ext.apply(this, config);
		TYPO3.Devlog.Listing.PageList.superclass.initComponent.call(this);
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
		var value = this.value - 0; // makes sure it is a number
		TYPO3.Devlog.Store.LogStore.baseParams.pid = value
		TYPO3.Devlog.Store.LogStore.load();
	}

});

Ext.reg('TYPO3.Devlog.Listing.PageList', TYPO3.Devlog.Listing.PageList);