<?php
/**
 * Citrus_Integration extension
 * @package Citrus_Integration
 * @copyright Copyright (c) 2017 Assembly Payments (https://assemblypayments.com/)
 */

/**
 * Class Citrus_Integration_Helper_Data
 */
class Citrus_Integration_Helper_Data extends Mage_Core_Helper_Data
{
    const CITRUS_STAGING_SERVER = "https://staging-integration.citrusad.com/v1/";
    const CITRUS_AU_SERVER = "https://au-integration.citrusad.com/v1/";
    const CITRUS_US_SERVER = "https://us-integration.citrusad.com/v1/";

    public function getTeamId(){
        return Mage::getStoreConfig('citrus/citrus_group/team_id', Mage::app()->getStore());
    }

    public function getApiKey(){
        return Mage::getStoreConfig('citrus/citrus_group/api_key', Mage::app()->getStore());
    }

    public function getHost(){
        $host = Mage::getStoreConfig('citrus/citrus_group/host', Mage::app()->getStore());
        switch ($host){
            case 1:
                return self::CITRUS_AU_SERVER;
                break;
            case 2:
                return self::CITRUS_US_SERVER;
                break;
            default:
                return self::CITRUS_STAGING_SERVER;
                break;
        }
    }
    /**
     * @param $name string
     * @return false|string
     */
    protected function getCitrusCatalogId($name = null){
        $model = Mage::getModel('citrusintegration/catalog');
        if($name)
            return $model->getCatalogIdByName($name);
        return $model->getCatalogId();
    }

    /**
     * @param $entity Mage_Sales_Model_Order
     * @return mixed
     */
    public function getOrderData($entity){
        $teamId = $this->getHelper()->getTeamId();
        $data['teamId'] = $teamId;
        $data['id'] = $entity->getId();
        $data['customerId'] = $entity->getCustomerId();
        $data['orderDate'] = $entity->getCreatedAt();
        $orderItems = $entity->getAllItems();
        foreach ($orderItems as $orderItem){
            $data['orderItems'][] = $this->getOrderItemData($orderItem);
        }
        $address = $entity->getDefaultBillingAddress();
        $data['postcode'] = isset($address) ? $address->getPostcode() : '';
        return $data;
    }

    /**
     * @param $item Mage_Sales_Model_Order_Item
     * @return array
     */
    public function getOrderItemData($item){
        $data['gtin'] = $item->getProductId();
        $data['quantity'] = (int)$item->getQtyOrdered();
        $data['regularUnitPrice'] = (int)$item->getBasePrice();
        $data['totalOrderItemPriceAfterDiscounts'] = (float)$item->getRowTotal();
        return $data;
    }

    /**
     * @param $entity Mage_Customer_Model_Customer
     * @return mixed
     */
    public function getCustomerData($entity){
        $teamId = $this->getHelper()->getTeamId();
        $data['teamId'] = $teamId;
        $data['id'] = $entity->getId();
        $gender = $entity->getGender();
        if($gender == 1)
            $gender = 'Male';
        elseif($gender == 2)
            $gender = 'Female';
        else
            $gender = 'Other';
        $data['gender'] = $gender;
        $dob = $entity->getDob();
        $datetime = new DateTime($dob);
        $year = $datetime->format('Y');
        $data['yearOfBirth'] = (int)$year;
        $address = $entity->getDefaultBillingAddress();
        $data['postcode'] = isset($address) ? $address->getPostcode() : '';
        return $data;
    }
    /**
     * @param $entity Mage_Catalog_Model_Product
     * @return mixed
     */
    public function getProductData($entity){
        $catalogId = $this->getCitrusCatalogId();
        $teamId = $this->getHelper()->getTeamId();
        $tags = $this->getProductTags($entity->getId());
        $data['catalogId'] = $catalogId;
        $data['teamId'] = $teamId;
        $data['gtin'] = $entity->getId();
        $data['name'] = $entity->getName();
        if ($entity->getImage() != 'no_selection')
            $data['images'] = [Mage::getModel('catalog/product_media_config')->getMediaUrl($entity->getImage())];
        $data['inventory'] = (int)$entity->getQty();
        $data['price'] = (int)$entity->getPrice();
        $data['tags'] = $tags;
        $categoryIds = $entity->getCategoryIds();
        $catModel = Mage::getModel('catalog/category')->setStoreId(Mage::app()->getStore()->getId());
        if (is_array($categoryIds))
            foreach ($categoryIds as $categoryId) {
                $category = $catModel->load($categoryId);
                $data['categoryHierarchy'][] = $category->getName();
            }
        return $data;
    }
    protected function getProductTags($id){
        $results = [];
        $model=Mage::getModel('tag/tag');
        $tags= $model->getResourceCollection()
            ->addPopularity()
            ->addStatusFilter($model->getApprovedStatus())
            ->addProductFilter($id)
            ->addStoreFilter(Mage::app()->getStore()->getId())
            ->setActiveFilter();
        foreach ($tags as $tag){
            $results[] = $tag->getName();
        }
        return $results;
    }
}
