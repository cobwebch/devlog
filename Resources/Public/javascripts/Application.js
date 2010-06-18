Ext.ns("TYPO3.Devlog");

/**
 * @class TYPO3.Devlog.Application
 * @namespace TYPO3.Devlog
 * @extends Ext.util.Observable
 *
 * The main entry point which controls the lifecycle of the application.
 *
 * This is the main event handler of the application.
 *
 * First, it calls all registered bootstrappers, thus other modules can register event listeners.
 * Afterwards, the bootstrap procedure is started. During bootstrap, it will initialize:
 * <ul><li>QuickTips</li>
 * <li>History Manager</li></ul>
 *
 * @singleton
 */
TYPO3.Devlog.Application = Ext.apply(new Ext.util.Observable, {
	/**
	 * @event TYPO3.Devlog.Application.afterBootstrap
	 * After bootstrap event. Should be used for main initialization.
	 */

	bootstrappers: [],

	/**
	 * Main bootstrap. This is called by Ext.onReady and calls all registered bootstraps.
	 *
	 * This method is called automatically.
	 */
	bootstrap: function() {
		this._configureExtJs();
//		this._initializeExtDirect();
		this._registerEventDebugging();
		this._invokeBootstrappers();

		Ext.QuickTips.init();

		this.fireEvent('TYPO3.Devlog.Application.afterBootstrap');
		
		this._initializeHistoryManager();
	},

	/**
	 * Registers a new bootstrap class.
	 *
	 * Every bootstrap class needs to extend TYPO3.Devlog.Application.AbstractBootstrap.
	 * @param {TYPO3.Devlog.Application.AbstractBootstrap} bootstrap The bootstrap class to be registered.
	 * @api
	 */
	registerBootstrap: function(bootstrap) {
		this.bootstrappers.push(bootstrap);
	},


	// pirvate
	/**
	 * Initialize Ext.Direct Provider
	 */
//	_initializeExtDirect: function() {
//		Ext.app.ExtDirectAPI.enableBuffer = 100;
//		Ext.Direct.addProvider(Ext.app.ExtDirectAPI);
//	},

	// private
	/**
	 * Sets the blank image URL
	 */
	_configureExtJs: function() {
//		Ext.BLANK_IMAGE_URL = 'ext/resources/images/default/s.gif';
	},

	/**
	 * Invoke the registered bootstrappers.
	 */
	_invokeBootstrappers: function() {
		Ext.each(this.bootstrappers, function(bootstrapper) {
			bootstrapper.initialize();
		});
	},

	_initializeHistoryManager: function() {
		Ext.History.on('change', function(token) {
			this.fireEvent('TYPO3.Devlog.Application.navigate', token);
		}, this);
		// Handle initial token (on page load)
		Ext.History.init(function(history) {
			history.fireEvent('change', history.getToken());
		}, this);
	},

	_registerEventDebugging: function() {
		Ext.util.Observable.capture(
			this,
			function(e) {
				if (window.console && window.console.log) {
					console.log(e, arguments);
				}
			}
		);
	}

});

Ext.onReady(TYPO3.Devlog.Application.bootstrap, TYPO3.Devlog.Application);