<?php

class Citrus_Integration_Model_System_Config_Source_Page
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'Home', 'label'=>Mage::helper('adminhtml')->__('Home')),
            array('value' => 'Category', 'label'=>Mage::helper('adminhtml')->__('Category')),
            array('value' => 'Search', 'label'=>Mage::helper('adminhtml')->__('Search')),
            array('value' => 'Specials', 'label'=>Mage::helper('adminhtml')->__('Specials')),
            array('value' => 'PastOrder', 'label'=>Mage::helper('adminhtml')->__('PastOrder')),
            array('value' => 'Substitution', 'label'=>Mage::helper('adminhtml')->__('Substitution')),
            array('value' => 'Samples', 'label'=>Mage::helper('adminhtml')->__('Samples')),
        );
    }

}
