<?php
class Citrus_Integration_Block_Adminhtml_Citrusintegration_Queue_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    public function __construct()
    {
        parent::__construct();
        // Set some defaults for our grid
        $this->setDefaultSort('id');
        $this->setId('citrus_integration_queue_grid');
        $this->setDefaultDir('asc');
        $this->setSaveParametersInSession(true);
    }

    protected function _getCollectionClass()
    {
        // This is the model we are using for the grid
        return 'citrusintegration/queue_collection';
    }

    protected function _prepareCollection()
    {
        // Get and set our collection for the grid
        $collection = Mage::getResourceModel($this->_getCollectionClass());
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
//
    protected function _prepareColumns()
    {
        // Add the columns that should appear in the grid
        $this->addColumn('id',
            array(
                'header'=> $this->__('ID'),
                'align' =>'right',
                'width' => '50px',
                'index' => 'id'
            )
        );
        $this->addColumn('entity_id',
            array(
                'header'=> $this->__('Magento Entity'),
                'align' =>'right',
                'width' => '50px',
                'index' => 'entity_id'
            )
        );
        $this->addColumn('type',
            array(
                'header'=> $this->__('Type'),
                'align' =>'right',
                'width' => '50px',
                'index' => 'type'
            )
        );

        $this->addColumn('enqueue_time',
            array(
                'header'=> $this->__('Enqueue Time'),
                'index' => 'enqueue_time'
            )
        );

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        // This is where our row data will link to
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }
}