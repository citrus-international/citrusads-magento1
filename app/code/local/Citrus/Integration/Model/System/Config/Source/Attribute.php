<?php

class Citrus_Integration_Model_System_Config_Source_Attribute
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray($isMultiselect)
    {
        $entityType = Mage::getModel('eav/config')->getEntityType(Mage_Catalog_Model_Product::ENTITY);

        /** @var Mage_Eav_Model_Resource_Entity_Attribute_Collection $attributes */
        $attributes = Mage::getModel('eav/entity_attribute')->getCollection()
            ->setEntityTypeFilter($entityType);
        $options = [];
        foreach ($attributes as $attribute) { /** @var Mage_Eav_Model_Entity_Attribute $attribute */
            if($attribute->getIsVisibleOnFront()) {
                $options[] = [
                    'value' => $attribute->getAttributeCode(),
                    'label' => Mage::helper('adminhtml')->__($attribute->getFrontendLabel())
                ];
            }
        }
        return $options;
    }
}
