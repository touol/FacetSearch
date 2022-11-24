<?php

class FacetSearchOfficeItemCreateProcessor extends modObjectCreateProcessor
{
    public $objectType = 'FacetSearchItem';
    public $classKey = 'FacetSearchItem';
    public $languageTopics = ['facetsearch'];
    //public $permission = 'create';


    /**
     * @return bool
     */
    public function beforeSet()
    {
        $name = trim($this->getProperty('name'));
        if (empty($name)) {
            $this->modx->error->addField('name', $this->modx->lexicon('facetsearch_item_err_name'));
        } elseif ($this->modx->getCount($this->classKey, ['name' => $name])) {
            $this->modx->error->addField('name', $this->modx->lexicon('facetsearch_item_err_ae'));
        }

        return parent::beforeSet();
    }

}

return 'FacetSearchOfficeItemCreateProcessor';