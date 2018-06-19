<?php

class Citrus_Integration_Model_Queue extends Mage_Core_Model_Abstract
{
    /**
     * Entity code.
     * Can be used as part of method name for entity processing
     */
    const ENTITY                 = 'queue';
    public function _construct()
    {
        parent::_construct();
        $this->_init('citrusintegration/queue');
    }
    protected function _getResource()
    {
        return parent::_getResource();
    }
    public function getCount(){
        return $this->_getResource()->getCount();
    }

    public function enqueue($entityId, $type){
        $data = [
            'type' => $type,
            'entity_id' => $entityId,
            'enqueue_time' => time()
        ];
        $this->addData($data);
        try{
            $this->save();
        }catch(Exception $e){

        }

    }
    public function makeDelete($ids, $type = null){
        return $this->_getResource()->makeDelete($ids, $type);
    }
}