Ext.ns('TYPO3.Devlog.UserInterface');

TYPO3.Devlog.UserInterface.AjaxRowExpander = Ext.extend(TYPO3.Devlog.UserInterface.RowExpander, {

	constructor: function(config){
		TYPO3.Devlog.UserInterface.AjaxRowExpander.superclass.constructor.call(this, config);
	},

    getBodyContent : function(record, index){
		var body = record.data.data_var;
		
		// result is stored, so that next time it is not necessary to query the database per Ajax
		if (record.data.has_data_var && record.data.data_var == '') {
			body = '<div class="loading" id="tmp' + record.id + '">&nbsp;</div>';
			Ext.Ajax.request({
			   url: '/typo3/ajax.php',
			   params: {
				   ajaxID: 'LogController::getDataVar',
				   uid: record.id
			   },
			   disableCaching: true,
			   success: function(response, options) {
				   Ext.get("tmp" + options.objId).removeClass('loading');
				   Ext.getDom("tmp" + options.objId).innerHTML = response.responseText;
				   record.data.data_var = response.responseText;
			   },
			   failure: function(error) {
				   //alert(error)
			   },
			   objId: record.id
			});

			return body;

		}
		return body;
    },
    beforeExpand : function(record, body, rowIndex){
        if(this.fireEvent('beforeexpand', this, record, body, rowIndex) !== false){
            body.innerHTML = this.getBodyContent(record, rowIndex);
            return true;
        } else{
            return false;
        }
    }
});


Ext.preg('ajaxrowexpander', TYPO3.Devlog.UserInterface.RowExpander);
