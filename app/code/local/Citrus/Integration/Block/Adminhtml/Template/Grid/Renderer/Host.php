<?php
class Citrus_Integration_Block_Adminhtml_Template_Grid_Renderer_Host extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $val = $row['host'];
        switch ($val) {
            case 'https://us-integration.citrusad.com/v1/':
                $out = 'USA';
                break;
            case 'https://au-integration.citrusad.com/v1/':
                $out = 'AUS';
                break;
            default:
                $out = 'Staging';
                break;
        }

        return $out;
    }
}