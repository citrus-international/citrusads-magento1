<?php

class Citrus_Integration_Model_Catalog extends Mage_Core_Model_Abstract
{
    /**
     * Entity code.
     * Can be used as part of method name for entity processing
     */
    const ENTITY                 = 'catalog';
    public function _construct()
    {
        parent::_construct();
        $this->_init('citrusintegration/catalog');
    }

    /**
     * Retrieve catalog_id by name
     *
     * @param   string $name
     * @return  integer
     */
    public function getCatalogIdByName($name)
    {
        return $this->_getResource()->getCatalogIdByName($name);
    }
    /**
     * Retrieve id by name
     *
     * @param   string $name
     * @return  integer
     */
    public function getIdByName($name)
    {
        return $this->_getResource()->getIdByName($name);
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