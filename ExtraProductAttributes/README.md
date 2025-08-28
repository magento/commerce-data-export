# Overview

Module `ExtraProductAttributes` enriches the product feed with additional attributes that are not part of the standard product schema.
Corresponding attributes are registered as system attributes dynamically.

## Installation

Add `adobe-commerce/module-extra-product-attributes` module using composer:

```
composer require adobe-commerce/module-extra-product-attributes
```

After redeployment, the Adobe Commerce instance will automatically enrich products with additional attributes during product synchronization.
If you want to force synchronization, you can run the following commands:

```
bin/magento saas:resync --feed=products
bin/magento saas:resync --feed=productAttributes
```

## Exported attributes

### Tax Class
Enrich products with tax class through the attribute `ac_tax_class`.
Example:
```json
{
  "attributes": [
    {
      "code": "ac_tax_class",
      "values": [
        "Taxable Goods"
      ]
    }
  ]
}
```

## Attribute Set
Enrich products with attribute set name through the attribute `ac_attribute_set`.
Example:
```json
{
  "attributes": [
    {
      "code": "ac_attribute_set",
      "values": [
        "Default"
      ]
    }
  ]
}
```

## Inventory data
Enrich products with attribute set name through the attribute `ac_inventory`.
Attribute `ac_inventory` is a serialized JSON with inventory-related fields:

- `manageStock`: boolean, whether stock management is enabled
- `cartMinQty`: float, minimum quantity allowed in cart
- `cartMaxQty`: float, maximum quantity allowed in cart
- `backorders`: string, backorder policy, one of:
    - `no`: No backorders allowed
    - `allow`: Allow Qty Below 0
    - `allow_notify`: Allow Qty Below 0 and Notify Customer
- `enableQtyIncrements`: boolean, whether quantity increments are enabled
- `qtyIncrements`: float, quantity increment value

```json
{
  "attributes": [
    {
      "code": "ac_inventory",
      "values": [
        "{\"manageStock\":true,\"cartMinQty\":2,\"cartMaxQty\":42,\"backorders\":\"no\",\"enableQtyIncrements\":false,\"qtyIncrements\":2}"
      ]
    }
  ]
}
```