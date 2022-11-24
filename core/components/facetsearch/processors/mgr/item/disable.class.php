<?php
include_once dirname(__FILE__) . '/update.class.php';
class FacetSearchItemDisableProcessor extends FacetSearchItemUpdateProcessor
{
    public function beforeSet()
    {
        $this->setProperty('active', false);
        return true;
    }
}
return 'FacetSearchItemDisableProcessor';
