<?php
class Citrus_Integration_Block_Adminhtml_Citrusintegration_Ad_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
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
        $model = Mage::getModel('citrusintegration/ad');
        $form = new Varien_Data_Form(array(
            'id'        => 'edit_form',
            'action'    => $this->getUrl('*/*/save', array('id' => $this->getRequest()->getParam('id'))),
            'method'    => 'post'
        ));

        $fieldset = $form->addFieldset('ad_fieldset', array(
            'legend'    => Mage::helper('checkout')->__('Ad Information'),
            'class'     => 'fieldset-wide',
        ));

        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', array(
                'name' => 'id',
            ));
        }

        $fieldset->addField('name', 'text', array(
            'name'      => 'id',
            'label'     => Mage::helper('checkout')->__('Ad Id'),
            'title'     => Mage::helper('checkout')->__('Ad Id'),
            'disabled'  => true,
        ));
        $fieldset->addField('name', 'text', array(
            'name'      => 'id',
            'label'     => Mage::helper('checkout')->__('Gtin'),
            'title'     => Mage::helper('checkout')->__('Gtin'),
            'disabled'  => true,
        ));
        $fieldset->addField('name', 'text', array(
            'name'      => 'id',
            'label'     => Mage::helper('checkout')->__('Expiry'),
            'title'     => Mage::helper('checkout')->__('Expiry'),
            'disabled'  => true,
        ));
        $discountFieldset = $form->addFieldset('discount_fieldset', array(
            'legend'    => Mage::helper('checkout')->__('Discount Information'),
            'class'     => 'fieldset-wide',
        ));
        $discountFieldset->addField('name', 'text', array(
            'name'      => 'amount',
            'label'     => Mage::helper('checkout')->__('Amount'),
            'title'     => Mage::helper('checkout')->__('Amount'),
            'disabled'  => true,
        ));
        $discountFieldset->addField('name', 'text', array(
            'name'      => 'minPrice',
            'label'     => Mage::helper('checkout')->__('Min Price'),
            'title'     => Mage::helper('checkout')->__('Min Price'),
            'disabled'  => true,
        ));
        $discountFieldset->addField('name', 'text', array(
            'name'      => 'maxPerCustomer',
            'label'     => Mage::helper('checkout')->__('maxPerCustomer'),
            'title'     => Mage::helper('checkout')->__('maxPerCustomer'),
            'disabled'  => true,
        ));
        $bannerFieldset = $form->addFieldset('banner_fieldset', array(
            'legend'    => Mage::helper('checkout')->__('Banner Information'),
            'class'     => 'fieldset-wide',
        ));
        $bannerFieldset->addField('name', 'text', array(
            'name'      => 'name',
            'label'     => Mage::helper('checkout')->__('Name'),
            'title'     => Mage::helper('checkout')->__('Name'),
            'required'  => true,
        ));
        $productFieldset = $form->addFieldset('product_fieldset', array(
            'legend'    => Mage::helper('checkout')->__('Product Information'),
            'class'     => 'fieldset-wide',
        ));
        $productFieldset->addField('name', 'text', array(
            'name'      => 'name',
            'label'     => Mage::helper('checkout')->__('Name'),
            'title'     => Mage::helper('checkout')->__('Name'),
            'required'  => true,
        ));

        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}