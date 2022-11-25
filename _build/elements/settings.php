<?php

return [
    'enable_upload' => [
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'facetsearch_main',
    ],
    
    'handler_class' => [
      'xtype' => 'textfield',
      'value' => 'FacetSearchHandler',
      'area' => 'facetsearch_main',
    ],
    'last_upload' => [
      'xtype' => 'textfield',
      'value' => '',
      'area' => 'facetsearch_main',
    ],
    'index' => [
      'xtype' => 'textfield',
      'value' => '',
      'area' => 'facetsearch_main',
    ],
    'server_url' => [
      'xtype' => 'textfield',
      'value' => '',
      'area' => 'facetsearch_main',
    ],
    'api_key' => [
      'xtype' => 'textfield',
      'value' => '',
      'area' => 'facetsearch_main',
    ],
    'build_index_status' => [
      'xtype' => 'textfield',
      'value' => '0',
      'area' => 'facetsearch_main',
    ],
    'aggs_size' => [
      'xtype' => 'textfield',
      'value' => '500',
      'area' => 'facetsearch_main',
    ],
    'admin' => [
        'xtype' => 'textfield',
        'force_update'=>1,
        'value' => '{
          "loadModels": "facetsearch",
          "selects": {
            "class": {
              "type": "data",
              "rows": [
                [1,"Ресурс"],
                [2,"ms"],
                [3,"msoption"],
                [4,"TV"],
                [5,"msvendor"]
              ]
            },
            "option_type": {
              "type": "data",
              "rows": [
                [1,"Строка"],
                [2,"Число"],
                [3,"Массив чисел"],
                [4,"Массив строк"]
              ]
            }
          },
          "tabs": {
            "fsOption": {
              "label": "Опции",
              "table": {
                "class": "fsOption",
                "actions": {
                  "create": [],
                  "update": [],
                  "get_options": {
                    "cls": "btn blue",
                    "text": "Обновить таблицу опций",
                    "action": "facetsearch/get_options",
                    "multiple": {
                      "title": "Обновить таблицу опций"
                    }
                  },
                  "create_index": {
                    "cls": "btn blue",
                    "text": "Создать индекс",
                    "action": "facetsearch/create_index",
                    "multiple": {
                      "title": "Создать индекс"
                    }
                  },
                  "rebuild_index": {
                    "cls": "btn blue",
                    "text": "Ребилд индекс",
                    "action": "facetsearch/rebuild_index",
                    "multiple": {
                      "title": "Ребилд индекс"
                    }
                  },
                  "build_index_status": {
                    "cls": "btn blue",
                    "text": "Статус",
                    "long_process": 1,
                    "action": "facetsearch/build_index_status",
                    "multiple": {
                      "title": "Статус"
                    }
                  },
                  "delete_index": {
                    "cls": "btn red",
                    "text": "Удалить индекс",
                    "action": "facetsearch/delete_index",
                    "multiple": {
                      "title": "Удалить индекс"
                    }
                  },
                  "remove": []
                },
                "pdoTools": {
                  "class": "fsOption"
                },
                "checkbox": 1,
                "autosave": 1,
                "row": {
                  "id": [],
                  "class_id": {
                    "label": "Класс таблицы",
                    "edit": {
                      "type": "select",
                      "select": "class"
                    },
                    "filter": 1
                  },
                  "key": {
                    "label": "Ключ",
                    "filter": 1
                  },
                  "alias": {
                    "label": "Алиас"
                  },
                  "option_type_id": {
                    "label": "Тип опции",
                    "edit": {
                      "type": "select",
                      "select": "option_type"
                    },
                    "filter": 1
                  },
                  "label": {
                    "label": "Заголовок фильтра",
                    "filter": 1
                  },
                  "active": {
                    "label": "Активно",
                    "filter": 1,
                    "edit": {
                      "type": "checkbox"
                    },
                    "default": 1
                  }
                }
              }
            }
          }
        }',
        'area' => 'facetsearch_main',
    ],
];