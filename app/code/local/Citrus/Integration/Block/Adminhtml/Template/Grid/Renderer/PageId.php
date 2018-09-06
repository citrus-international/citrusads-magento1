<?php
class Citrus_Integration_Block_Adminhtml_Template_Grid_Renderer_PageId extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $page_type = $row['page_type'];
        $pageId = $row['page_id'];
        $page_ids = json_decode($pageId);

        if($page_type == 0){
            $out = "All Pages";
        }
        elseif($page_type == 3){
            $out = "Search Page";
        }
        elseif($page_type == 1){
            $result = array();
            foreach ($page_ids as $page_id){
                $result[] = Mage::getModel('catalog/category')->load($page_id)->getName();
            }

            $out = json_encode($result);
            $out = str_replace(str_split('\\/:*?"<>|[]'), ' ', $out);
        }
        else{
            $result = array();
            foreach ($page_ids as $page_id){
                $result[] = Mage::getModel(Mage_Cms_Model_Page::class)->load($page_id)->getTitle();
            }

            $out = json_encode($result);
            $out = str_replace(str_split('\\/:*?"<>|[]'), ' ', $out);
        }

        return $out;
    }
}