<?php
include_once dirname(__FILE__) . '/update.class.php';
class FacetSearchItemEnableProcessor extends FacetSearchItemUpdateProcessor
{
    public function beforeSet()
    {
        $this->setProperty('active', true);
        return true;
    }
}
return 'FacetSearchItemEnableProcessor';