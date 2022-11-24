FacetSearch.page.Home = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        components: [{
            xtype: 'facetsearch-panel-home',
            renderTo: 'facetsearch-panel-home-div'
        }]
    });
    FacetSearch.page.Home.superclass.constructor.call(this, config);
};
Ext.extend(FacetSearch.page.Home, MODx.Component);
Ext.reg('facetsearch-page-home', FacetSearch.page.Home);