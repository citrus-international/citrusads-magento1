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
     * @param   string $adId
     * @return  array
     */
    public function getIdByAdId($adId)
    {
        return $this->_getResource()->getIdByAdId($adId);
    }

    protected function _getResource()
    {
        return parent::_getResource();
    }
}