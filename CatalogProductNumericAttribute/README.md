# AdobeCommerce_CatalogProductNumericAttribute

Adds a **Number** (`numeric`) product attribute input type that stores values as `decimal`, allows negative values, displays no currency symbol, and generates range facets for Live Search / Product Discovery.

## Motivation

Existing decimal-backed input types (`price`, `weight`) reject negative values in their backend models and — in the case of `price` — add currency formatting. This prevents merchants from creating attributes like operating temperature ranges (e.g., -40.5°C to +85°C) that require negative decimals with range-based filtering.

## What the module does

| Extension point                                                                  | Technique                 | Effect                                                                                                                                                                    |
|----------------------------------------------------------------------------------|---------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `Magento\Eav\Model\Entity\Attribute::getBackendTypeByInput()`                    | After plugin              | Maps `numeric` → `decimal` backend storage                                                                                                                                |
| `Magento\Eav\Model\Entity\Attribute::getDefaultValueByInput()`                   | After plugin              | Maps `numeric` → `default_value_text`                                                                                                                                     |
| `Magento\Eav\Helper\Data::getInputTypesValidatorData()`                          | After plugin              | Allows `numeric` to pass input-type validation                                                                                                                            |
| `general/validator_data/input_types`                                             | `config.xml` merge        | Registers `numeric` as a valid input type                                                                                                                                 |
| `Magento\Catalog\Model\ResourceModel\Eav\Attribute::isAllowedForRuleCondition()` | After plugin              | Allows `numeric` in catalog price rule conditions                                                                                                                         |
| `Magento\CatalogGraphQl\Model\Config\FilterAttributeReader::read()`              | After plugin              | Maps `numeric` to `FilterRangeTypeInput` for GraphQL                                                                                                                      |
| `Magento\Ui\DataProvider\Mapper\FormElement`                                     | `di.xml` argument merge   | Maps `numeric` → `input` form element in admin                                                                                                                            |
| `Magento\Eav\Model\Adminhtml\System\Config\Source\Inputtype`                     | `di.xml` argument merge   | Adds "Number" to EAV input type dropdown (inherited by product attribute form)                                                                                            |
| `NumericMetadataFormatter` virtual type                                          | `di.xml` argument merge   | Flags `numeric` attributes as `numeric: true` in SaaS feed                                                                                                                |
| `product_attribute_add_form.xml` `is_filterable` / `is_filterable_in_search`     | UI component XML merge    | Enables "Use in Layered Navigation" dropdown for `numeric` in the add-attribute form                                                                                      |
| `catalog_product_attribute_edit` / `_edit_popup` layouts                         | Layout XML + PHTML script | Enables "Use in Layered Navigation" dropdown for `numeric` in the legacy attribute edit form                                                                              |
| `Magento\Catalog\Ui\Component\ColumnFactory::create()`                           | Before plugin             | Sets grid column `filter` to `textRange` for `numeric` when **Use in Filter Options** is enabled so the Admin product grid shows from/to inputs (same mechanism as Price) |

## Behavior

- **Backend type**: `decimal` (stored in `catalog_product_entity_decimal`)
- **Validation**: No min-value constraint — negative decimals are allowed
- **Admin UI**: Standard text input, no currency symbol; product grid filters use **from / to** (`textRange`) when the attribute is allowed in filter options
- **Layered Navigation**: "Use in Layered Navigation" dropdown is enabled (Filterable with results / Filterable no results)
- **GraphQL**: Registered as `FilterRangeTypeInput` for range queries
- **SaaS feed**: Exported with `numeric: true` so Live Search generates range facet buckets
- **Rule conditions**: Available for catalog price rules
