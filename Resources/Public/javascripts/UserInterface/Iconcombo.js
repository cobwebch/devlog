Ext.namespace('TYPO3.Devlog.UserInterface');
 
/**
 * TYPO3.Devlog.UserInterface.IconCombo plugin for Ext.form.Combobox
 *
 * @author  Ing. Jozef Sakalos
 * @date    January 7, 2008
 *
 * @class TYPO3.Devlog.UserInterface.IconCombo
 * @extends Ext.util.Observable
 */
TYPO3.Devlog.UserInterface.IconCombo = function(config) {
    Ext.apply(this, config);
};
 
// plugin code
Ext.extend(TYPO3.Devlog.UserInterface.IconCombo, Ext.util.Observable, {
    init:function(combo) {
        Ext.apply(combo, {
            tpl:  '<tpl for=".">'
                + '<div class="x-combo-list-item ux-icon-combo-item '
                + '{' + combo.iconClsField + '}">'
                + '{' + combo.displayField + '}'
                + '</div></tpl>',
 
            onRender: combo.onRender.createSequence(function(ct, position) {
                // adjust styles
                this.wrap.applyStyles({position:'relative'});
                this.el.addClass('ux-icon-combo-input');
 
                // add div for icon
                this.icon = Ext.DomHelper.append(this.el.up('div.x-form-field-wrap'), {
                    tag: 'div', style:'position:absolute'
                });
            }), // end of function onRender
 
            setIconCls: function() {
                var rec = this.store.query(this.valueField, this.getValue()).itemAt(0);
                if(rec) {
                    this.icon.className = 'ux-icon-combo-icon ' + rec.get(this.iconClsField);
                }
            }, // end of function setIconCls
 
            setValue: combo.setValue.createSequence(function(value) {
                this.setIconCls();
            })
        });
    } // end of function init
}); // end of extend
