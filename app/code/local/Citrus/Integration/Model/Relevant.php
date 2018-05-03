<?php

class Citrus_Integration_Model_Relevant extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('citrusintegration/relevant');
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