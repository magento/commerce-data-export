# AdobeCommerce_ProductReviewSummarySync

Exports native Magento product review summaries (`Magento_Review`) to the Catalog Service products feed and keeps them
in sync with the reviews.

It provides the review summary source for `AdobeCommerce_ProductReviewSummaryAttributes`, which adds the values to the
products feed as the `ac_review_count` and `ac_rating_summary` attributes.

## Source

`Model\ReviewSummarySource` reads `review_entity_summary` for each store view:

| Attribute           | Column                                  |
|---------------------|-----------------------------------------|
| `ac_review_count`   | `reviews_count` (approved reviews)      |
| `ac_rating_summary` | `rating_summary` (average rating, 0-100) |

- Summaries are exported only for store views where "Enable Product Reviews" (`catalog/review/active`) is on. The
  feature is enabled when reviews are enabled in at least one store view.
- Summaries with 0 approved reviews are exported as `0`, so values are reset when reviews are unapproved or deleted.
- The admin (`store_id = 0`) summary row is never read: core does not reset it when the last review is unapproved.
- Read errors are not caught: the feed indexer keeps the batch in the changelog and retries it on the next run, instead
  of exporting the products without their review summary values.

## Sync triggers

Products are added to the products feed changelog and exported by the next feed indexer run (feed indexers always run
"Update by Schedule"). Products whose feed data did not change are not sent again.

- **Review summary changes**: `etc/mview.xml` subscribes the products feed to `review_entity_summary`
  (`entity_pk_value`), so any write to a summary row schedules the product:
  - review submitted, approved, rejected, saved, mass status update or deleted, from Admin, storefront or GraphQL
    (core `Review::aggregate()`);
  - rating deleted (Stores > Attributes > Rating, e.g. "Quality"): core recalculates `rating_summary` for every
    product with `Review\Summary::reAggregate()`;
  - reviews deleted together with their product.

  Products are scheduled even when reviews are disabled; their feed data does not change, so they are not sent again.
- **"Enable Product Reviews" changes**: an observer on `config_data_save_after` / `config_data_delete_after` schedules
  all products that have or had reviews when `catalog/review/active` changes in any scope (Admin,
  `bin/magento config:set`, including `--lock-env` and `--lock-config`).
  - Enabled: summary values are added to the feed.
  - Disabled: summary values are no longer exported for the store view.
  - Skipped when reviews are disabled and no values were ever exported (attributes not created yet).

`Model\ReviewSummaryResync::reconcile()` schedules all products that have or had reviews.

## Known gaps

These changes are not detected. Run `bin/magento saas:resync --feed=products --force` to re-export all products, or
`bin/magento saas:resync --feed=products --by-ids=SKU1,SKU2` for specific products:

- `catalog/review/active` deployed through `config.php` or `env.php` and imported with `app:config:import`: the config
  save event fires, but the value is already the new one, so it is not seen as a change.
- A `catalog/review/active` default changed in `config.xml`: no config save event is fired.

## Notes

- Reconcile reads reviewed products with a single `INSERT ... SELECT` into the changelog.
