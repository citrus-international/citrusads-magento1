<?php
class Citrus_Integration_Block_Adminhtml_Citrusintegration_Slotid_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        $this->setDefaultSort('id');
        $this->setId('citrus_integration_slotid_grid');
        $this->setDefaultDir('asc');
        $this->setSaveParametersInSession(true);
    }

    protected function _getCollectionClass()
    {
        return 'citrusintegration/slotid_collection';
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
            $this->addColumn(
                'id',
                array(
                    'header' => $this->__('ID'),
                    'align' => 'left',
                    'width' => '55px',
                    'index' => 'id'
                )
            );
            $this->addColumn(
                'page_type',
                array(
                    'header' => $this->__('Page Type'),
                    'align' => 'left',
                    'index' => 'page_type',
                    'renderer' => 'Citrus_Integration_Block_Adminhtml_Template_Grid_Renderer_PageType'
                )
            );
            $this->addColumn(
                'page_id',
                array(
                    'header' => $this->__('Page Id'),
                    'align' => 'left',
                    'index' => 'page_id',
                    'renderer' => 'Citrus_Integration_Block_Adminhtml_Template_Grid_Renderer_PageId'
                )
            );

            $this->addColumn(
                'slot_id',
                array(
                    'header' => $this->__('Slot Id'),
                    'index' => 'slot_id'
                )
            );
        }catch (Exception $e){
        }

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

}