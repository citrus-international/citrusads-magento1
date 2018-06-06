<?php

class Citrus_Integration_Helper_Product_View extends Mage_Catalog_Helper_Product_View
{
    // List of exceptions throwable during prepareAndRender() method
    public $ERR_NO_PRODUCT_LOADED = 1;
    public $ERR_BAD_CONTROLLER_INTERFACE = 2;

    /**
     * Prepares product view page - inits layout and all needed stuff
     *
     * $params can have all values as $params in Mage_Catalog_Helper_Product - initProduct().
     * Plus following keys:
     *   - 'buy_request' - Varien_Object holding buyRequest to configure product
     *   - 'specify_options' - boolean, whether to show 'Specify options' message
     *   - 'configure_mode' - boolean, whether we're in Configure-mode to edit product configuration
     *
     * @param int $productId
     * @param Mage_Core_Controller_Front_Action $controller
     * @param null|Varien_Object $params
     *
     * @return Mage_Catalog_Helper_Product_View
     */
    public function prepareAndRender($productId, $controller, $params = null)
    {
        // Prepare data
        $productHelper = Mage::helper('catalog/product');
        if (!$params) {
            $params = new Varien_Object();
        }

        // Standard algorithm to prepare and rendern product view page
        $productId = $this->checkProduct($productId);
        $product = $productHelper->initProduct($productId, $controller, $params);
        if (!$product) {
            throw new Mage_Core_Exception($this->__('Product is not loaded'), $this->ERR_NO_PRODUCT_LOADED);
        }

        $buyRequest = $params->getBuyRequest();
        if ($buyRequest) {
            $productHelper->prepareProductOptions($product, $buyRequest);
        }

        if ($params->hasConfigureMode()) {
            $product->setConfigureMode($params->getConfigureMode());
        }

        Mage::dispatchEvent('catalog_controller_product_view', array('product' => $product));

        if ($params->getSpecifyOptions()) {
            $notice = $product->getTypeInstance(true)->getSpecifyOptionMessage();
            Mage::getSingleton('catalog/session')->addNotice($notice);
        }

        Mage::getSingleton('catalog/session')->setLastViewedProductId($product->getId());

        $this->initProductLayout($product, $controller);

        $controller->initLayoutMessages(array('catalog/session', 'tag/session', 'checkout/session'))
            ->renderLayout();
        return $this;
    }

    public function checkProduct($product_id)
    {
        $productModel = Mage::getModel(Mage_Catalog_Model_Product::class);
        /** @var Mage_Catalog_Model_Product $product */
        $product = $productModel->load($product_id);
        /** @var Mage_Catalog_Model_Product_Type_Configurable $configurationModel */
        $configurationModel = Mage::getModel(Mage_Catalog_Model_Product_Type_Configurable::class);
        $parentIds = $configurationModel->getParentIdsByChild($product->getId());
        if (!empty($parentIds)) {
            $parentProduct = Mage::getModel('catalog/product')->load($parentIds[0]);
            $attributes = $parentProduct->getTypeInstance()->getConfigurableAttributes($parentProduct);
            $attribute_values = [];
            foreach ($attributes->getItems() as $attribute) {
                /** @var Mage_Catalog_Model_Product_Type_Configurable_Attribute $attribute_code */
                $_attr = $attribute->getProductAttribute();
                $attribute_code = (string)$_attr->getAttributeCode();
                $attribute_values[$attribute_code] = $product->getData($attribute_code);
            }
            Mage::register('redirect_op', $attribute_values);
            return $parentProduct->getId();
        } else {
            return $product_id;
        }
    }
}
