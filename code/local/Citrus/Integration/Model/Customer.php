<?php

class Citrus_Integration_Model_Customer extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('citrusintegration/customer');
    }

    /**
     * Retrieve customer_id by id
     *
     * @param   string $entityId
     * @return  integer
     */
    public function getCustomerIdByEntityId($entityId)
    {
        return $this->_getResource()->getCustomerIdByEntityId($entityId);
    }
    /**
     * Retrieve resource instance wrapper
     *
     * @return Citrus_Integration_Model_Resource_Catalog
     */
    protected function _getResource()
    {
        return parent::_getResource();
    }
}