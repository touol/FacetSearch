FacetSearch.window.CreateItem = function (config) {
    config = config || {}
    config.url = FacetSearch.config.connector_url

    Ext.applyIf(config, {
        title: _('facetsearch_item_create'),
        width: 600,
        cls: 'facetsearch_windows',
        baseParams: {
            action: 'mgr/item/create',
            resource_id: config.resource_id
        }
    })
    FacetSearch.window.CreateItem.superclass.constructor.call(this, config)

    this.on('success', function (data) {
        if (data.a.result.object) {
            // Авто запуск при создании новой подписик
            if (data.a.result.object.mode) {
                if (data.a.result.object.mode === 'new') {
                    var grid = Ext.getCmp('facetsearch-grid-items')
                    grid.updateItem(grid, '', {data: data.a.result.object})
                }
            }
        }
    }, this)
}
Ext.extend(FacetSearch.window.CreateItem, FacetSearch.window.Default, {

    getFields: function (config) {
        return [
            {xtype: 'hidden', name: 'id', id: config.id + '-id'},
            {
                xtype: 'textfield',
                fieldLabel: _('facetsearch_item_name'),
                name: 'name',
                id: config.id + '-name',
                anchor: '99%',
                allowBlank: false,
            }, {
                xtype: 'textarea',
                fieldLabel: _('facetsearch_item_description'),
                name: 'description',
                id: config.id + '-description',
                height: 150,
                anchor: '99%'
            },  {
                xtype: 'facetsearch-combo-filter-resource',
                fieldLabel: _('facetsearch_item_resource_id'),
                name: 'resource_id',
                id: config.id + '-resource_id',
                height: 150,
                anchor: '99%'
            }, {
                xtype: 'xcheckbox',
                boxLabel: _('facetsearch_item_active'),
                name: 'active',
                id: config.id + '-active',
                checked: true,
            }
        ]


    }
})
Ext.reg('facetsearch-item-window-create', FacetSearch.window.CreateItem)

FacetSearch.window.UpdateItem = function (config) {
    config = config || {}

    Ext.applyIf(config, {
        title: _('facetsearch_item_update'),
        baseParams: {
            action: 'mgr/item/update',
            resource_id: config.resource_id
        },
    })
    FacetSearch.window.UpdateItem.superclass.constructor.call(this, config)
}
Ext.extend(FacetSearch.window.UpdateItem, FacetSearch.window.CreateItem)
Ext.reg('facetsearch-item-window-update', FacetSearch.window.UpdateItem)