<?php

return [
    'FacetSearch' => [
        'file' => 'facetsearch',
        'description' => 'FacetSearch snippet',
        'properties' => [
            'element' => [
                'type' => 'textfield',
                'value' => 'pdoResources',
            ],
            'tpl' => [
                'type' => 'textfield',
                'value' => 'tpl.FacetSearch.row',
            ],
            'tplOuter'=>[
                'type' => 'textfield',
                'value' => 'tpl.FacetSearch.Outer',
            ],
            'tplFilter.outer.default'=>[
                'type' => 'textfield',
                'value' => 'tpl.FacetSearch.filter.outer',
            ],
            'tplFilter.row.default'=>[
                'type' => 'textfield',
                'value' => 'tpl.FacetSearch.filter.checkbox',
            ],
            'tplFilter.outer.slider'=>[
                'type' => 'textfield',
                'value' => 'tpl.FacetSearch.filter.slider',
            ],
            'tplFilter.row.number'=>[
                'type' => 'textfield',
                'value' => 'tpl.FacetSearch.filter.number',
            ],
            'js' => [
                'type' => 'textfield',
                'value' => 'js/web/default.js',
            ],
            'css' => [
                'type' => 'textfield',
                'value' => 'css/web/default.css',
            ],
            'limit' => [
                'type' => 'numberfield',
                'value' => 10,
            ],
            'outputSeparator' => [
                'type' => 'textfield',
                'value' => "\n",
            ],
            'toPlaceholder' => [
                'type' => 'combo-boolean',
                'value' => false,
            ],
            'pageLimit' => [
                'type' => 'numberfield',
                'value' => 5,
            ],
            'ajaxMode' => [
                'type' => 'list',
                'options' => [
                    ['text' => 'default', 'value' => 'default'],
                    ['text' => 'button', 'value' => 'button'],
                    ['text' => 'scroll', 'value' => 'scroll'],
                ],
                'value' => 'default',
            ],
            'sortOption' => [
                'type' => 'list',
                'options' => [
                    ['text' => 'count', 'value' => 'count'],
                    ['text' => 'asc', 'value' => 'asc'],
                    ['text' => 'desc', 'value' => 'desc'],
                ],
                'value' => 'default',
            ],
            'pageVarKey' => [
                'type' => 'textfield',
                'value' => 'page',
            ],
            'tplPage' => [
                'type' => 'textfield',
                'value' => '@INLINE <li class="page-item"><a class="page-link" href="[[+href]]">[[+pageNo]]</a></li>',
            ],
            'tplPageActive' => [
                'type' => 'textfield',
                'value' => '@INLINE <li class="page-item active"><a class="page-link" href="[[+href]]">[[+pageNo]]</a></li>',
            ],
            'tplPageFirst' => [
                'type' => 'textfield',
                'value' => '@INLINE <li class="page-item"><a class="page-link" href="[[+href]]">[[%pdopage_first]]</a></li>',
            ],
            'tplPageFirstEmpty' => [
                'type' => 'textfield',
                'value' => '@INLINE <li class="page-item disabled"><a class="page-link" href="#">[[%pdopage_first]]</a></li>',
            ],
            'tplPageLast' => [
                'type' => 'textfield',
                'value' => '@INLINE <li class="page-item"><a class="page-link" href="[[+href]]">[[%pdopage_last]]</a></li>',
            ],
            'tplPageLastEmpty' => [
                'type' => 'textfield',
                'value' => '@INLINE <li class="page-item disabled"><a class="page-link" href="#">[[%pdopage_last]]</a></li>',
            ],
            'tplPageNext' => [
                'type' => 'textfield',
                'value' => '@INLINE <li class="page-item"><a class="page-link" href="[[+href]]">&raquo;</a></li>',
            ],
            'tplPageNextEmpty' => [
                'type' => 'textfield',
                'value' => '@INLINE <li class="page-item disabled"><a class="page-link" href="#">&raquo;</a></li>',
            ],
            'tplPagePrev' => [
                'type' => 'textfield',
                'value' => '@INLINE <li class="page-item"><a class="page-link" href="[[+href]]">&laquo;</a></li>',
            ],
            'tplPagePrevEmpty' => [
                'type' => 'textfield',
                'value' => '@INLINE <li class="page-item disabled"><a class="page-link" href="#">&laquo;</a></li>',
            ],
            'tplPageSkip' => [
                'type' => 'textfield',
                'value' => '@INLINE <li class="page-item disabled"><a class="page-link" href="#">...</a></li>',
            ],
            'tplPageWrapper' => [
                'type' => 'textfield',
                'value' => '@INLINE <ul class="pagination">[[+first]][[+prev]][[+pages]][[+next]][[+last]]</ul>',
            ],
        ],
    ],
];