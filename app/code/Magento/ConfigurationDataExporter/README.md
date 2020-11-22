# Magento_ConfigurationDataExporter module

The ConfigurationDataExporter module is implementing functionality to export changes in system configuration (or to sync full system config)
from Magento to external queue for sharing configuration to external services.

###Full export

Module performs full configuration export to keep in sync configuration that set in `connfig.xml` and deployment configuration files.

There is 2 ways to perform full export:
1. In recurring data upgrade script (to automatically sync configuration on every deployment)
2. By running `bin/magento commerce-data-export:config:export` with optional argument `--store`

###Export configuration updates

Module observes `config_data_save_commit_after` - event when changed configuration saved to db to publish changes to Queue.

###Whitelist of configuration paths

To allow export of configuration specific paths need to be whitelisted:

1. In `deployment configuration files` in `commerce-data-export\configuratio\npath-whitelist` node e.g.:


        'commerce-data-export' => [
            'configuration' => [
               'path-whitelist' => [
                   'general/store_information/name',
                   'general/single_store_mode/enabled'
               ]
            ]
        ]

2. In `di.xml` by adding paths into `Magento\ConfigurationDataExporter\Model\Whitelist\ModularProvider` e.g.:


        <type name="Magento\ConfigurationDataExporter\Model\Whitelist\ModularProvider">
            <arguments>
                <argument name="whitelist" xsi:type="array">
                    <item name="catalog1" xsi:type="string">catalog/search/engine</item>
                    <item name="general1" xsi:type="string">general/store_information/postcode</item>
                </argument>
            </arguments>
        </type>


3. Or create own implementation of `Magento\ConfigurationDataExporter\Api\WhitelistProviderInterface` and inject it into 
`Magento\ConfigurationDataExporter\Model\WhitelistProviderPool`

###Add paths to white list:

Configuration path can be added to whitelist by running `bin/magento commerce-data-export:config:add-paths-to-whitelist`
with space-separated paths as argument.

Note: to allow export of whole section (group) of configuration paths - just define path without specific field e.g.:
`catalog/layered_navigation` - all configuration fields of catalog layered navigation will be allowed for export.