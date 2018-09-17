<?php
class Citrus_Integration_Block_Adminhtml_Citrusintegration_Banner_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setDefaultSort('id');
        $this->setId('citrus_integration_banner_grid');
        $this->setDefaultDir('asc');
        $this->setSaveParametersInSession(true);
    }

    protected function _getCollectionClass()
    {
        return 'citrusintegration/banner_collection';
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
                'citrus_id',
                array(
                    'header'=> $this->__('Banner Id'),
                    'align' =>'left',
                    'index' => 'citrus_id'
                )
            );
            $this->addColumn(
                'slotId',
                array(
                    'header'=> $this->__('Slot Id'),
                    'align' =>'left',
                    'index' => 'slotId'
                )
            );
            $this->addColumn(
                'pageType',
                array(
                    'header'=> $this->__('Page Type'),
                    'align' =>'left',
                    'index' => 'pageType'
                )
            );

            $this->addColumn(
                'imageUrl',
                array(
                    'header'=> $this->__('Image Url'),
                    'index' => 'imageUrl',
                    'renderer' => 'Citrus_Integration_Block_Adminhtml_Template_Grid_Renderer_Image'
                )
            );
            $this->addColumn(
                'linkUrl',
                array(
                    'header'=> $this->__('Link Url'),
                    'index' => 'linkUrl'
                )
            );
            $this->addColumn(
                'altText',
                array(
                    'header'=> $this->__('Alt Text'),
                    'index' => 'altText'
                )
            );
            $this->addColumn(
                'expiry',
                array(
                    'header'=> $this->__('Expiry'),
                    'index' => 'expiry'
                )
            );
            $this->addColumn(
                'host',
                array(
                    'header'=> $this->__('Host'),
                    'index' => 'host',
                    'renderer' => 'Citrus_Integration_Block_Adminhtml_Template_Grid_Renderer_Host'
                )
            );
        }catch (Exception $e){
            error_log("Exception while enqueuing: " . $e->getMessage());
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