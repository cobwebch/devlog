Ext.ns('TYPO3.Devlog');

TYPO3.Devlog.Utils = {};

/**
 * Clone Function
 *
 * @param {Object/Array} o Object or array to clone
 * @return {Object/Array} Deep clone of an object or an array
 * @author Ing. Jozef Sakáloš
 */
TYPO3.Devlog.Utils.clone = function(o) {
	if (!o || 'object' !== typeof o) {
		return o;
	}
	if ('function' === typeof o.clone) {
		return o.clone();
	}
	var c = '[object Array]' === Object.prototype.toString.call(o) ? [] : {};
	var p, v;
	for (p in o) {
		if (o.hasOwnProperty(p)) {
			v = o[p];
			if (v && 'object' === typeof v) {
				c[p] = TYPO3.Devlog.Utils.clone(v);
			} else {
				c[p] = v;
			}
		}
	}
	return c;
};

/**
 * Generates a sprite icon according to TYPO3 convention
 *
 * @param {String} spriteName
 * @return {String}
 */
TYPO3.Devlog.Utils.getSpriteIcon = function(spriteName) {
	var elements = spriteName.split('-');
	var category = elements[0];
	var baseClass = category + '-' + elements[1];
	var className = spriteName.replace(category + '-', '');
	return '<span class="t3-icon t3-icon-' + category + ' t3-icon-' + baseClass + ' t3-icon-' + className + '"></span>';
}


/**
 * Simulate a mouse event on the GUI
 *
 * @param {String} eventName
 * @param {Object} element
 * @return void
 */
TYPO3.Devlog.Utils.fireEvent = function(eventName, element) {
	if( document.createEvent ) {
		var evObj = document.createEvent('MouseEvents');
		evObj.initEvent( eventName, true, false );
		element.dispatchEvent(evObj);
	} else if( document.createEventObject ) {
		element.fireEvent(eventName);
	}
}