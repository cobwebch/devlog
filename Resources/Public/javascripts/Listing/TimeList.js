Ext.ns("TYPO3.Devlog.Listing");

/**
 * Button of the rootline menu
 * @class TYPO3.Devlog.Listing.TimeList
 * @extends Ext.TimeList
 */
TYPO3.Devlog.Listing.TimeList = Ext.extend(Ext.form.ComboBox, {
	
	/**
	 * Event triggered after initialization of the main area.
	 *
	 * @event TYPO3.Devlog.Listing.TimeList.afterInit
	 *
	 */
	initComponent: function() {
		var config = {
			store: TYPO3.Devlog.Store.initTimeListStore(),
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
		TYPO3.Devlog.Listing.TimeList.superclass.initComponent.call(this);
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

		this.on(
			'onclickoncrdatecell',
			this.onclickoncrdatecell,
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
		TYPO3.Devlog.Store.LogStore.baseParams.limit = value;
		TYPO3.Devlog.UserInterface.container.logGrid.pagebrowser.pageSize = value
		TYPO3.Devlog.Store.LogStore.load();
	},

	/**
	 * Defines default value
	 *
	 * @access public
	 * @method onafterrender
	 * @return void
	 */
	onclickoncrdatecell: function() {
		this.setValue(-1);
	}

});

Ext.reg('TYPO3.Devlog.Listing.TimeList', TYPO3.Devlog.Listing.TimeList);