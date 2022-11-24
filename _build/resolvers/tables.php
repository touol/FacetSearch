<?php
/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $modx->addPackage('facetsearch', MODX_CORE_PATH . 'components/facetsearch/model/');
            $manager = $modx->getManager();
            $objects = [];
            $schemaFile = MODX_CORE_PATH . 'components/facetsearch/model/schema/facetsearch.mysql.schema.xml';
            if (is_file($schemaFile)) {
                $schema = new SimpleXMLElement($schemaFile, 0, true);
                if (isset($schema->object)) {
                    foreach ($schema->object as $obj) {
                        $objects[] = (string)$obj['class'];
                    }
                }
                unset($schema);
            }
            foreach ($objects as $class) {
                $table = $modx->getTableName($class);
                $sql = "SHOW TABLES LIKE '" . trim($table, '`') . "'";
                $stmt = $modx->prepare($sql);
                $newTable = true;
                if ($stmt->execute() && $stmt->fetchAll()) {
                    $newTable = false;
                }
                // If the table is just created
                if ($newTable) {
                    $manager->createObjectContainer($class);
                } else {
                    // If the table exists
                    // 1. Operate with tables
                    $tableFields = [];
                    $c = $modx->prepare("SHOW COLUMNS IN {$modx->getTableName($class)}");
                    $c->execute();
                    while ($cl = $c->fetch(PDO::FETCH_ASSOC)) {
                        $tableFields[$cl['Field']] = $cl['Field'];
                    }
                    foreach ($modx->getFields($class) as $field => $v) {
                        if (in_array($field, $tableFields)) {
                            unset($tableFields[$field]);
                            $manager->alterField($class, $field);
                        } else {
                            $manager->addField($class, $field);
                        }
                    }
                    foreach ($tableFields as $field) {
                        $manager->removeField($class, $field);
                    }
                    // 2. Operate with indexes
                    $indexes = [];
                    $c = $modx->prepare("SHOW INDEX FROM {$modx->getTableName($class)}");
                    $c->execute();
                    while ($row = $c->fetch(PDO::FETCH_ASSOC)) {
                        $name = $row['Key_name'];
                        if (!isset($indexes[$name])) {
                            $indexes[$name] = [$row['Column_name']];
                        } else {
                            $indexes[$name][] = $row['Column_name'];
                        }
                    }
                    foreach ($indexes as $name => $values) {
                        sort($values);
                        $indexes[$name] = implode(':', $values);
                    }
                    $map = $modx->getIndexMeta($class);
                    // Remove old indexes
                    foreach ($indexes as $key => $index) {
                        if (!isset($map[$key])) {
                            if ($manager->removeIndex($class, $key)) {
                                $modx->log(modX::LOG_LEVEL_INFO, "Removed index \"{$key}\" of the table \"{$class}\"");
                            }
                        }
                    }
                    // Add or alter existing
                    foreach ($map as $key => $index) {
                        ksort($index['columns']);
                        $index = implode(':', array_keys($index['columns']));
                        if (!isset($indexes[$key])) {
                            if ($manager->addIndex($class, $key)) {
                                $modx->log(modX::LOG_LEVEL_INFO, "Added index \"{$key}\" in the table \"{$class}\"");
                            }
                        } else {
                            if ($index != $indexes[$key]) {
                                if ($manager->removeIndex($class, $key) && $manager->addIndex($class, $key)) {
                                    $modx->log(modX::LOG_LEVEL_INFO,
                                        "Updated index \"{$key}\" of the table \"{$class}\""
                                    );
                                }
                            }
                        }
                    }
                }
            }
            // $mfClasss = [
            //     [
            //         'sort'=>1,
            //         'alias'=>'tv',
            //         'class'=>'modTemplateVar',
            //     ],
            //     [
            //         'sort'=>2,
            //         'alias'=>'ms',
            //         'class'=>'msProductData',

            //     ],
            //     [
            //         'sort'=>3,
            //         'alias'=>'msoption',
            //         'class'=>'msOption',
            //     ],
            // ];
            // foreach($mfClasss as $o){
            //     if(!$opt = $modx->getObject("mfClass",['alias'=>$o['alias']])){
            //         if($opt = $modx->newObject("mfClass",$o)){
            //             $opt->save(); 
            //         }
            //     }
            // }
            // $mfFilters = [
            //     [
            //         'name'=>'default',
            //     ],
            //     [
            //         'name'=>'number',
            //     ],
            //     [
            //         'name'=>'decimal',
            //     ],
            //     [
            //         'name'=>'boolean',
            //     ],
            // ];
            // foreach($mfFilters as $o){
            //     if(!$opt = $modx->getObject("mfFilter",['name'=>$o['name']])){
            //         if($opt = $modx->newObject("mfFilter",$o)){
            //             $opt->save(); 
            //         }
            //     }
            // }
            //'types'=>'text,checkbox,autotag
            //'types'=>'input,float,combobox_json,combobox_boolean,checkbox',
            //'types'=>'textfield,combo-options',
            // $mfFieldTypes = [
            //     [
            //         'alias'=>'tv',
            //         'name'=>'text',
            //         'label'=>'tv техт',
            //     ],
            //     [
            //         'alias'=>'tv',
            //         'name'=>'checkbox',
            //         'label'=>'tv флажок',
            //     ],
            //     [
            //         'alias'=>'tv',
            //         'name'=>'autotag',
            //         'label'=>'tv список с автодополнениями',
            //     ],
            //     [
            //         'alias'=>'ms',
            //         'name'=>'input',
            //         'label'=>'ms техт',
            //     ],
            //     [
            //         'alias'=>'ms',
            //         'name'=>'float',
            //         'label'=>'ms число',
            //     ],
            //     [
            //         'alias'=>'ms',
            //         'name'=>'combobox_json',
            //         'label'=>'ms список с автодополнениями',
            //     ],
            //     [
            //         'alias'=>'ms',
            //         'name'=>'checkbox',
            //         'label'=>'ms флажок',
            //     ],
            //     [
            //         'alias'=>'msoption',
            //         'name'=>'textfield',
            //         'label'=>'msoption текст',
            //     ],
            //     [
            //         'alias'=>'msoption',
            //         'name'=>'combo-options',
            //         'label'=>'msoption список с автодополнениями',
            //     ],
            // ];
            // foreach($mfFieldTypes as $o){
            //     if(!$opt = $modx->getObject("mfFieldType",['name'=>$o['name']])){
            //         if($opt = $modx->newObject("mfFieldType",$o)){
            //             $opt->save(); 
            //         }
            //     }
            // }
            break;

        case xPDOTransport::ACTION_UNINSTALL:
            break;
    }
}

return true;