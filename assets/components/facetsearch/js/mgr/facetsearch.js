var FacetSearch = function (config) {
    config = config || {};
    FacetSearch.superclass.constructor.call(this, config);
};
Ext.extend(FacetSearch, Ext.Component, {
    page: {}, window: {}, grid: {}, tree: {}, panel: {}, combo: {}, config: {}, view: {}, utils: {}, buttons: {}
});
Ext.reg('facetsearch', FacetSearch);

FacetSearch = new FacetSearch();