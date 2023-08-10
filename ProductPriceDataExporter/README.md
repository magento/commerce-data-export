# Overview
Module is created to support feeds which carrying information with product prices data.

The "price" field will be eliminated from the products feed. Instead of that - use the "Product Prices" feed with stored products pricing data.
Feed fields overview:
```json{
"websiteId": website id of the pricing value (as price can be unique per website)

"productId": product ID

"sku": product SKU

"type": type of the product (SIMPLE, CONFIGURABLE, GROUPED, BUNDLE_FIXED, BUNDLE_DYNAMMIC, etc)

"customerGroupCode": customer group code which price is relevant for (sha1 representation of customer groupd id. See \Magento\ProductPriceDataExporter\Model\Provider\ProductPrice::buildCustomerGroupCode)

"websiteCode": website code of the price relevant for

"regular": regular price of the product. For some products it's could be null (like DYNAMIC and CONFIGURABLE)

"discounts": array of additional discounts for the product. Each discount has two fields "code" and "price". Currently we are using two codes - "special_price" (for any special price discounts) and "group" (for tier-price discounts)

"deleted": is price deleted (0 or 1)

"updatedAt": time and day when price was updated. Service specific field

"modifiedAt": time and day when price was modified. Service specific field
}
```