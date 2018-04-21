<?php

class Citrus_Integration_Model_Ad extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('citrusintegration/ad');
    }
    public function getIdByCitrusId($citrusId){
        return $this->_getResource()->getIdByCitrusId($citrusId);
    }
    protected function _getResource()
    {
        return parent::_getResource();
    }
}