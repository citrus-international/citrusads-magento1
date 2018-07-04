<?php
class Citrus_Integration_Block_Adminhtml_Template_Grid_Renderer_PageType extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $val = $row['page_type'];
        switch ($val) {
            case '1':
                $out = 'Category Pages';
                break;
            case '2':
                $out = 'CMS Pages';
                break;
            case '3':
                $out = 'Search Pages';
                break;
            default:
                $out = 'All Pages';
                break;
        }

        return $out;
    }
}