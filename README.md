# Overview
This project purposed to assemble and synchronization of data that represent Magento entities with integrations,
which are sources for the data consumed by external or SaaS services and integrations.

To collect and store the denormalized data required by the SaaS services and integrations,
the export component utilizes native Magento indexers functionality under the hood.
As a result, after the component installation,
a merchant will notice new indexers in the corresponding admin menu,
where each indexer represents a data feed.

### Code generation
Magento_CatalogExportApi module contains DTOs that are automatically generated on every run of setup:upgrade and setup:di:compile. 

## Requirements and Dependencies
The export component is a set Magento modules and requires Magento 2.3 and higher.

### Contributing
Contributions are welcomed! Read the [Contributing Guide](./CONTRIBUTING.md) for more information.

### Licensing
This project is licensed under the OSL-3.0 License. See [LICENSE](./LICENSE.md) for more information.