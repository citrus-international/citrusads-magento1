<?php
class Citrus_Integration_Model_Observer
{

    public function createCatalog($observer)
    {
        $enable = Mage::getStoreConfig('citrus/citrus_group/enable', Mage::app()->getStore());
        $teamId = Mage::getStoreConfig('citrus/citrus_group/team_id', Mage::app()->getStore());
        $apiKey = Mage::getStoreConfig('citrus/citrus_group/api_key', Mage::app()->getStore());
        $catalogName = Mage::getStoreConfig('citrus/citrus_group/catalog_name', Mage::app()->getStore());
        if ($enable) {
            /** @var Citrus_Integration_Helper_Api $api */
            $api = Mage::helper('citrusintegration/api');
            $params = ['teamId' => $teamId];
            $reponse = $api->setCatalog($params,$apiKey, $catalogName);
            if($reponse['success']){
                $data = json_decode($reponse['message'], true);
                $catalogData['catalog_id'] = $data['catalogs'][0]['id'];
                $catalogData['name'] = $data['catalogs'][0]['name'];
                /** @var Citrus_Integration_Model_Catalog $model */
                $model = Mage::getModel('citrusintegration/catalog')->setData($catalogData);
                try{
                    $model->save();
                    self::createRootCategory(0, $catalogName);
                }catch (Exception $e){

                }

            }

        }
    }
    public function createRootCategory($storeId, $name){
        /** @var Mage_Catalog_Model_Category $category */
        $category = Mage::getModel('catalog/category');
        $category->setStoreId($storeId);
        $category->setName($name);
        $category->setIsActive(1);
        $category->setDisplayMode(Mage_Catalog_Model_Category::DM_PRODUCT);
        $parentId = Mage_Catalog_Model_Category::TREE_ROOT_ID;
        $parentCategory = Mage::getModel('catalog/category')->load($parentId);
        $category->setPath($parentCategory->getPath());
        $category->save();
    }
}
?>