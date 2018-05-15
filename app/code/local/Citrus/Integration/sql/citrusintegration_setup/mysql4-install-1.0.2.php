<?php
$installer = $this;
$installer->startSetup();
$installer->run("

DROP TABLE IF EXISTS {$this->getTable('citrusintegration/ad')};
CREATE TABLE {$this->getTable('citrusintegration/ad')} (
  `id` int(11) unsigned NOT NULL auto_increment,
  `citrus_id` VARCHAR(255) NOT NULL,
  `gtin` VARCHAR(255) NULL,
  `discount_id` int(11) NULL,
  `pageType` VARCHAR(255) NULL,
  `expiry` VARCHAR(255) NULL,
  `host` VARCHAR(255) NULL,
  PRIMARY KEY (`id`)    
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();