<?php

class Citrus_Integration_Model_Mysql4_Relevant_Collection
    extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
//        parent::__construct();
        $this->_init('citrusintegration/relevant');
    }
}