<?php

class Citrus_Integration_Model_Slotid extends Mage_Core_Model_Abstract
{
    /**
     * Entity code.
     * Can be used as part of method name for entity processing
     */
    const ENTITY                 = 'slotid';
    public function _construct()
    {
        parent::_construct();
        $this->_init('citrusintegration/slotid');
    }
    public function getSlotIdById($entity_id,$pageType){
        return $this->_getResource()->getSlotIdById($entity_id,$pageType);
    }

    protected function _getResource()
    {
        return parent::_getResource();
    }
}