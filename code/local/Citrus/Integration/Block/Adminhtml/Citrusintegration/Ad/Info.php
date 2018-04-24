<?php
class Citrus_Integration_Block_Adminhtml_Citrusintegration_Ad_Info extends Mage_Adminhtml_Block_Template

{
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('citrus/integration/info.phtml');
    }

    public function getAdData(){
        $adData = [];
        $model = Mage::registry('citrus_ad');

        $adId = $model->getId();
        $adData['ads'] = $model;
        $discountData = $this->getDiscountModel()->load($model->getId());
        $adData['ads']->addData(['discount' => $discountData]);
        $bannerIds = $this->getBannerModel()->getIdByAdId($adId);
        $relevantIds = $this->getRelevantModel()->getIdByAdId($adId);
        $bannerData = [];
        if(is_array($bannerIds)){
            foreach ($bannerIds as $bannerId){
                $bannerData[] = $this->getBannerModel()->load($bannerId['id']);
            }
        }
        $adData['banners'] = $bannerData;
        $relevantData = [];
        if(is_array($relevantIds)){
            foreach ($relevantIds as $relevantId){
                $relevantData[] = $this->getBannerModel()->load($relevantId['id']);
            }
        }
        $adData['products'] = $relevantData;
        $object = new Varien_Object();
        return $object->addData($adData);
    }
    /**
     * @return Citrus_Integration_Model_Banner|false
     */
    protected function getBannerModel(){
        return Mage::getModel('citrusintegration/banner');
    }

    /**
     * @return false|Citrus_Integration_Model_Discount
     */
    protected function getDiscountModel(){
        return Mage::getModel('citrusintegration/discount');
    }

    /**
     * @return false|Citrus_Integration_Model_Relevant
     */
    protected function getRelevantModel(){
        return Mage::getModel('citrusintegration/relevant');
    }

}