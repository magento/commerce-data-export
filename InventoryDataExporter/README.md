*Magento_InventoryDataExporter* module is responsible for collecting inventory data

## Stock Status

- Collects aggregated value of Stock Status described in [et_schema.xml](etc/et_schema.xml)
- Depends on Inventory indexer which is used to get `isSalable` status and `qty` in stock. 
- `qtyForSale` calculated based on Reservations API
- Stock is considered as infinite in the following cases:
  - Manage Stock disabled
  - [Backorders](https://experienceleague.adobe.com/docs/commerce-admin/inventory/configuration/backorders.html) enabled and Out-of-Stock threshold is set to 0.
- To calculate salable quantity Reservations API is used. 
  - salable qty is calculated only for indexer in "on schedule" mode to prevent frequent reindexation during place order
