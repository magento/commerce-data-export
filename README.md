# Overview
This project purposed to assemble and synchronization of data that represent Magento entities with integrations,
which are sources for the data consumed by external or SaaS services and integrations.

To collect and store the denormalized data required by the SaaS services and integrations,
the export component utilizes native Magento indexers functionality under the hood.
As a result, after the component installation,
a merchant will notice new indexers in the corresponding admin menu,
where each indexer represents a data feed.

## Requirements and Dependencies
The export component is a set Magento modules and requires Magento 2.4.4 and higher.

### Contributing
Contributions are welcomed! Read the [Contributing Guide](./CONTRIBUTING.md) for more information.

### Licensing
This project is licensed under the OSL-3.0 License. See [LICENSE](./LICENSE.md) for more information.

## Export process
This extension allows to collect and export entity (called "feed") to consumer immediately after feed items have been collected.
Consumer must implement interface `Magento\DataExporter\Model\ExportFeedInterface::export` (see default implementation in [magento/saas-export](https://github.com/magento/saas-export))

Implementation of `ExportFeedInterface::export` must return status of operation `Magento\DataExporter\Model\FeedExportStatus` with response status code `Magento\DataExporter\Status\ExportStatusCode`:
- Can be HTTP status code
  -- `200` - exported successfully
  -- `4xx` - client can't process request
  -- `5xx` - server side error
- Or custom codes:
  -- `Magento\DataExporter\Status\ExportStatusCodeProvider::APPLICATION_ERROR` - something happened in side of Adobe Commerce configuration or processing
  -- `Magento\DataExporter\Status\ExportStatusCodeProvider::FAILED_ITEM_ERROR` - happens when some of the items in request were not processed successfully
These codes will be saved in the "status" field of the feed table, to keep information about item status and resend items if they have "retryable" status (everything which is not 200 or 400 is retryable): https://github.com/magento/commerce-data-export/blob/7d225940ea9714f18130ef8bbb5a32027aea94bc/DataExporter/Status/ExportStatusCodeProvider.php#L15

### Immediate export flow:
- collect entities during reindex or save action
- get entities that have to be deleted from feed (instead updating feed table with is_deleted=true)
- filter entities with identical hash (only if "export status NOT IN [Magento\DataExporter\Status\ExportStatusCodeProvider::NON_RETRYABLE_HTTP_STATUS_CODE])
- submit entities to consumer via `ExportFeedInterface::export` and return status of submitted entities
- persist to feed table state of exported entities
- save record state status according to exporting result

### Retry Logic for failed entities (only server error code):
- by cron check is any entities with status different from [200, 400] (`Magento\DataExporter\Status\ExportStatusCodeProvider::NON_RETRYABLE_HTTP_STATUS_CODE`) in the feed table
- do partial reindex

### Migration to immediate export approach:
- Replace existing feed table with the new one (with required fields and indexes for immediate feed processing) to db_schema:
   ```xml
  <table name="cde_new_immediate_feed" resource="default" engine="innodb" comment="New Immediate Feed Table">
          <column xsi:type="int" name="id" unsigned="true" nullable="false" identity="true"
                  comment="Autoincrement ID. System field"/>

        <column xsi:type="int"
                name="source_entity_id"
                padding="10"
                unsigned="true"
                nullable="false"
                comment="Entity ID from the source table"
        />
        <column
                xsi:type="varchar"
                name="feed_id"
                nullable="false"
                length="64"
                comment="Feed Item Identifier. Hash based on feed item identity fields"
        />
        <column
                xsi:type="timestamp"
                name="modified_at"
                on_update="true"
                nullable="false"
                default="CURRENT_TIMESTAMP"
                comment="Timestamp of the latest table row modification"
        />
        <column
                xsi:type="tinyint"
                name="is_deleted"
                nullable="false"
                default="0"
                comment="Feed item deletion flag"
        />
        <column
                xsi:type="smallint"
                name="status"
                nullable="false"
                default="0"
                comment="Feed item sending status"
        />
        <column
                xsi:type="mediumtext"
                name="feed_data"
                nullable="false"
                comment="Feed Data"
        />
        <column
                xsi:type="varchar"
                name="feed_hash"
                nullable="false"
                length="64"
                comment="Hash based on {feed_data}"
        />
        <column
                xsi:type="text"
                name="errors"
                nullable="true"
                comment="Errors from consumer"
        />
        <constraint xsi:type="primary" referenceId="PRIMARY">
            <column name="id"/>
        </constraint>

        <constraint referenceId="cde_new_immediate_feed_id"  xsi:type="unique">
            <column name="feed_id"/>
        </constraint>
        <!-- \Magento\DataExporter\Model\Query\DeletedEntitiesByModifiedAtQuery::getQuery -->
        <index referenceId="cde_new_immediate_feed_source_id_modified_at" indexType="btree">
            <column name="source_entity_id"/>
            <column name="modified_at"/>
        </index>
        <!-- for failed items \Magento\DataExporter\Model\Batch\Feed\Generator::getSelect -->
        <index referenceId="cde_new_immediate_feed_status" indexType="btree">
            <column name="status"/>
        </index>
    </table>
- di.xml changes (in case if virtual type is created for the `FeedIndexMetadata` type. Otherwise - add these arguments to real class):
-- Change the `exportImmediately` value to `true` for metadata configuration:
   ```xml
        <argument name="exportImmediately" xsi:type="boolean">true</argument>
- There is also an option for debugging purposes to keep saving whole data to the feed table with argument `persistExportedFeed` set to `true`
- Add `minimalPayload` argument with a minimal set of fields required by Feed Ingestion Service. Used to handle cases when feed item has been deleted.
  for example:
  ```xml
  <argument name="minimalPayload" xsi:type="array">
      <item name="sku" xsi:type="string">sku</item>
      <item name="customerGroupCode" xsi:type="string">customerGroupCode</item>
      <item name="websiteCode" xsi:type="string">websiteCode</item>
      <item name="updatedAt" xsi:type="string">updatedAt</item>
  </argument>
- Add `feedItemIdentifiers` argument: describes the mapping between generated feed identified hash in the feed table and corresponding fields in the feed item:
  for example:
  ```xml
  <argument name="feedItemIdentifiers" xsi:type="array">
      <item name="product_id" xsi:type="string">productId</item>
      <item name="website_id" xsi:type="string">websiteId</item>
      <item name="customer_group_code" xsi:type="string">customerGroupCode</item>
  </argument>
- Remove implementation of Magento\DataExporter\Model\Indexer\DataSerializer (virtual or actual) as it is not needed anymore and not used in immediate export logic.
- Make sure that your feed provider extends `\Magento\DataExporter\Export\DataProcessorInterface` and implements the `execute` method.
### Feed Index Metadata additional parameters:
- entitiesRemovable - this parameter handles feed configuration to cover cases when feed entities are not removable. Default value: `false` - feed entities can not be removed.  For example:
- `sales order` feed export's Sales Orders entities cannot be deleted and `isRemovable` metadata parameter set to false.
- `product` feed export's Products can be deleted and `isRemovable` metadata parameter *MUST* be set to true, in other case - feed records wouldn't be marked as deleted in the event of entity removal.
### Multi-thread data export mode:
The purpose of this mode is to speed up the export process by splitting the data into batches and processing them in parallel.
The performance of data export should be aligned with the limit that is defined for a client at consumer side.

Configuration of this mode is done via System configuration (config.xml) per feed indexer:
```xml
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_Store:etc/config.xsd">
    <default>
        <commerce_data_export>
            <feeds>
                <products>
                    <thread_count>1</thread_count>
                    <batch_size>100</batch_size>
                </products>
            </feeds>
        </commerce_data_export>
    </default>
</config>
```
- `thread_count` - number of threads that will be used for processing _(1 by default)_
- `batch_size` - number of items that will be processed in one batch _(100 by default)_

The multi-thread data export mode is applied for full and partial reindex.

It may be useful to change `thread_count` and `batch_size` in runtime when performing data export via CLI command. This can be done by passing the options --thread-count, --batch-size to the saas:resync command.

For example:
```bash
bin/magento indexer:reindex catalog_data_exporter_products --thread-count=5 --batch-size=400
```
### Product Feed: extend exported attributes list
Not all system product attributes are exported by default. To extend the list of exported attributes, you need to add needed attribute codes as `systemAttributes` arguments of the `Magento\CatalogDataExporter\Model\Query\ProductAttributeQuery` configuration in `di.xml` file, for example:

```xml
    <type name="Magento\CatalogDataExporter\Model\Query\ProductAttributeQuery">
        <arguments>
            <argument name="systemAttributes" xsi:type="array">
                <item name="news_from_date" xsi:type="string">news_from_date</item>
                ...
                <item name="some_system_attribute_code">some_system_attribute_code</item>
            </argument>
        </arguments>
    </type>
```