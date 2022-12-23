## Release notes

### *ScopesDataExporter* module

The scoping service is a new functionality created to notify consumers when any element of a scope changes, 
whether it is a website or a store view. 

This Magento data exporter extension is part of such service and it's meant to track changes in 
Adobe Commerce side.

Currently, all the scopes defined in the monolith such as Websites, Stores, Store Views and Customer Groups 
are necessary information for most of the SaaS applications. We envision this service to serve that need 
for all SaaS applications that are in production now, and those that are planned for the future. 

The overall goals are the following :

* Make it easy for any SaaS application to obtain the latest snapshot of scopes defined in the commerce monolith.
* Decouple SaaS applications from how scopes are defined currently. For example, if we choose to move away 
  from the current hierarchy of scopes in the monolith, the scoping service will act as an abstraction layer 
  between the monolith and SaaS applications thus ensuring easy scalability in the future.

### Scopes Data Export Details

#### Indexer Details

Two Indexers are created:

1. *scopes_website_data_exporter* for tracking changes (create/update/delete) in the following tables: 
   * table "store" -> entity_column="website_id"
   * table "store_group" -> entity_column="website_id"
   * table "store_website" -> entity_column="website_id"

2. *scopes_customergroup_data_exporter* to track changes (create/update/delete) in the following table:
   * table "customer_group" -> entity_column="customer_group_id"

##### Json Payload for indexer *scopes_website_data_exporter*

This payload data is stored in table *scopes_website_data_exporter*.
The changelog data is stored in table *scopes_website_data_exporter_cl* every time
there's a change in the observed tables.

```javascript
{
    "websiteId": "0",
    "websiteCode": "admin",
    "stores": [
        {
            "storeId": "0",
            "storeCode": "default",
            "storeViews": [
                {
                    "storeViewId": "0",
                    "storeViewCode": "admin"
                }
            ]
        }
    ]
}
```

##### Json Payload for indexer *scopes_customergroup_data_exporter*

This payload data is stored in table *scopes_customergroup_data_exporter*.
The changelog data is stored in table *scopes_customergroup_data_exporter_cl* every time 
there's a change in the observed tables.

```javascript
{
    "customerGroupId": "0",
    "customerGroupCode": "customer-group-0",
    "websites": [
        {
            "websiteId": "1",
            "websiteCode": "site-1"
        },
        {
            "websiteId": "2",
            "websiteCode": "site-2"
        }
    ]
}
```
