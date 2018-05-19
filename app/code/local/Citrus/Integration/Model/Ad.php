<?php

class Citrus_Integration_Model_Ad extends Mage_Core_Model_Abstract
{
    /**
     * Entity code.
     * Can be used as part of method name for entity processing
     */
    const ENTITY  = 'ad';
    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'citrus_integration_ad';

    protected $_eventObject = 'ad';

    public function _construct()
    {
        parent::_construct();
        $this->_init('citrusintegration/ad');
    }
    public function getIdByCitrusId($citrusId){
        return $this->_getResource()->getIdByCitrusId($citrusId);
    }
    public function getCitrusIdByGtin($gtin){
        return $this->_getResource()->getCitrusIdByGtin($gtin);
    }
    protected function _getResource()
    {
        return parent::_getResource();
    }
}