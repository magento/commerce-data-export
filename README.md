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
Consumer must implement interface `Magento\DataExporter\Model\ExportFeedInterface::export` (see default implementation in [magento-commerce/saas-export](https://github.com/magento-commerce/saas-export))

Implementation of `ExportFeedInterface::export` must return status of operation `Magento\DataExporter\Model\FeedExportStatus` with the following statuses:
- `SUCCESS` - exported successfully
- `CLIENT_ERROR` - client can't process request
- `APPLICATION_ERROR` - something happened in side of Adobe Commerce configuration or processing
- `SERVER_ERROR` - happens when server can't process request


### Immediate export flow:
- collect entities during reindex or save action
- get entities that have to be deleted from feed (instead updating feed table with is_deleted=true)
- filter entities with identical hash (only if "export status in [SUCCESS, APPLICATION_ERROR])
- submit entities to consumer via `ExportFeedInterface::export` and return status of submitted entities
- persist to feed table state of exported entities
- save record state status according to exporting result

### Retry Logic for failed entities (only server error code):
- by cron check is any entities with status `SERVER_ERROR` or `APPLICATION_ERROR` in the feed table
- select entities with filter by modified_at && status = `SERVER_ERROR`, `APPLICATION_ERROR`
- partial reindex

### Migration to immediate export approach:
- Add new columns (required for immediate feed processing) to db_schema of the feed table:
   ```xml
        <column
            xsi:type="tinyint"
            name="status"
            nullable="false"
            default="0"
            comment="Status"
        />
        <column
            xsi:type="varchar"
            name="feed_hash"
            nullable="false"
            length="64"
            comment="Feed Hash"
        />
        <column
            xsi:type="text"
            name="errors"
            nullable="true"
            comment="Errors"
        />
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
- Add `feedIdentifierMapping` argument: describes the mapping between primary key columns in the feed table and corresponding fields in the feed item:
  for example:
  ```xml
  <argument name="feedIdentifierMapping" xsi:type="array">
      <item name="product_id" xsi:type="string">productId</item>
      <item name="website_id" xsi:type="string">websiteId</item>
      <item name="customer_group_code" xsi:type="string">customerGroupCode</item>
  </argument>