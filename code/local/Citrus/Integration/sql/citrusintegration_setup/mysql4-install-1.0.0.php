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

DROP TABLE IF EXISTS {$this->getTable('citrusintegration/queue')};
CREATE TABLE {$this->getTable('citrusintegration/queue')} (
  `id` int(11) unsigned NOT NULL auto_increment,
  `entity_id` VARCHAR(255) NOT NULL,
  `type` VARCHAR(255) NULL,
  `enqueue_time` datetime NULL,
  PRIMARY KEY (`id`)    
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('citrusintegration/log')};
CREATE TABLE {$this->getTable('citrusintegration/log')} (
  `id` int(11) unsigned NOT NULL auto_increment,
  `entity_id` VARCHAR(255) NOT NULL,
  `type` VARCHAR(255) NULL,
  `dequeue_time` datetime NULL,
  `status` VARCHAR(255) NULL,
  `citrus_id` VARCHAR(255) NULL,
  `message` VARCHAR(255) NULL,
  PRIMARY KEY (`id`)    
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();