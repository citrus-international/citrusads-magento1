<?php
$installer = $this;
$installer->startSetup();
$installer->run("
DROP TABLE IF EXISTS {$this->getTable('citrusintegration/catalog')};
CREATE TABLE {$this->getTable('citrusintegration/catalog')} (
  `id` int(11) unsigned NOT NULL auto_increment,
  `catalog_id` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NULL,
  PRIMARY KEY (`id`)    
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();