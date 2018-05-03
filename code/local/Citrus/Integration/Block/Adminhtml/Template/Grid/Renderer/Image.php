<?php
class Citrus_Integration_Block_Adminhtml_Template_Grid_Renderer_Image extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
    public function render(Varien_Object $row)
    {
        $val = $row['imageUrl'];
        $out = "<img src=". $val ." width='97px'/>";
        return $out;
    }
}