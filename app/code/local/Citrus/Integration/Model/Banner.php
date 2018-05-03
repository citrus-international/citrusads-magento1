<?php

class Citrus_Integration_Model_Banner extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('citrusintegration/banner');
    }

    /**
     * Retrieve catalog_id by name
     *
     * @param   string $citrusId
     * @return  string
     */
    public function getIdByCitrusId($citrusId){
        return $this->_getResource()->getIdByCitrusId($citrusId);
    }
    public function getBannerByPageType($pageType){
        return $this->_getResource()->getBannerByPageType($pageType);
    }

    protected function _getResource()
    {
        return parent::_getResource();
    }
}