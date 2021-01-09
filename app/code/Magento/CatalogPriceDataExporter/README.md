## Release notes

TODO: Move sync to cli command
TODO: Add tests
TODO: Unify the command for the initial event formatting, or create a ticket...

*Magento_CatalogPriceDataExporter* module

This module is responsible for sending price change notifications.
The module is currently based around a custom indexer. 

###Structure
Relevant changes in database are stored in changelog table.
These changes are organised by price type:

    -product_price (special/normal)
    -tier_price
    -custom_option_price
    -custom_option_type_price
    -downloadable_link_price
    -bundle_variation (no price is sent)
    -configurable_variatiComplexProductLinkon (no price is sent)
    -price/product deletion for all the preceding (only for partial sync)

When indexer is set `On Schedule` each price type change creates a different entry in the changelog table with relevant ids,values,etc.

Each price type has 2 different providers responsible for collecting relevant data and generating relevant events.
One is for full reindex, utilizing the changelog data and one is for full reindex.

There are also 2 exceptions:

    -Tier price provider also outputs product_price notification, if the tier price is for qty===1 and general customer group.
    -Some providers also generate price/product delete events, (only for partial reindex).

###Batching
The data returned by the providers is batched using the `\Generator` class.

Partial sync providers batch the data coming from changelog table.

Full sync providers batch the data at the time it is retrieved from the database.
This was done in order to avoid having to request virtually the same data twice (once for entity ids to batch them and once for prices of these entities).
