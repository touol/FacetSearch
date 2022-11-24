<?php

class FacetSearchOfficeItemRemoveProcessor extends modObjectProcessor
{
    public $objectType = 'FacetSearchItem';
    public $classKey = 'FacetSearchItem';
    public $languageTopics = ['facetsearch'];
    //public $permission = 'remove';


    /**
     * @return array|string
     */
    public function process()
    {
        if (!$this->checkPermissions()) {
            return $this->failure($this->modx->lexicon('access_denied'));
        }

        $ids = $this->modx->fromJSON($this->getProperty('ids'));
        if (empty($ids)) {
            return $this->failure($this->modx->lexicon('facetsearch_item_err_ns'));
        }

        foreach ($ids as $id) {
            /** @var FacetSearchItem $object */
            if (!$object = $this->modx->getObject($this->classKey, $id)) {
                return $this->failure($this->modx->lexicon('facetsearch_item_err_nf'));
            }

            $object->remove();
        }

        return $this->success();
    }

}

return 'FacetSearchOfficeItemRemoveProcessor';