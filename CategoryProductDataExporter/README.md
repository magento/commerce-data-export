# AdobeCommerce_CategoryProductDataExporter module

The AdobeCommerce_CategoryProductDataExporter module supplies product-to-category relationship data for the
Catalog Data Exporter product feed by reading directly from the `catalog_category_product` write-side
table instead of the `catalog_category_product_index` index.

It overrides `Magento\CatalogDataExporter\Model\Provider\Product\CategoryData` and resolves
anchor-category inheritance in PHP, so category membership (direct assignments plus anchor rollup) is
still exported correctly when the `catalog_category_product` and `catalog_product_category` indexers are
disabled by the AdobeCommerce_IndexerStatusManager module.

## Release notes

Initial release.
