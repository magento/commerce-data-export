# Overview

Magento_CatalogExport has two responsibilities
 - expose Magento Storefront data via REST endpoints
 - send the event to the Message Bus with notification about entity changes (see \Magento\CatalogExport\Model\Indexer\EntityIndexerCallback)

To be able to use REST endpoints `bin/magento setup:di:compile` must be executed.
During di compile phase method DTO classes required for REST endpoints will be generated.
Theses DTO classes describe entities in et_schema.xml
