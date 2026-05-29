# MDEE Log Codes Reference

Log code format: `CDE<group_id>-<log_id>` (e.g., `CDE01-02`)

Sources: `commerce-data-export`, `commerce-data-export-ee`, `saas-export`

Codes are assigned only to `error`, `warning` and `critical` level log messages. `info`, `notice`, and `debug` level messages are excluded.

---

## Group 01 - Data Collection Phase

Log codes related to errors or warnings that occur while collecting data from source entities, typically within data providers.
- Affected entities might be processed with partial data or skipped entirely if an error occurs. See the log message for details.
- Warnings can indicate incorrect integration with the Data Export extension by third-party modules; however, sync operations typically continue.

| Log Code | Level   | Message                                                                                                                            | File Path                                                                                                |
|----------|---------|------------------------------------------------------------------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------|
| CDE01-01 | error   | `CDE01-01 Failed to add stock info to "ac_inventory" attribute for ids "{ids}". Error: {exception_message}`                        | `commerce-data-export/ExtraProductAttributes/Provider/AdvancedInventoryProvider.php`                  |
| CDE01-02 | warning | `CDE01-02 Field "{field}" is missing in row {row_data}`                                                                            | `commerce-data-export/ExtraProductAttributes/Provider/AdvancedInventoryProvider.php`                 |
| CDE01-03 | warning | `CDE01-03 Invalid field "{field}" requested from inventory config {config_data}`                                                   | `commerce-data-export/ExtraProductAttributes/Provider/AdvancedInventoryProvider.php`                 |
| CDE01-04 | error   | `CDE01-04 Was not able to add data to "ac_attribute_set" attribute for ids "{ids}". Error: {exception_message}`                    | `commerce-data-export/ExtraProductAttributes/Provider/AttributeSetProvider.php`                       |
| CDE01-05 | error   | `CDE01-05 Unable to sync feed "{feed}" for ids "{ids}". Affected data provider: "{provider}". Error: {exception_message}`          | `commerce-data-export/DataExporter/Export/Processor.php`                                              |
| CDE01-06 | error   | `CDE01-06 Unable to sync feed "{feed}" for ids "{ids}". Error: {exception_message}`                                                | `commerce-data-export/DataExporter/Export/Processor.php`                                             |
| CDE01-07 | error   | `CDE01-07 Source entity id is null. Item sync was skip for feed "{feed}". field: "{field}", item: {item}`                          | `commerce-data-export/DataExporter/Model/Indexer/DataSerializer.php`                                 |
| CDE01-08 | error   | `CDE01-08 Cannot collect "inStock" for products "{product_ids}": no sales channel data for stores "{store_view_codes}"`            | `commerce-data-export/CatalogInventoryDataExporter/Model/Query/InventoryData.php`                    |
| CDE01-09 | error   | `CDE01-09 Cannot get status attribute. Product variants ignore stock status. Error: {exception_message}`                           | `commerce-data-export/ConfigurableProductDataExporter/Model/Provider/Product/Options.php`            |
| CDE01-10 | error   | `CDE01-10 Unable to retrieve gift card product options for products "{values}". Error: {exception_message}`                        | `commerce-data-export-ee/GiftCardProductDataExporter/Model/Provider/Product/Options.php`             |
| CDE01-11 | error   | `CDE01-11 Unable to retrieve gift card shopper input options for products "{values}". Error: {exception_message}`                  | `commerce-data-export-ee/GiftCardProductDataExporter/Model/Provider/Product/ShopperInputOptions.php` |
| CDE01-12 | warning | `CDE01-12 Catalog Permissions: Global Configuration path was not found for path {path}. {config_dump}`                             | `commerce-data-export-ee/CategoryPermissionDataExporter/Model/Provider/ConfigurationProvider.php`    |
| CDE01-13 | error   | `CDE01-13 Catalog Permissions: wrong state in global config. item: {item}, config: {config}`                                       | `commerce-data-export-ee/CategoryPermissionDataExporter/Model/Provider/ConfigurationProvider.php`    |
| CDE01-14 | error   | `CDE01-14 Failed to assign UUIDs for type: {type}, ids: {ids}`                                                                     | `commerce-data-export/DataExporter/Uuid/UuidManager.php`                                              |
| CDE01-15 | error   | `CDE01-15 Failed to assign UUIDs for type: {type}, ids: {ids}. duplicates: {duplicates}`                                           | `commerce-data-export/DataExporter/Uuid/UuidManager.php`                                             |
| CDE01-16 | error   | `CDE01-16 "{feed_name}" feed sync error: cannot build identifier for "{field}". Item skipped: {item}`                              | `commerce-data-export/DataExporter/Model/FeedHashBuilder.php`                                         |
| CDE01-17 | warning | `CDE01-17 Failed to create attribute "{attribute_code}". Will be retried on next sync. Error: {message}`                           | `commerce-data-export/CatalogDataExporter/Service/SystemAttributeRegistrar.php`                      |
| CDE01-18 | warning | `CDE01-18 Error on getting datetime for catalog price rule fetch. Using system time. website: "{website_id}", store: "{store_id}"` | `commerce-data-export/ProductPriceDataExporter/Model/Query/DateWebsiteProvider.php`                   |
| CDE01-19 | warning | `CDE01-19 GiftCard {sku} does not have shopper input options`                                                                      | `commerce-data-export-ee/GiftCardProductDataExporter/Plugin/GiftCardAsAttribute.php`                 |
| CDE01-20 | warning | `CDE01-20 GiftCard {sku} doesn't have valid options: {options}`                                                                    | `commerce-data-export-ee/GiftCardProductDataExporter/Plugin/GiftCardAsAttribute.php`                 |
| CDE01-21 | error   | `CDE01-21 Unable to resolve url_path for category {id} with path "{path}", url_key "{urk_key}", store "{store}"`                   | `commerce-data-export/CatalogDataExporter/Model/Provider/Categories.php`                             |
| CDE01-22 | error   | `CDE01-22 Unable to resolve url_path for category{id} with path "{path}" for store view "{store}"`                                 | `commerce-data-export/CatalogDataExporter/Model/Provider/Product/CategoryData.php`                    |

---

## Group 02 - Sending Data to SaaS Phase

Log codes related to errors or warnings that occur while submitting feed data to SaaS endpoints.
- Errors typically indicate failures during HTTP requests, response handling, or data validation that prevent data from being accepted.
- Warnings usually indicate transient conditions (such as rate limiting or server errors) where requests are retried automatically.

| Log Code  | Level   | Message | File Path |
|-----------|---------|---------|-----------|
| CDE02-01 | error   | `CDE02-01 Application error on sending data to SaaS for feed "{feed_name}". Error: {error_message}` | `saas-export/SaaSCommon/Model/Http/Command/SubmitFeed.php` |
| CDE02-02 | error   | `CDE02-02 Unexpected error on sending data to SaaS for feed "{feed_name}". Error: {error_message}` | `saas-export/SaaSCommon/Model/Http/Command/SubmitFeed.php` |
| CDE02-03 | warning | `CDE02-03 Cannot parse the API response because the request was not successful.` | `saas-export/SaaSCommon/Model/Http/ResponseParser.php` |
| CDE02-04 | error   | `CDE02-04 Cannot obtain feed metadata for feed name "{feed_name}". Sync terminated. Error: {error_message}` | `saas-export/SaaSCommon/Cron/SubmitFeed.php` |
| CDE02-05 | error   | `CDE02-05 Failed to submit feed batch for feed {feed_name}. Error: {error_message}` | `saas-export/SaaSCommon/Cron/SubmitFeed.php` |
| CDE02-06 | error   | `CDE02-06 Failed to retry feed items submission for feed {feed_name}. Error: {error_message}` | `saas-export/SaaSCommon/Cron/SubmitFeed.php` |
| CDE02-07 | warning | `CDE02-07 Feed "{feed_name}" sync error: too many requests (HTTP 429). Request will be retried.` | `saas-export/SaaSCommon/Model/Http/Command/SubmitFeed.php` |
| CDE02-08 | warning | `CDE02-08 Feed "{feed_name}" sync error: Server error (HTTP {http_status_code}). Request will be retried.` | `saas-export/SaaSCommon/Model/Http/Command/SubmitFeed.php` |
| CDE02-09 | error   | `CDE02-09 Feed "{feed_name}" sync error: data validation failed. Check logs. Request will not be retried.` | `saas-export/SaaSCommon/Model/Http/Command/SubmitFeed.php` |
| CDE02-10 | warning | `CDE02-10 Feed "{feed_name}" sync error: Client error (HTTP {http_status_code}). Request will be retried.` | `saas-export/SaaSCommon/Model/Http/Command/SubmitFeed.php` |
| CDE02-11 | warning | `CDE02-11 Feed "{feed_name}" sync error: application-level error. Request will be retried.` | `saas-export/SaaSCommon/Model/Http/Command/SubmitFeed.php` |
| CDE02-12 | error   | `CDE02-12 Feed "{feed_name}" sync error API request was not successful (status code: {status_code}).` | `saas-export/SaaSCommon/Model/Http/Command/SubmitFeed.php` |
| CDE02-13 | warning | `CDE02-13 The zlib-ext is not loaded. Request body can't be compressed and will proceed with regular json` | `saas-export/SaaSCommon/Model/Http/Converter/Factory.php` |

---

## Group 03 - Scheduling Sync on Entity Update

Log codes related to errors or warnings that occur when scheduling or triggering synchronization in response to entity changes.
- Errors can prevent incremental synchronization from being scheduled and often require a full or partial resync to recover.
- Warnings indicate that a sync operation was skipped or deferred due to unsupported input, missing identifiers, or configuration issues.

| Log Code | Level    | Message                                                                                                                          | File Path                                                                                                             |
|----------|----------|----------------------------------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------|
| CDE03-01 | error    | `CDE03-01 Cannot schedule resync for feeds`                                                                                      | `commerce-data-export/DataExporter/Service/FeedItemsResyncScheduler.php`                                              |
| CDE03-02 | warning  | `CDE03-02 Skipping product feed update scheduling. Category path "{category_path}" is wrongly formatted`                         | `commerce-data-export/CatalogDataExporter/Plugin/Category/ScheduleProductUpdateOnCategoryChange.php`                  |
| CDE03-03 | error    | `CDE03-03 Categories sync error on category "{category_id}" save. Run resync. Error: {error_message}`                            | `commerce-data-export/CatalogDataExporter/Plugin/Category/ReindexCategoryFeedOnSave.php`                              |
| CDE03-04 | error    | `CDE03-04 Product sync scheduling error on url key change ({old_url_key} -> {new_url_key}). Run resync. Error: {error_message}`  | `commerce-data-export/CatalogDataExporter/Plugin/Category/ResyncProductsOnCategoryChange.php`                         |
| CDE03-05 | error    | `CDE03-05 Product sync scheduling error on category path change ({old_path} -> {new_path}). Run resync. Error: {error_message}`  | `commerce-data-export/CatalogDataExporter/Plugin/Category/ResyncProductsOnCategoryChange.php`                         |
| CDE03-06 | error    | `CDE03-06 Product sync scheduling error on attribute "{attribute_code}" deletion. Run full resync. Error: {error_message}`       | `commerce-data-export/CatalogDataExporter/Plugin/Eav/Attribute/ProductAttributeDelete.php`                            |
| CDE03-07 | warning  | `CDE03-07 Product sync scheduling error on inventory source save for SKUs: {product_skus}. Error: {error_message}`               | `commerce-data-export/CatalogInventoryDataExporter/Model/Plugin/ScheduleProductUpdate.php`                            |
| CDE03-08 | error    | `CDE03-08 Product variants sync scheduling error on product "{sku_or_id}" save. Run resync. Error: {error_message}`              | `commerce-data-export/ProductVariantDataExporter/Plugin/ReindexVariantsAfterSave.php`                                 |
| CDE03-09 | warning  | `CDE03-09 The '{feed_name}' feed does not support partial resync by IDs, or an unsupported identifier type was specified.`       | `saas-export/SaaSCommon/Model/ResyncManager.php`                                                                      |
| CDE03-10 | warning  | `CDE03-10 There are no {id_field}s found to reindex for provided identifiers list: {identifiers}`                                | `saas-export/SaaSCommon/Model/ResyncManager.php`                                                                      |
| CDE03-11 | error    | `CDE03-11 Categories Permissions feed sync scheduling error on category "{category_id_and_name}" delete. Error: {error_message}` | `commerce-data-export-ee/CategoryPermissionDataExporter/Plugin/MarkEntityAsDeletedOnCategoryRemove.php`               |
| CDE03-12 | warning  | `CDE03-12 Product Overrides sync failed. Marked indexer as invalid. Error: {error_message}`                                      | `commerce-data-export-ee/ProductOverrideDataExporter/Plugin/Indexer/Category/TriggerFullResync.php`                   |
| CDE03-13 | error    | `CDE03-13 Cannot invalidate indexers "{indexer_ids}" for event "{event_name}". Error: {error_message}`                           | `commerce-data-export/DataExporter/Service/IndexInvalidationManager.php`                                              |
| CDE03-14 | error    | `CDE03-14 Failed to read config values. Indexer invalidation skipped. Error: {error_message}`                                    | `commerce-data-export/CatalogDataExporter/Plugin/Index/InvalidateOnConfigChange.php`                                  |
| CDE03-15 | error    | `CDE03-15 Categories Permissions feed sync scheduling error on config save: {error_message}`                                     | `commerce-data-export-ee/CategoryPermissionDataExporter/Plugin/InvalidateOnConfigChange.php`                          |
| CDE03-16 | error    | `CDE03-16 Failed to reindex category permissions global configuration after full reindex: {error_message}`                       | `commerce-data-export-ee/CategoryPermissionDataExporter/Plugin/GlobalConfigurationReindex.php`                        |
| CDE03-17 | critical | `CDE03-17 Failed to recreate product override view subscriptions on customer group save: {error_message}`                        | `commerce-data-export-ee/ProductOverrideDataExporter/Plugin/CreateViewAfterChangeCustomerGroup.php`                   |
| CDE03-18 | critical | `CDE03-18 Failed to recreate product override view subscriptions on customer group delete: {error_message}`                      | `commerce-data-export-ee/ProductOverrideDataExporter/Plugin/CreateViewAfterChangeCustomerGroup.php`                   |
| CDE03-19 | error    | `CDE03-19 Failed to remove product override view subscriptions during table maintenance: {error_message}`                        | `commerce-data-export-ee/ProductOverrideDataExporter/Plugin/CreateViewAfterTableMaintenance.php`                      |
| CDE03-20 | error    | `CDE03-20 Failed to recreate product override view subscriptions after table maintenance: {error_message}`                       | `commerce-data-export-ee/ProductOverrideDataExporter/Plugin/CreateViewAfterTableMaintenance.php`                      |
| CDE03-21 | error    | `CDE03-21 Product sync scheduling error on attribute {%s} option change. Run resync. Error: %s`                                  | `commerce-data-export/CatalogDataExporter/Plugin/Eav/ResyncProductsOnAttributeOptionLabelChange.php`                  |
| CDE03-22 | warning  | `CDE03-22 StagedCategoryUrlKeyChangeDetector: no active row at version {version_id} for entity_id(s) [{entity_ids}]; skipping.`  | `commerce-data-export-ee/ProductOverrideDataExporter/Model/Category/StagedCategoryUrlKeyChangeDetector.php`           |
| CDE03-23 | error    | `CDE03-23 StagedCategoryUrlKeyChangeDetector: catalog_category url_key attribute not found; failing open.`                       | `commerce-data-export-ee/ProductOverrideDataExporter/Model/Category/StagedCategoryUrlKeyChangeDetector.php`           |
| CDE03-24 | error    | `CDE03-24 InvalidateProductFeedOnCategoryUrlKeyChange: scheduler failed for path "{path}": {error_message}`                      | `commerce-data-export-ee/ProductOverrideDataExporter/Plugin/Category/InvalidateProductFeedOnCategoryUrlKeyChange.php` |
| CDE03-25 | error    | `CDE03-25 InvalidateProductFeedOnCategoryUrlKeyChange: gate query failed: {error_message}`                                       | `commerce-data-export-ee/ProductOverrideDataExporter/Plugin/Category/InvalidateProductFeedOnCategoryUrlKeyChange.php` |
| CDE03-26 | error    | `CDE03-26 ExpandStagedCategoryUrlKeyReindex: unable to expand staged url_key category reindex scope: {error_message}`            | `commerce-data-export-ee/ProductOverrideDataExporter/Plugin/Category/ExpandStagedCategoryUrlKeyReindex.php`           |
| CDE03-27 | error    | `CDE03-27 Failed to invalidate indexers after config "{config_section}" change. Error: {error_message}`                          | `commerce-data-export/CatalogDataExporter/Plugin/Index/InvalidateOnConfigChange.php`                                  |

---

## Group 04 - General Errors Related to Indexation or Configuration

Log codes related to errors during the indexation process or due to misconfiguration.

| Log Code  | Level   | Message | File Path |
|-----------|---------|---------|-----------|
| CDE04-02 | error   | `CDE04-02 Cannot set indexer to Update On Schedule mode for indexer {indexer_id}. Error: {message}` | `commerce-data-export/DataExporter/Setup/Recurring.php` |
| CDE04-03 | warning | `CDE04-03 Partial sync failed for changelog "{changelog_name}". Should be retried. Error: {message}` | `commerce-data-export/DataExporter/Plugin/MviewUpdatePlugin.php` |
| CDE04-04 | error   | `CDE04-04 Feed metadata does not contain indexer name. Check di.xml config` | `commerce-data-export/DataExporter/Service/FeedIndexerProvider.php` |
| CDE04-05 | error   | `CDE04-05 Cannot load feed indexer for feed` | `commerce-data-export/DataExporter/Service/FeedIndexerProvider.php` |
| CDE04-06 | error   | `CDE04-06 Failed to reset MView triggers for "{affected_views}" on index table switch. Run reindex. Error: {message}` | `commerce-data-export/CatalogDataExporter/Plugin/DDLTrigger/ResetTriggers.php` |
| CDE04-07 | error   | `CDE04-07 Error on partial resync for feed "{feed_name}". Error: {message}` | `commerce-data-export/DataExporter/Model/Batch/FeedChangeLog/Generator.php` |
| CDE04-08 | error   | `CDE04-08 Error retrying failed items sync for feed "{feed_name}". Error: {message}` | `commerce-data-export/DataExporter/Model/Batch/Feed/Generator.php` |
| CDE04-09 | error   | `CDE04-09 Error on full resync for feed "{feed_name}". Error: {message}` | `commerce-data-export/DataExporter/Model/Batch/FeedSource/Generator.php` |
| CDE04-10 | error   | `CDE04-10 Error during full sync. Message: "{message}". The following IDs were skipped: [{ids}]` | `commerce-data-export/DataExporter/Model/Indexer/FeedIndexProcessorCreateUpdate.php` |
| CDE04-11 | warning | `CDE04-11 Feed "{feed_name}" sync failed. Resync will be run on next cron run. Error: {message}` | `commerce-data-export/DataExporter/Model/Indexer/FeedIndexProcessorCreateUpdate.php` |
| CDE04-12 | warning | `CDE04-12 Partial sync failed for feed "{feed_name}". Retry has been scheduled. Error: {message}` | `commerce-data-export/DataExporter/Model/Indexer/ViewMaterializer.php` |
| CDE04-13 | error   | `CDE04-13 Sync completed, but failed to persist status to feed table for "{feed_name}" feed. Error: {message}` | `commerce-data-export/DataExporter/Model/Indexer/FeedUpdater.php` |
| CDE04-14 | error   | `CDE04-14 Cannot delete feed items for feed "{feed_name}" for ids: "{ids}". Error: {message}` | `commerce-data-export/DataExporter/Model/Indexer/FeedIndexProcessorCreateUpdateDelete.php` |
| CDE04-15 | warning | `CDE04-15 Failed to serialize metadata after sync. Error: {message}` | `commerce-data-export/DataExporter/Model/FeedExportStatus.php` |
| CDE04-16 | warning | `CDE04-16 Batch table insert query "{query}" returned unexpected result. Expected: {expected_class}, Actual: {actual_type}` | `commerce-data-export/DataExporter/Model/Batch/BatchTable.php` |
| CDE04-17 | warning | `CDE04-17 Failed to check indexer type when setting schedule mode: {message}` | `commerce-data-export/DataExporter/Plugin/ForceExporterIndexerModeOnSchedule.php` |
| CDE04-18 | warning | `CDE04-18 Fixture generator: failed to filter indexer changelog tables from fixture SQL: {message}` | `commerce-data-export/CatalogDataExporter/Plugin/FilterChangeLogTable.php` |
| CDE04-19 | warning | `CDE04-19 The identifier for a feed item is empty. Sync is skipped for the entity.` | `commerce-data-export/DataExporter/Model/Indexer/FeedIndexProcessorCreateUpdate.php` |
| CDE04-20 | warning | `CDE04-20 Unexpected call: feed "{feed_name}" is not locked, trace: {stack_trace}` | `commerce-data-export/DataExporter/Model/Indexer/FeedIndexer.php` |
| CDE04-21 | error   | `CDE04-21 Failed to clean up deleted feed items for feed "{feed_name}". Error: {error_message}` | `commerce-data-export/DataExporter/Cron/CleanupDeletedFeedItems.php` |
