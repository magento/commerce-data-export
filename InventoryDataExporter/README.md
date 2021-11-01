*Magento_InventoryDataExporter* module is responsible for collecting inventory data

## Stock Status

- Collects aggregated value of Stock Status described in [et_schema.xml](etc/et_schema.xml)
- Depends on Inventory indexer which is used to get `isSalable` status and `qty` in stock. 
- `qtyForSale` calculated based on Reservations API
- Stock is considered as infinite in the following cases:
  - Manage Stock disabled
  - [Backorders](https://docs.magento.com/user-guide/catalog/inventory-backorders.html?itm_source=devdocs&itm_medium=quick_search&itm_campaign=federated_search&itm_term=backorer) enabled and Out-of-Stock threshold is set to 0.
