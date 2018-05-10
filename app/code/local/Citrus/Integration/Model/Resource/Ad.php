<?php

class Citrus_Integration_Model_Resource_Ad extends Mage_Core_Model_Resource_Db_Abstract
{
    public function _construct()
    {
        $this->_init('citrusintegration/ad', 'id');
    }
    public function getIdByCitrusId($citrusId){
        $host = $this->getHelper()->getHost();
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
            ->from(self::getMainTable(), 'id')
            ->where('citrus_id = :citrusId')
            ->where('host = :host');
        $bind = array(
            ':citrusId' => (string)$citrusId,
            ':host' => (string)$host
        );
        return $adapter->fetchOne($select, $bind);
    }
    /**
     * @param $pageType
     * @return array
     */
    public function getAds()
    {
        $datetime = new DateTime();
//        $limit = Mage::getStoreConfig('citrus/citrus_banner/'.strtolower($pageType).'_limit', Mage::app()->getStore());
        $now = $datetime->format('Y-m-d\TH:i:s\Z');
        $host = $this->getHelper()->getHost();
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
            ->from(self::getMainTable(), 'gtin')
            ->where('host = :host')
            ->where('expiry >= :expiry' );
        $bind = array(
            ':host' => (string)$host,
            ':expiry' => $now
        );
        return $adapter->fetchAll($select, $bind);
    }
    /**
     * @return Mage_Core_Helper_Abstract|Citrus_Integration_Helper_Data
     */
    protected function getHelper(){
        return Mage::helper('citrusintegration/data');
    }
}