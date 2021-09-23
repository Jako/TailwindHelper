var tailwindhelper = function (config) {
    config = config || {};
    Ext.applyIf(config, {});
    tailwindhelper.superclass.constructor.call(this, config);
    return this;
};
Ext.extend(tailwindhelper, Ext.Component, {
    initComponent: function () {
    }, page: {}, window: {}, grid: {}, tree: {}, panel: {}, combo: {}, config: {}, util: {}, form: {}
});
Ext.reg('tailwindhelper', tailwindhelper);

TailwindHelper = new tailwindhelper();
