<?php
class Citrus_Integration_Block_Adminhtml_Citrusintegration_Slotid_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Init class
     */
    public function __construct()
    {
        parent::__construct();

        $this->setId('citrus_ad_form');
        $this->setTitle($this->__('Ad Information'));
    }

    /**
     * Setup form fields for inserts/updates
     *
     * return Mage_Adminhtml_Block_Widget_Form
     */
    protected function _prepareForm()
    {
        $model = Mage::registry('slotid_model');
        $form = new Varien_Data_Form(array(
            'id'        => 'edit_form',
            'action'    => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
            'method'    => 'post'
        ));

        $fieldset = $form->addFieldset('slotid_fieldset', array(
            'legend'    => Mage::helper('checkout')->__('SlotId Information'),
        ));

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', array(
                'name' => 'id',
            ));
        }
        $page_type = $fieldset->addField('page_type', 'select', array(
            'name'      => 'page_type',
            'label'     => Mage::helper('checkout')->__('Page Type'),
            'title'     => Mage::helper('checkout')->__('Page Type'),
            'values' => [
                '0' => 'All Pages',
                '1' => 'Category Pages',
                '2' => 'CMS Pages',
                '3' => 'Search Pages',
            ],
            'require' => true
        ));
        $category_page = $fieldset->addField('page_category_id', 'multiselect', array(
            'name'      => 'page_id',
            'label'     => Mage::helper('checkout')->__('Page Id'),
            'title'     => Mage::helper('checkout')->__('Page Id'),
            'values' => Mage::helper('citrusintegration')->getAllCategoriesArray(true),
            'require' => true
        ));
        $cms_page = $fieldset->addField('page_cms_id', 'multiselect', array(
            'name'      => 'page_id',
            'label'     => Mage::helper('checkout')->__('Page Id'),
            'title'     => Mage::helper('checkout')->__('Page Id'),
            'values' => Mage::getModel('cms/page')->getCollection()->toOptionArray(),
            'require' => true
        ));
        $fieldset->addField('slot_id', 'text', array(
            'name'      => 'slot_id',
            'label'     => Mage::helper('checkout')->__('Slot Id'),
            'title'     => Mage::helper('checkout')->__('Slot Id'),
            'require' => true
        ));

        $form->setValues($model->getData());

        $this->setForm($form);
        $this->setChild('form_after', $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence')
            ->addFieldMap($page_type->getHtmlId(), $page_type->getName())
            ->addFieldMap($category_page->getHtmlId(), $category_page->getHtmlId())
            ->addFieldMap($cms_page->getHtmlId(), $cms_page->getHtmlId())
            ->addFieldDependence(
                $cms_page->getHtmlId(),
                $page_type->getName(),
                Citrus_Integration_Helper_Data::CITRUS_PAGE_TYPE_CMS
            )
            ->addFieldDependence(
                $category_page->getHtmlId(),
                $page_type->getName(),
                Citrus_Integration_Helper_Data::CITRUS_PAGE_TYPE_CATEGORY
            )
        );
        $form->setUseContainer(true);
        return parent::_prepareForm();
    }
}