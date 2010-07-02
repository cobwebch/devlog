Ext.ns("TYPO3.Devlog.Listing");

/**
 * @class TYPO3.Devlog.Listing.Bootstrap
 * @namespace TYPO3.Devlog.Listing
 * @extends TYPO3.Newsletter.Application.AbstractBootstrap
 *
 * Bootrap module statistics
 *
 * $Id$
 */
TYPO3.Devlog.Listing.Bootstrap = Ext.apply(new TYPO3.Devlog.Application.AbstractBootstrap, {
	initialize: function() {

	}
});

TYPO3.Devlog.Application.registerBootstrap(TYPO3.Devlog.Listing.Bootstrap);