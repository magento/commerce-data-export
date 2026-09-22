# AdobeCommerce_IndexerStatusManager module

Config-driven disabling of Magento indexers, reusable across modules.

An indexer is disabled when it is both on the `manageableIndexers` allowlist (in `etc/di.xml`) and has its
flag `ac_disabled_indexers/<indexerCode>` set. `Model\Config::isDisabled()` resolves both; a flag set for an
indexer that is not on the allowlist is ignored.

A disabled indexer is removed from `indexer:info` / `indexer:reindex` and from the indexer dependency graph,
and its reindex, save-time reindex and mview update are skipped. On disable its materialized view is
unsubscribed (DB triggers + changelog dropped) and it is invalidated, so it is rebuilt whenever it is
re-enabled by any means (including removing the flag directly). Enabling re-subscribes the view and
invalidates the indexer. Optionally its index tables are truncated (see below).

## Disable an indexer

1. Add its code to the allowlist in `etc/di.xml`:

```xml
<type name="AdobeCommerce\IndexerStatusManager\Model\Config">
    <arguments>
        <argument name="manageableIndexers" xsi:type="array">
            <item name="catalog_product_price" xsi:type="string">catalog_product_price</item>
        </argument>
    </arguments>
</type>
```

2. Set its flag, either as a module default in `etc/config.xml`:

```xml
<default>
    <ac_disabled_indexers>
        <catalog_product_price>1</catalog_product_price>
    </ac_disabled_indexers>
</default>
```

or per environment in `app/etc/env.php` (the more common way):

```php
'system' => [
    'default' => [
        'ac_disabled_indexers' => [
            'catalogsearch_fulltext' => 1,
        ],
    ],
],
```

or at runtime with `bin/magento indexer:disable catalog_product_price` (followed by `cache:clean config`).
`bin/magento indexer:enable` reverses it. Both commands reject indexers that are not on the allowlist.

## Free the index table on disable (optional)

Declare the index tables an indexer owns via `indexTables` and they are truncated when it is disabled (on
`indexer:disable` and on `setup:upgrade`), freeing space and surfacing any code still reading the local
index. A table is truncated only when **every** indexer that lists it is disabled, so a table shared by
several indexers is never emptied while one of them is still enabled:

```xml
<type name="AdobeCommerce\IndexerStatusManager\Model\Config">
    <arguments>
        <argument name="indexTables" xsi:type="array">
            <item name="catalog_category_product" xsi:type="array">
                <item name="catalog_category_product_index" xsi:type="string">catalog_category_product_index</item>
            </item>
            <item name="catalog_product_category" xsi:type="array">
                <item name="catalog_category_product_index" xsi:type="string">catalog_category_product_index</item>
            </item>
        </argument>
    </arguments>
</type>
```

Only list tables written exclusively by managed indexers. Indexers with no DB index table (e.g.
`catalogsearch_fulltext`, whose data lives in OpenSearch) simply declare none.
