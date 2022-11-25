(function (window, document, $, FacetSearchConfig) {
    var FacetSearch = FacetSearch || {};
    
    FacetSearchConfig.callbacksObjectTemplate = function () {
        return {
            // return false to prevent send data
            before: [],
            response: {
                success: [],
                error: []
            },
            ajax: {
                done: [],
                fail: [],
                always: []
            }
        }
    };
    FacetSearch.setup = function () {
        // selectors & $objects
        this.actionName = 'facetsearch_action';
        this.action = ':submit[name=' + this.actionName + ']';
        this.form = '.FacetSearch_form';
        this.$doc = $(document);
        
        this.sendData = {
            $form: null,
            action: null,
            formData: null
        };
        
        this.timeout = 300;
    };
    FacetSearch.initialize = function () {
        FacetSearch.setup();
                        
        FacetSearch.Filter.initialize();
    };
    FacetSearch.controller = function () {
        var self = this;
        //console.info(self.sendData.action);
        switch (self.sendData.action) {
            // case 'report/update':
            //     FacetSearch.Report.update();
            //     break;
            // case 'report/update_file':
            //     FacetSearch.Report.update_file();
            //     break;
            default:
                return;
        }
    };
    FacetSearch.send = function (data, callbacks) {
        var runCallback = function (callback, bind) {
            if (typeof callback == 'function') {
                return callback.apply(bind, Array.prototype.slice.call(arguments, 2));
            }
            else if (typeof callback == 'object') {
                for (var i in callback) {
                    if (callback.hasOwnProperty(i)) {
                        var response = callback[i].apply(bind, Array.prototype.slice.call(arguments, 2));
                        if (response === false) {
                            return false;
                        }
                    }
                }
            }
            return true;
        };

        var url = (FacetSearchConfig.actionUrl)
                      ? FacetSearchConfig.actionUrl
                      : document.location.href;
        // send
        var xhr = function (callbacks) {
            return $.post(url, data, function (response) {
                if (response.success) {
                    // if (response.message) {
                    //     getTables.Message.success(response.message);
                    // }
                    //console.info(callbacks.response.success);
                    runCallback(callbacks.response.success, FacetSearch, response);
                }
                else {
                    //getTables.Message.error(response.message);
                    runCallback(callbacks.response.error, FacetSearch, response);
                }
            }, 'json')
        }(callbacks);
    };
    

    FacetSearch.Filter = {
        callbacks: {
            load: FacetSearchConfig.callbacksObjectTemplate(),
        },
        reached: false,
        options: {
            wrapper: '.facetsearch-outer',
            filters: '.facetsearch-filter',
            pagination: '.facetsearch_pagination',
            pagination_link: '.facetsearch_pagination a',
    
            sort: '.facetsearch_sort',
            sort_link: '.facetsearch_sort a',
    
            selected: '.facetsearch_selected',
            selected_tpl: '<a href="#" data-field="_field_" data-value="_value_" class="facetsearch_selected_link"><em>_title_</em><sup>x</sup></a>',
            selected_wrapper_tpl: '<strong>_title_:</strong>',
            selected_filters_delimeter: '; ',
            selected_values_delimeter: ' ',
            
            filter_wrapper: '.facetsearch-filter',
            filter_title: '.filter-title',
            
            more: '.btn_more',
            more_tpl: '<button class="btn btn-default btn_more">' + FacetSearchConfig['moreText'] + '</button>',
            more_wraper: '.facetsearch_btm_more',

            active_class: 'active',
    
        },
        selections: {},
        elements: ['filters', 'results', 'pagination', 'sort', 'selected', 'limit'],
        setup: function () {
            var i;
            if (FacetSearchConfig['filterOptions'] != undefined && Object.keys(FacetSearchConfig['filterOptions']).length > 0) {
                for (i in FacetSearchConfig['filterOptions']) {
                    if (FacetSearchConfig['filterOptions'].hasOwnProperty(i)) {
                        FacetSearch.Filter.options[i] = FacetSearchConfig['filterOptions'][i];
                    }
                }
            }
            for (i in this.elements) {
                if (this.elements.hasOwnProperty(i)) {
                    var elem = this.elements[i];
                    this[elem] = $('body').find(this.options[elem]);
                }
            }
        },
        initialize: function () {
            FacetSearch.Filter.setup();
            FacetSearch.Slider.initialize();
            FacetSearch.$doc
            .on('change', '.facetsearch-checkbox-value,.facetsearch-number-value,.facetsearch-filter-select select', function (e) {
                e.preventDefault();
                FacetSearch.Filter.submit();
                
            });
            

            this.handlePagination();
            this.handleSort();

            window.setTimeout(function() {
                $(window).on('popstate', function (e) {
                    if (e.originalEvent.state && e.originalEvent.state['FacetSearch']) {
                        var params = {};
                        var tmp = e.originalEvent.state['FacetSearch'].split('?');
                        if (tmp[1]) {
                            tmp = tmp[1].split('&');
                            for (var i in tmp) {
                                if (tmp.hasOwnProperty(i)) {
                                    var tmp2 = tmp[i].split('=');
                                    params[tmp2[0]] = tmp2[1];
                                }
                            }
                        }
                        FacetSearch.Filter.setFilters(params);
                        FacetSearch.Filter.load(params);
                    }
                });
            }, 1000);

            if (this.selected) {
                this.selections[this.selected.text().replace(/\s+$/, '').replace(/:$/, '')] = [];
                var selectors = [
                    this.options.wrapper + ' input[type="checkbox"]',
                    this.options.wrapper + ' input[type="radio"]',
                    this.options.wrapper + ' select'
                ];
                $(document).on('change', selectors.join(', '), function () {
                    FacetSearch.Filter.handleSelected($(this));
                });
    
                selectors = [
                    'input[type="checkbox"]:checked',
                    'input[type="radio"]:checked',
                    'select'
                ];
                $(this.options.wrapper).find(selectors.join(', ')).each(function () {
                    FacetSearch.Filter.handleSelected($(this));
                });
    
                $(document).on('click', this.options.selected + ' a', function (e) {
                    e.preventDefault();
                    var field = $(this).data('field');
                    var value = $(this).data('value');
                    var elem = $('.facetsearch-' + field + ' [value="' + value + '"]');
                    if(elem.length == 0) elem = $('.facetsearch-' + field + ' select');
                    if (elem[0]) {
                        switch (elem[0].tagName) {
                            case 'INPUT':
                                elem.prop('checked', false).trigger('change');
                                break;
                            case 'SELECT':
                                elem.val(elem.find('option:first').prop('value')).trigger('change');
                                break;
                        }
                    }
                    return false;
                });
            }
        },
        handleSelected: function (input) {
            if (!input[0]) {
                return;
            }
            var field = input.attr('name');
            var value = input.attr('value');

            var title = '';
            var elem;
    
            var filter = input.parents(this.options['filter_wrapper']);
            var filter_title = '';
            var tmp;
            if (filter.length) {
                tmp = filter.find(this.options['filter_title']);
                if (tmp.length > 0) {
                    filter_title = tmp.text();
                }
            }
            // if (filter_title == '') {
            //     //noinspection LoopStatementThatDoesntLoopJS
            //     for (filter_title in this.selections) {
            //         break;
            //     }
            // }
    
            switch (input[0].tagName) {
                case 'INPUT':
                    var label = input.parent('label');
                    var sup = label.find('sup').text();
                    var text = label.text().trim();
                    if (sup) {
                        title = text.replace(new RegExp(sup.replace('+', '\\+') + '$'), '');
                    } else {
                        title = text;
                    }
                    $('[data-field="' + field + '"]'+'[data-value="' + value + '"]', this.selected).remove();
                    if (input.is(':checked')) {
                        elem = this.options['selected_tpl']
                            .replace('[[+field]]', field).replace('[[+title]]', title)
                            .replace('[[+value]]', value).replace('_value_', value)
                            .replace('_field_', field).replace('_title_', title);
                    }
                    break;
    
                case 'SELECT':
                    var option = input.find('option:selected');
                    $('[data-field="' + field + '"]', this.selected).remove();
                    if (input.val() != '') {
                        title = ' ' + option.text().replace(/(\(.*\)$)/, '');
                        elem = this.options['selected_tpl']
                            .replace('[[+field]]', field).replace('[[+title]]', title)
                            .replace('_field_', field).replace('_title_', title);
                    }
                    break;
            }
    
            if (elem != undefined) {
                if (this.selections[filter_title] == undefined || input[0].type == 'radio') {
                    this.selections[filter_title] = {};
                }
                this.selections[filter_title][field + value] = elem;
            }
            else if (this.selections[filter_title] != undefined && this.selections[filter_title][field + value] != undefined) {
                delete this.selections[filter_title][field + value];
            }
    
            this.selected.html('');
            var count = 0;
            var selected = [];
            for (var i in this.selections) {
                if (!this.selections.hasOwnProperty(i) || !Object.keys(this.selections).length) {
                    continue;
                }
                if (Object.keys(this.selections[i]).length) {
                    tmp = [];
                    for (var i2 in this.selections[i]) {
                        if (!this.selections[i].hasOwnProperty(i2)) {
                            continue;
                        }
                        tmp.push(this.selections[i][i2]);
                        count++;
                    }
                    title = this.options['selected_wrapper_tpl']
                        .replace('[[+title]]', i)
                        .replace('_title_', i);
                    selected.push(title + tmp.join(this.options['selected_values_delimeter']));
                }
            }
    
            if (count) {
                this.selected.append(selected.join(this.options['selected_filters_delimeter'])).show();
            }
            else {
                this.selected.hide();
            }
        },
        submit: function (params) {
            if (!params || !Object.keys(params).length) {
                params = this.getFilters();
            }
            FacetSearchConfig['page'] = '';
            FacetSearch.Hash.set(params);
            this.load(params);
        },
        load: function (params, callback, append, no_aggs) {
            if (!params || !Object.keys(params).length) {
                params = this.getFilters();
            }
            params.pageId = FacetSearchConfig['pageId'];
            if(no_aggs) params.no_aggs = 1;
             
            let action = 'filter_ajax';
            $FacetSearchOuter = $(FacetSearch.Filter.options['wrapper']);
            formData ={
                action:action,
                hash:$FacetSearchOuter.data('hash')
            };
            
            $.each(params, function( key, value ) {
                formData[key] = value;
            });

            FacetSearch.sendData = {
                action: action,
                formData: formData
            };
            
            var callbacks = FacetSearch.Filter.callbacks;
            callbacks.load.response.success = function (response) {
                
                if(response.data.total !== ''){
                    $(FacetSearch.Filter.options['wrapper']).find('.facetsearch-total').html(response.data.total);
                }
                if(response.data.results){
                    //$(FacetSearch.Filter.options['wrapper']).find('.facetsearch-results').html(response.data.results);
                    if (append) {
                        $(FacetSearch.Filter.options['wrapper']).find('.facetsearch-results').append(response['data']['results']);
                    }
                    else {
                        $(FacetSearch.Filter.options['wrapper']).find('.facetsearch-results').html(response['data']['results']);
                    }
                    
                }
                if(response.data.pagination){
                    $(FacetSearch.Filter.options['wrapper']).find('.facetsearch_pagination').html(response.data.pagination);
                }
                if(response.data.aggs){
                    FacetSearch.Filter.setAggs($(FacetSearch.Filter.options['wrapper']),response.data.aggs);
                }

                if(response.data.log){
                    $(FacetSearch.Filter.options['wrapper']).find('.FacetSearchLog').html(response.data.log);
                }
                
                if (FacetSearchConfig['mode'] == 'button') {
                    if (response['data']['pages'] == response['data']['page']) {
                        $(FacetSearch.Filter.options['more']).hide();
                    }
                    else {
                        $(FacetSearch.Filter.options['more']).show();
                    }
                }
                else if (FacetSearchConfig['mode'] == 'scroll') {
                    FacetSearch.Filter.reached = response['data']['pages'] == response['data']['page'];
                }

                if (callback && $.isFunction(callback)) {
                    callback.call(this, response, params);
                }
                $(document).trigger('mse2_load', response);
            };
            
            FacetSearch.send(FacetSearch.sendData.formData, FacetSearch.Filter.callbacks.load);
            
        },
        addPage: function () {
            var pcre = new RegExp(FacetSearchConfig['pageVar'] + '[=|\/|-](\\d+)');
            var current = FacetSearchConfig['page'] || 1;
            $(this.options['pagination_link']).each(function () {
                var href = $(this).prop('href');
                var match = href.match(pcre);
                var page = !match ? 1 : Number(match[1]);
                if (page > current) {
                    FacetSearchConfig['page'] = (page != FacetSearchConfig['start_page']) ? page : '';
                    var tmp = FacetSearch.Filter.getFilters();
                    delete(tmp['page']);
                    FacetSearch.Hash.set(tmp);
    
                    var params = FacetSearch.Filter.getFilters();
                    FacetSearch.Filter.load(params, null, true, true);
                    return false;
                }
            });
        },
        handleSort: function () {
            var params = FacetSearch.Hash.get();
            if (params.sort) {
                var sorts = params.sort.split(',');
                for (var i = 0; i < sorts.length; i++) {
                    var tmp = sorts[i].split(':');
                    if (tmp[0] && tmp[1]) {
                        $(this.options.sort_link + '[data-sort="' + tmp[0] + '"]').data('dir', tmp[1]).attr('data-dir', tmp[1]).addClass(this.options.active_class);
                    }
                }
            }
            
            $(document).on('click', this.options.sort_link, function () {
                
                if ($(this).hasClass(FacetSearch.Filter.options.active_class) && $(this).data('dir') == '') {
                    return false;
                }
                $(FacetSearch.Filter.options.sort_link).removeClass(FacetSearch.Filter.options.active_class);
                $(this).addClass(FacetSearch.Filter.options.active_class);
                var dir;
                if ($(this).data('dir') == '') {
                    dir = $(this).data('default');
                }
                else {
                    dir = $(this).data('dir') == 'desc'
                        ? 'asc'
                        : 'desc';
                }
                $(FacetSearch.Filter.options.sort_link).data('dir', '').attr('data-dir', '');
                $(this).data('dir', dir).attr('data-dir', dir);
    
                var sort = $(this).data('sort');
                if (dir) {
                    sort = sort.replace(
                        new RegExp(':' + '.*?' + ','),
                        ':' + dir + ','
                    );
                    sort += ':' + dir;
                }
                FacetSearchConfig['sort'] = (sort != FacetSearchConfig['start_sort']) ? sort : '';
                var params = FacetSearch.Filter.getFilters();
                if (FacetSearchConfig['page'] > 1 && (FacetSearchConfig['mode'] == 'scroll' || FacetSearchConfig['mode'] == 'button')) {
                    FacetSearchConfig['page'] = '';
                    delete(params['page']);
                }
                FacetSearch.Hash.set(params);
                FacetSearch.Filter.load(params, null, false, true);
    
                return false;
            });
        },
        handlePagination: function () {
            var pcre = new RegExp(FacetSearchConfig['pageVar'] + '[=|\/|-](\\d+)');
            switch (FacetSearchConfig['mode']) {
                case 'button':
                    this.pagination.hide();
                    // Add more button
                    $(this.options['more_wraper']).html(this.options['more_tpl']);
                    var more = $(this.options['more']);
    
                    var has_results = false;
                    $(this.options['pagination_link']).each(function () {
                        var href = $(this).prop('href');
                        var match = href.match(pcre);
                        var page = !match ? 1 : match[1];
                        if (page > FacetSearchConfig['page']) {
                            has_results = true;
                            return false;
                        }
                    });
                    if (!has_results) {
                        more.hide();
                    }
                    if (FacetSearchConfig['page'] > 1) {
                        FacetSearchConfig['page'] = '';
                        FacetSearch.Hash.remove('page');
                        FacetSearch.Filter.load();
                    }
    
                    $(document).on('click', this.options['wrapper'] + ' ' + this.options['more'], function (e) {
                        e.preventDefault();
                        FacetSearch.Filter.addPage();
                    });
                    break;
    
                case 'scroll':
                    this.pagination.hide();
                    var wrapper = $(this.options['wrapper']);
                    var $window = $(window);
                    $window.on('scroll', function () {
                        if (!FacetSearch.Filter.reached && $window.scrollTop() > wrapper.height() - $window.height()) {
                            FacetSearch.Filter.reached = true;
                            FacetSearch.Filter.addPage();
                        }
                    });
    
                    if (FacetSearchConfig['page'] > 1) {
                        FacetSearchConfig['page'] = '';
                        FacetSearch.Hash.remove('page');
                        FacetSearch.Filter.load();
                    }
                    break;
    
                default:
                    $(document).on('click', this.options.pagination_link, function () {
                        if (!$(this).hasClass(FacetSearch.Filter.options.active_class)) {
                            $(FacetSearch.Filter.options.pagination).removeClass(FacetSearch.Filter.options.active_class);
                            $(this).addClass(FacetSearch.Filter.options.active_class);
    
                            var tmp = $(this).prop('href').match(pcre);
                            var page = tmp && tmp[1] ? Number(tmp[1]) : 1;
                            FacetSearchConfig['page'] = (page != FacetSearchConfig['start_page']) ? page : '';
    
                            var params = FacetSearch.Filter.getFilters();
                            FacetSearch.Hash.set(params);
                            FacetSearch.Filter.load(params, function () {
                                $('html, body').animate({
                                    scrollTop: $(FacetSearch.Filter.options.wrapper).position().top || 0
                                }, 0);
                            });
                        }
    
                        return false;
                    });
            }
        },
        setFilters: function (params) {
            if (!params) {
                params = {};
            }
            for (var i in this.elements) {
                if (!this.elements.hasOwnProperty(i)) {
                    continue;
                }
                var elem = this.elements[i];
                
                if (typeof(this[elem]) == 'undefined') {
                    continue;
                }
                var item, name, values, val, type;
                switch (elem) {
                    // case 'limit':
                    //     if (params['limit'] == undefined) {
                    //         this.limit.val(FacetSearchConfig['start_limit']);
                    //         mse2Config['limit'] = '';
                    //     }
                    //     else {
                    //         this.limit.val(params['limit']);
                    //     }
                    //     break;
                    case 'pagination':
                        FacetSearchConfig['page'] = params['page'] == undefined
                            ? ''
                            : params['page'];
                        break;
                    case 'sort':
                        var sorts = {};
                        values = params['sort'];
                        if (values == undefined) {
                            values = FacetSearchConfig['start_sort'];
                            FacetSearchConfig['sort'] = '';
                        }
                        if (typeof(values) != 'object' && values != '') {
                            values = values.split(',');
                            for (i in values) {
                                if (!values.hasOwnProperty(i)) {
                                    continue;
                                }
                                name = values[i].split(':');
                                if (name[0] && name[1]) {
                                    sorts[name[0]] = name[1];
                                }
                            }
                        }
                        $(document).find(this.options['sort_link']).each(function () {
                            item = $(this);
                            name = item.data('sort');
                            if (sorts[name]) {
                                item.data('dir', sorts[name]).attr('data-dir', sorts[name]);
                                item.addClass(FacetSearch.Filter.options['active_class']);
                            }
                            else {
                                item.data('dir', '').attr('data-dir', '');
                                item.removeClass(FacetSearch.Filter.options['active_class']);
                            }
                        });
                        break;
                    case 'filters':
                        this['filters'].find('input').each(function () {
                            item = $(this);
                            name = item.prop('name');
                            type = item.prop('type');
                            values = params[name];
                            if (values != undefined && typeof(values) != 'object') {
                                values = values.split(',');
                            }
                            switch (type) {
                                case 'checkbox':
                                case 'radio':
                                    var checked = item.is(':checked');
                                    if (params[name] != undefined) {
                                        item.prop('checked', values.indexOf(String(item.val())) != -1);
                                    }
                                    else {
                                        item.prop('checked', false);
                                    }
                                    if (item.is(':checked') != checked) {
                                        FacetSearch.Filter.handleSelected(item);
                                    }
                                    break;
                                default:
                                    if (FacetSearch.Slider.sliders[name]) {
                                        if (item.data('idx')==0) {
                                            val = (values != undefined && values[0] != undefined)
                                                ? values[0]
                                                : FacetSearch.Slider.sliders[name]['values'][0];
                                        }
                                        else {
                                            val = (values != undefined && values[1] != undefined)
                                                ? values[1]
                                                : FacetSearch.Slider.sliders[name]['values'][1];
                                        }
                                        if (val != item.val()) {
                                            item.val(val).trigger('click');
                                        }
                                    } else {
                                        var original = item.data('original-value');
                                        if (original != undefined) {
                                            item.val(original);
                                        }
                                    }
                            }
                        });
                        this['filters'].find('select').each(function () {
                            item = $(this);
                            name = item.prop('name');
                            type = item.prop('type');
                            values = params[name];
                            if (values != undefined) {
                                if (typeof(values) != 'object') {
                                    values = values.split(',');
                                }
                                item.find('option').each(function () {
                                    var option = $(this);
                                    val = option.prop('value');
                                    var selected = option.is(':selected');
                                    $(this).prop('selected', values.indexOf(String(val)) != -1);
                                    if (option.is(':selected') != selected) {
                                        FacetSearch.Filter.handleSelected(item);
                                    }
                                });
                            }
                            else {
                                item.val('');
                            }
                        });
                        break;
                }
            }
        },
        setAggs: function ($FacetSearchOuter,aggs) {
            var count;
            for (var alias in aggs){
                var is_slider = typeof(FacetSearch.Slider.sliders[alias]) != 'undefined';
                if (is_slider && !FacetSearch.Slider.sliders[alias]['user_changed']) {
                    FacetSearch.Slider.setAggs($FacetSearchOuter,alias,aggs[alias]);
                }else{
                    $filter = $FacetSearchOuter.find('.facetsearch-'+alias);
                    if($filter.length != 1) continue;
                    var values = aggs[alias];
                    for(var value in values){
                        count = values[value];
                        var input = $filter.find('.facetsearch-checkbox-value[value="'+value+'"],option[value="'+value+'"]');
                        if (!input[0]) {
                            continue;
                        }
                        switch (input[0].tagName) {
                            case 'INPUT':
                                var label = input.parent();
                                var elem = label.find('sup');
                                
                                elem.text(count);
                                if (!count || count == 0) {
                                    if (input.is(':not(:checked)')) {
                                        input.prop('disabled', true);
                                        label.addClass('disabled');
                                        FacetSearch.Filter.handleSelected(input);
                                    }
                                }
                                else {
                                    input.prop('disabled', false);
                                    label.removeClass('disabled');
                                }
    
                                if (input.is(':checked')) {
                                    elem.hide();
                                }
                                else {
                                    elem.show();
                                }
                            break;
                            case 'OPTION':
                                var text = input.text();
                                var matches = text.match(/\([^\)]+\)$/);
                                var src = matches
                                    ? matches[0]
                                    : '';
                                var dst = '';

                                if (!count) {
                                    input.prop('disabled', true).addClass('disabled');
                                    /*
                                    if (input.is(':selected')) {
                                    input.prop('selected', false);
                                    FacetSearch.Filter.handleSelected(input);
                                    }
                                    */
                                }
                                else {
                                    dst = ' (' + count + ')';
                                    input.prop('disabled', false).removeClass('disabled');
                                }

                                if (input.is(':selected')) {
                                    dst = '';
                                }

                                if (src) {
                                    text = text.replace(src, dst);
                                }
                                else {
                                    text += dst;
                                }
                                input.text(text);

                                FacetSearch.Filter.handleSelected(input.parent());
                            break;
                        }
                    }
                }
                
            }
        },
        getFilters: function () {
            var params = {};
            if (FacetSearchConfig['page'] > 0) {
                params.page = FacetSearchConfig['page'];
            }
            $(FacetSearch.Filter.options['wrapper']).find('input.facetsearch-checkbox-value:checked').each(function(){
                if(params[$(this).attr('name')]){
                    params[$(this).attr('name')] += ","+$(this).val();
                }else{
                    params[$(this).attr('name')] = $(this).val();
                }
            });
            $(FacetSearch.Filter.options['wrapper']).find('.facetsearch-filter-select select').each(function(){
                val = $(this).find("option:selected").val();
                if(val){
                    params[$(this).attr('name')] = val;
                }
            });
            $(FacetSearch.Filter.options['wrapper']).find('input.facetsearch-number-value').each(function(){
                if (FacetSearch.Slider.sliders[$(this).attr('name')]['changed']){
                    if(params[$(this).attr('name')]){
                        params[$(this).attr('name')] += ","+$(this).val();
                    }else{
                        params[$(this).attr('name')] = $(this).val();
                    }
                }
            });
            if (FacetSearchConfig['sort'] != '') {
                params.sort = FacetSearchConfig['sort'];
            }
            return params;
        },
        
    };
    FacetSearch.Slider = {
        sliders: {},
        setAggs: function ($FacetSearchOuter,alias,arr) {
            var vmin = null;
            var vmax = null;
            for (value in arr) {
                if (!arr.hasOwnProperty(value)) {
                    continue;
                }
                count = arr[value];
                if (typeof(count) != 'number') {
                    continue;
                }
                if (count > 0 && (vmin === null || vmin > value)) {
                    vmin = Number(value);
                }
                if (count > 0 && vmax < value) {
                    vmax = Number(value);
                }
            }
            
            // if (vmin == null && vmax == null) {
            //     remove.push(alias);
            // }
            
            var imin = $('.facetsearch-number-value.'+ alias + '_0');

            if (imin.length) {
                if (vmin == null) {
                    vmin = Number(imin.data('original-value'));
                }
                imin.val(vmin.toFixed(imin.data('decimals'))).trigger('click');
            }
            var imax = $('.facetsearch-number-value.'+ alias + '_1');
            
            if (imax.length) {
                if (vmax == null) {
                    vmax = Number(imax.data('original-value'));
                }
                imax.val(vmax.toFixed(imax.data('decimals'))).trigger('click');
            }
            FacetSearch.Slider.sliders[alias]['values'] = [vmin, vmax];
            FacetSearch.Slider.sliders[alias]['changed'] = false;
        },
        initialize: function () {
            if (!$('.facetsearch-filter-slider').length) {
                return false;
            }else if (!$.ui || !$.ui.slider) {
                return FacetSearch.Slider.loadJQUI(FacetSearch.Slider.initialize);
                
            }
            //slider: '.mse2_number_slider'
            $('.facetsearch_number_slider').each(function () {
                var $this = $(this);
                var fieldset = $(this).parents('.facetsearch-filter-slider');
                var imin = fieldset.find('input:first');
                var imax = fieldset.find('input:last');
                var vmin = Number(imin.attr('value'));
                var vmax = Number(imax.attr('value'));
                var cmin = Number(imin.data('current-value'));
                var cmax = Number(imax.data('current-value'));
                // Check for decimals
                var ival = imin.val();
                var decimal = ival.indexOf('.') != -1;
                var decimals = decimal
                    ? Number(ival.substr(ival.indexOf('.') + 1).length)
                    : 0;
                var delimiter = 1;
                for (var i = 1; i <= decimals; i++) {
                    delimiter *= 10;
                }
    
                var name = imin.prop('name');
                $this.slider({
                    min: vmin,
                    max: vmax,
                    values: [vmin, vmax],
                    range: true,
                    step: 1 / delimiter,
                    stop: function (e, ui) {
                        imin.val(ui.values[0].toFixed(decimals));
                        imax.val(ui.values[1].toFixed(decimals));
                        imin.add(imax).trigger('change');
                        FacetSearch.Slider.sliders[name]['user_changed'] = true;
                    },
                    change: function (e, ui) {
                        if (FacetSearch.Slider.sliders[name] != undefined && FacetSearch.Slider.sliders[name]['values'] != undefined) {
                            FacetSearch.Slider.sliders[name]['changed'] = FacetSearch.Slider.sliders[name]['values'][0] != ui.values[0].toFixed(decimals) ||
                            FacetSearch.Slider.sliders[name]['values'][1] != ui.values[1].toFixed(decimals);
                        }
                    },
                    slide: function (e, ui) {
                        if (decimal) {
                            imin.val(ui.values[0].toFixed(decimals));
                            imax.val(ui.values[1].toFixed(decimals));
                        } else {
                            imin.val(ui.values[0]);
                            imax.val(ui.values[1]);
                        }
                    }
                });
    
                var changed = FacetSearch.Hash.get()[name] !== undefined;
                FacetSearch.Slider.sliders[name] = {
                    changed: changed,
                    user_changed: changed
                };
    
                var values = FacetSearch.Hash.get();
                if (values[name]) {
                    var tmp = values[name].split(',');
                    if (tmp[0].match(/(?!^-)[^0-9\.]/g)) {
                        tmp[0] = tmp[0].replace(/(?!^-)[^0-9\.]/g, '');
                    }
                    if (tmp.length > 1) {
                        if (tmp[1].match(/(?!^-)[^0-9\.]/g)) {
                            tmp[1] = tmp[1].replace(/(?!^-)[^0-9\.]/g, '');
                        }
                    }
                    imin.val(tmp[0]);
                    imax.val(tmp.length > 1 ? tmp[1] : tmp[0]);
                }
    
                //imin.attr('readonly', true);
                imin.attr('data-decimal', decimals);
                imin.on('change keyup input click', function (e) {
                    if (this.value.match(/(?!^-)[^0-9\.]/g)) {
                        this.value = this.value.replace(/(?!^-)[^0-9\.]/g, '');
                    }
                    if (e.type != 'keyup' && e.type != 'input') {
                        if (this.value > vmax) {
                            this.value = vmax;
                        }
                        else if (this.value < vmin) {
                            this.value = vmin;
                        }
                    }
                    if (e.type == 'change') {
                        FacetSearch.Slider.sliders[name]['user_changed'] = true;
                    }
                    $this.slider('values', 0, this.value);
                });
                //imax.attr('readonly', true);
                imax.attr('data-decimal', decimals);
                imax.on('change keyup input click', function (e) {
                    if (this.value.match(/(?!^-)[^0-9\.]/g)) {
                        this.value = this.value.replace(/(?!^-)[^0-9\.]/g, '');
                    }
                    if (e.type != 'keyup' && e.type != 'input') {
                        if (this.value > vmax) {
                            this.value = vmax;
                        }
                        else if (this.value < vmin) {
                            this.value = vmin;
                        }
                    }
                    if (e.type == 'change') {
                        FacetSearch.Slider.sliders[name]['user_changed'] = true;
                    }
                    $this.slider('values', 1, this.value);
                });
    
                if (values[name]) {
                    imin.add(imax).trigger('click');
                }
    
                FacetSearch.Slider.sliders[name]['values'] = [vmin, vmax];
                if (!isNaN(cmin) && !isNaN(cmax)) {
                    if (cmin != 0 && cmin != vmin) {
                        $this.slider('values', 0, cmin);
                        imin.val(cmin);
                    }
                    if (cmax != 0 && cmax != vmax) {
                        $this.slider('values', 1, cmax);
                        imax.val(cmax);
                    }
                    FacetSearch.Slider.sliders[name]['changed'] = FacetSearch.Hash.get()[name] !== undefined;
                }
            });
            
        },
        loadJQUI: function (callback, parameters) {
            $('<link/>', {
                rel: 'stylesheet',
                type: 'text/css',
                href: FacetSearchConfig['cssUrl'] + 'jquery-ui/jquery-ui.min.css'
            }).appendTo('head');
    
            return $.getScript(FacetSearchConfig['jsUrl'] + 'lib/jquery-ui.min.js').done( function () {
                if (typeof callback == 'function') {
                    callback(parameters);
                }
            });
        },
    };
    FacetSearch.Hash = {
        get: function () {
            
            var vars = {}, hash, splitter, hashes;
            if (!this.oldbrowser()) {
                var pos = window.location.href.indexOf('?');
                hashes = (pos != -1) ? decodeURIComponent(window.location.href.substr(pos + 1)) : '';
                splitter = '&';
            }
            else {
                hashes = decodeURIComponent(window.location.hash.substr(1));
                splitter = '/';
            }
    
            if (hashes.length == 0) {
                return vars;
            }
            else {
                hashes = hashes.split(splitter);
            }
    
            for (var i in hashes) {
                if (hashes.hasOwnProperty(i)) {
                    hash = hashes[i].split('=');
                    if (typeof hash[1] == 'undefined') {
                        vars['anchor'] = hash[0];
                    }
                    else {
                        vars[hash[0]] = hash[1];
                    }
                }
            }
            
            return vars;
        },
        set: function (vars) {
            
            var hash = '';
            var i;
            for (i in vars) {
                if (vars.hasOwnProperty(i)) {
                    hash += '&' + i + '=' + vars[i];
                }
            }
            if (!this.oldbrowser()) {
                if (hash.length != 0) {
                    hash = '?' + hash.substr(1);
                    var specialChars = {"%": "%25", "+": "%2B"};
                    for (i in specialChars) {
                        if (specialChars.hasOwnProperty(i) && hash.indexOf(i) !== -1) {
                            hash = hash.replace(new RegExp('\\' + i, 'g'), specialChars[i]);
                        }
                    }
                }
                window.history.pushState({FacetSearch: document.location.pathname + hash}, '', document.location.pathname + hash);
            }
            else {
                window.location.hash = hash.substr(1);
            }
        },
        add: function (key, val) {
            var hash = this.get();
            hash[key] = val;
            this.set(hash);
        },
        remove: function (key) {
            var hash = this.get();
            delete hash[key];
            this.set(hash);
        },
        clear: function () {
            this.set({});
        },
        oldbrowser: function () {
            return !(window.history && history.pushState);
        }
    };
    $(document).ready(function ($) {
        FacetSearch.initialize();
        var html = $('html');
        html.removeClass('no-js');
        if (!html.hasClass('js')) {
            html.addClass('js');
        }
    });

    window.FacetSearch = FacetSearch;
})(window, document, jQuery, FacetSearchConfig);