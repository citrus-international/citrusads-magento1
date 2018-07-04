<?php
$installer = $this;
$installer->startSetup();
$installer->run(
    "
DROP TABLE IF EXISTS {$this->getTable('citrusintegration/slotid')};
CREATE TABLE {$this->getTable('citrusintegration/slotid')} (
  `id` int(11) unsigned NOT NULL auto_increment,
  `page_type` VARCHAR(255) NOT NULL,
  `page_id` VARCHAR(255) NOT NULL,
  `slot_id` VARCHAR(255) NULL,
  PRIMARY KEY (`id`)    
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
"
);

$installer->endSetup();