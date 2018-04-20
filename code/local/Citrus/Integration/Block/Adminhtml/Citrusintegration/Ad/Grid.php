<?php
class Citrus_Integration_Block_Adminhtml_Citrusintegration_Ad_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setDefaultSort('id');
        $this->setId('citrus_integration_ad_grid');
        $this->setDefaultDir('asc');
        $this->setSaveParametersInSession(true);
    }

    protected function _getCollectionClass()
    {
        return 'citrusintegration/ad_collection';
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
        try {
            $this->addColumn('id',
                array(
                    'header' => $this->__('ID'),
                    'align' => 'left',
                    'width' => '55px',
                    'index' => 'id'
                )
            );
            $this->addColumn('citrus_id',
                array(
                    'header' => $this->__('Citrus Id'),
                    'align' => 'left',
                    'index' => 'citrus_id'
                )
            );
            $this->addColumn('discount_id',
                array(
                    'header' => $this->__('Discount Id'),
                    'align' => 'left',
                    'index' => 'discount_id'
                )
            );

            $this->addColumn('expiry',
                array(
                    'header' => $this->__('Expiry'),
                    'index' => 'expiry'
                )
            );
        }catch (Exception $e){

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