# Overview
SalesOrdersDataExporter module allows to collect Orders entities and sales related entities (order items, shipments, invoices, credit memos, payment transactions) for further export to external services.
Sales Orders Export works properly only with “Updated On Schedule” indexer mode.
### Entities UUID
Each exported item should have it's own UUID, so only items with UUID assigned will be able to be exported. For example if customer has some orders and then installs SalesOrdersDataExporter module - only orders which would be created after installation will be exported.
There is also ability to assign UUIDs to already assigned orders and order entities: "commerce-data-export:orders:link" command:

```shell
bin/magento commerce-data-export:orders:link -s "new" -f "03/09/2022 10:00:00"

Start updating UUID with parameters [state=new, from=03/09/2022 10:00:00, to=2022-03-09T19:09:50+00:00, batch_size=10000]


```

There are following parameters available for this command:
```shell
-s, --state[=STATE]            Statuses for filter order [default: ""]
Available states: pending/holded/complete/closed e.t.c

-f, --from[=FROM]              Date from for filter order [default: ""]
Any date format, foe example: 03/09/2022 10:00:00
Unix start time will be used by default: 1970-01-01T00:00:00

-t, --to[=TO]                  Date to for filter order [default: ""]
Any date format, foe example: 03/09/2022 10:00:00.
Current time will be used by default

-b, --batch-size[=BATCH-SIZE]  Batch size [default: 10000]
```

### Reindexing
To export orders data simply run reindex command:
```shell
bin/magento indexer:reindex sales_order_data_exporter_v2
```

Reindexing is currently limited to orders modified in the last 7 days.

Note that `commerce-data-export:orders:link` will assign uuids to the orders older than last 7 days but only orders
modified within that timeframe will be exported.
