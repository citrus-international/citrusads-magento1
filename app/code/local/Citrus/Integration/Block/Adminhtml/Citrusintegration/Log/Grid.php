<?php
class Citrus_Integration_Block_Adminhtml_Citrusintegration_Log_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setDefaultSort('id');
        $this->setId('citrus_integration_log_grid');
        $this->setDefaultDir('asc');
        $this->setSaveParametersInSession(true);
    }

    protected function _getCollectionClass()
    {
        return 'citrusintegration/log_collection';
    }

    protected function _prepareCollection()
    {
        /** @var Varien_Data_Collection $collection */
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        try{
            $this->addColumn(
                'id',
                array(
                    'header'=> $this->__('ID'),
                    'align' =>'left',
                    'width' => '55px',
                    'index' => 'id'
                )
            );
            $this->addColumn(
                'entity_id',
                array(
                    'header'=> $this->__('Magento Entity'),
                    'align' =>'left',
                    'index' => 'entity_id'
                )
            );
            $this->addColumn(
                'type',
                array(
                    'header'=> $this->__('Type'),
                    'align' =>'left',
                    'index' => 'type'
                )
            );

            $this->addColumn(
                'dequeue_time',
                array(
                    'header'=> $this->__('Enqueue Time'),
                    'index' => 'dequeue_time'
                )
            );
            $this->addColumn(
                'status',
                array(
                    'header'=> $this->__('Status'),
                    'index' => 'enqueue_time'
                )
            );
            $this->addColumn(
                'citrus_id',
                array(
                    'header'=> $this->__('Citrus Id'),
                    'index' => 'citrus_id'
                )
            );
            $this->addColumn(
                'message',
                array(
                    'header'=> $this->__('Message'),
                    'index' => 'message'
                )
            );
        }catch (Exception $e){
            error_log("Exception while preparing columns: " . $e->getMessage());
        }

        return parent::_prepareColumns();
    }

//    protected function _prepareMassaction()
//    {
//        $this->setMassactionIdField('id');
//        $this->getMassactionBlock()->setFormFieldName('id');
//
//        $this->getMassactionBlock()->addItem('delete', array(
//            'label'    => Mage::helper('citrusintegration')->__('Delete'),
//            'url'      => $this->getUrl('*/*/massDelete'),
//            'confirm'  => Mage::helper('citrusintegration')->__('Are you sure?')
//        ));
//
//        $this->getMassactionBlock()->addItem('sync', array(
//            'label'    => Mage::helper('citrusintegration')->__('Sync'),
//            'url'      => $this->getUrl('*/*/massSync'),
//            'confirm'  => Mage::helper('citrusintegration')->__('Are you sure?')
//        ));
//
//        return $this;
//    }
}