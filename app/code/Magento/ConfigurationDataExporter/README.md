# Magento_ConfigurationDataExporter module

The ConfigurationDataExporter module is implementing functionality to export changes in system configuration (or to sync full system config)
from Magento to external queue for sharing configuration to external services.

###Full export

Module performs full configuration export to keep in sync configuration that set in `connfig.xml` and deployment configuration files.

There is 2 ways to perform full export:
1. In recurring data upgrade script (to automatically sync configuration on every deployment)
2. By running `bin/magento config-export:sync:full` with optional argument `--store`

###Export configuration updates

Module observes `config_data_save_commit_after` - event when changed configuration saved to db to publish changes to Queue.
