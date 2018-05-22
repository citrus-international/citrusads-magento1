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
DROP TABLE IF EXISTS {$this->getTable('citrusintegration/slotid')};
CREATE TABLE {$this->getTable('citrusintegration/slotid')} (
  `id` int(11) unsigned NOT NULL auto_increment,
  `page_type` VARCHAR(255) NOT NULL,
  `page_id` VARCHAR(255) NOT NULL,
  `slot_id` VARCHAR(255) NULL,
  PRIMARY KEY (`id`)    
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
DROP TABLE IF EXISTS {$this->getTable('citrusintegration/catalog')};
CREATE TABLE {$this->getTable('citrusintegration/catalog')} (
  `id` int(11) unsigned NOT NULL auto_increment,
  `catalog_id` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NULL,
  `host` VARCHAR(255) NULL,
  `teamId` VARCHAR(255) NULL,
  PRIMARY KEY (`id`)    
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();