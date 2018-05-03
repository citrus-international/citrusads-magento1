<?php
class Citrus_Integration_Block_Adminhtml_Citrusintegration_Ad_Request extends Mage_Adminhtml_Block_Widget_Form

{
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array(
            'id' => 'request_form',
            'action' => $this->getUrl('*/*/send'),
            'method' => 'post',
        ));
        $is_banner = Mage::registry('is_banner');
        $fieldset = $form->addFieldset('citrusintegration_form', array('legend'=>Mage::helper('citrusintegration')->__('Context')));

        $pageType = $fieldset->addField('pageType', 'select', array(
            'label'     => Mage::helper('citrusintegration')->__('Page Type'),
            'class'     => 'required-entry',
            'values'    => Mage::getModel('citrusintegration/system_config_source_page')->toOptionArray(),
            'required'  => true,
            'name'      => 'pageType',
        ));

        $searchTerm = $fieldset->addField('searchTerm', 'text', array(
            'label'     => Mage::helper('citrusintegration')->__('Search Term'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'searchTerm',
        ));

        $productFilter = $fieldset->addField('productFilter', 'text', array(
            'label'     => Mage::helper('citrusintegration')->__('Product Filters'),
            'class'     => 'required-entry',
//            'required'  => true,
            'name'      => 'productFilters',
        ));
        $maxNumberOfAds = $fieldset->addField('maxNumberOfAds', 'text', array(
            'label'     => Mage::helper('citrusintegration')->__('Max Number Of Ads'),
            'class'     => 'required-entry',
            'required'  => true,
            'name'      => 'maxNumberOfAds',
        ));
        $fieldset->addField('is_banner', 'hidden', array(
            'label'     => Mage::helper('citrusintegration')->__('Is Banner'),
            'name'      => 'is_banner',
            'value'     => $is_banner
        ));
        $bannerSlotIds = $fieldset->addField('bannerSlotIds', 'text', array(
            'label'     => Mage::helper('citrusintegration')->__('Banner Slot Ids'),
            'name'      => 'bannerSlotIds',
        ));
        $submit = $fieldset->addField('submit', 'submit', array(
            'label' => Mage::helper('citrusintegration')->__('Send Request'),
            'value' => Mage::helper('citrusintegration')->__('Submit'),
            'name'  => 'submit',
            'class' => 'form-button',
            'onclick' => "setLocation('{$this->getUrl('*/*/send')}')",
        ));


        $this->setForm($form);
        $this->setChild('form_after', $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence')
            ->addFieldMap($pageType->getHtmlId(), $pageType->getName())
            ->addFieldMap($searchTerm->getHtmlId(), $searchTerm->getName())
            ->addFieldMap($productFilter->getHtmlId(), $productFilter->getName())
//            ->addFieldDependence(
//                $productFilter->getName(),
//                $pageType->getName(),
//                'Category'
//            )
            ->addFieldDependence(
                $searchTerm->getName(),
                $pageType->getName(),
                'Search'
            )
        );
        $form->setUseContainer(true);

        return parent::_prepareForm();
    }
}