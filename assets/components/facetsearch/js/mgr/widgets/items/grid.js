FacetSearch.grid.Items = function (config) {
    config = config || {};
    if (!config.id) {
        config.id = 'facetsearch-grid-items';
    }

    Ext.applyIf(config, {
        baseParams: {
            action: 'mgr/item/getlist',
            sort: 'id',
            dir: 'DESC'
        },
        stateful: true,
        stateId: config.id,
        viewConfig: {
            forceFit: true,
            enableRowBody: true,
            autoFill: true,
            showPreview: true,
            scrollOffset: 0,
            getRowClass: function (rec) {
                return !rec.data.active
                  ? 'facetsearch-grid-row-disabled'
                  : '';
            }
        },
        paging: true,
        remoteSort: true,
        autoHeight: true,
    });
    FacetSearch.grid.Items.superclass.constructor.call(this, config);
};
Ext.extend(FacetSearch.grid.Items, FacetSearch.grid.Default, {

    getFields: function () {
        return [
            'id', 'name', 'description', 'active', 'actions'
        ];
    },

    getColumns: function () {
        return [
            {header: _('facetsearch_item_id'), dataIndex: 'id', width: 20, sortable: true},
            {header: _('facetsearch_item_name'), dataIndex: 'name', sortable: true, width: 200},
            {header: _('facetsearch_item_description'), dataIndex: 'description', sortable: false, width: 250},
            {header: _('facetsearch_item_createdon'), dataIndex: 'createdon', width: 75, renderer: FacetSearch.utils.formatDate},
            {header: _('facetsearch_item_updatedon'), dataIndex: 'updatedon', width: 75, renderer: FacetSearch.utils.formatDate},
            {header: _('facetsearch_item_active'), dataIndex: 'active', width: 75, renderer: FacetSearch.utils.renderBoolean},
            {
                header: _('facetsearch_grid_actions'),
                dataIndex: 'actions',
                id: 'actions',
                width: 50,
                renderer: FacetSearch.utils.renderActions
            }
        ];
    },

    getTopBar: function () {
        return [{
            text: '<i class="icon icon-plus"></i>&nbsp;' + _('facetsearch_item_create'),
            handler: this.createItem,
            scope: this
        },{
            xtype: 'facetsearch-combo-filter-active',
            name: 'active',
            width: 210,
            custm: true,
            clear: true,
            addall: true,
            value: '',
            listeners: {
                select: {
                    fn: this._filterByCombo,
                    scope: this
                },
                afterrender: {
                    fn: this._filterByCombo,
                    scope: this
                }
            }
        },{
            xtype: 'facetsearch-combo-filter-resource',
            name: 'resource',
            width: 210,
            custm: true,
            clear: true,
            addall: true,
            value: '',
            listeners: {
                select: {
                    fn: this._filterByCombo,
                    scope: this
                },
                afterrender: {
                    fn: this._filterByCombo,
                    scope: this
                }
            }
        },
            '->', this.getSearchField()];
    },

    getListeners: function () {
        return {
            rowDblClick: function (grid, rowIndex, e) {
                var row = grid.store.getAt(rowIndex);
                this.updateItem(grid, e, row);
            },
        };
    },

    createItem: function (btn, e) {
        var w = MODx.load({
            xtype: 'facetsearch-item-window-create',
            id: Ext.id(),
            listeners: {
                success: {
                    fn: function () {
                        this.refresh();
                    }, scope: this
                }
            }
        });
        w.reset();
        w.setValues({active: true});
        w.show(e.target);
    },

    updateItem: function (btn, e, row) {
        if (typeof(row) != 'undefined') {
            this.menu.record = row.data;
        }
        else if (!this.menu.record) {
            return false;
        }
        var id = this.menu.record.id;

        MODx.Ajax.request({
            url: this.config.url,
            params: {
                action: 'mgr/item/get',
                id: id
            },
            listeners: {
                success: {
                    fn: function (r) {
                        var w = MODx.load({
                            xtype: 'facetsearch-item-window-update',
                            id: Ext.id(),
                            record: r,
                            listeners: {
                                success: {
                                    fn: function () {
                                        this.refresh();
                                    }, scope: this
                                }
                            }
                        });
                        w.reset();
                        w.setValues(r.object);
                        w.show(e.target);
                    }, scope: this
                }
            }
        });
    },

    removeItem: function () {
        this.action('remove')
    },
    disableItem: function () {
        this.action('disable')
    },
    enableItem: function () {
        this.action('enable')
    },
});
Ext.reg('facetsearch-grid-items', FacetSearch.grid.Items);