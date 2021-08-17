
## Release notes

*QueryXmlDataProvider* module

The ultimate plugin to connect Data Exporter and QueryXML through the naming convention.
`et_schema.xml` may request data from `query.xml` directly by using the common provider.
The naming convention can overload the virtual type in `di.xml` when config files can not coincide. 

### Example, returns websites, store, and store views as hierarchical array

#### query.xml

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_QueryXml:etc/query.xsd">
    <query name="storeHierarchy">
        <source name="store_website">
            <attribute name="website_id" alias="websiteId" />
            <attribute name="code" alias="websiteCode" />
            <attribute name="name" alias="name" />
            <filter glue="and">
                <condition attribute="website_id" operator="in" type="placeholder">websiteId</condition>
            </filter>
        </source>
    </query>
    <query name="storeGroups">
        <source name="store_group">
            <attribute name="group_id" alias="storeGroupId" />
            <attribute name="website_id" alias="websiteId" />
            <attribute name="code" alias="storeGroupCode" />
            <attribute name="name" alias="name" />
            <filter glue="and">
                <condition attribute="website_id" operator="in" type="placeholder">websiteId</condition>
            </filter>
        </source>
    </query>
    <query name="storeViews">
        <source name="store">
            <attribute name="group_id" alias="storeGroupId" />
            <attribute name="store_id" alias="storeViewId" />
            <attribute name="website_id" alias="websiteId" />
            <attribute name="code" alias="storeViewCode" />
            <attribute name="name" alias="name" />
            <filter glue="and">
                <condition attribute="group_id" operator="in" type="placeholder">storeGroupId</condition>
            </filter>
        </source>
    </query>
</config>
```
#### et_schema.xml
```xml
<?xml version="1.0" encoding="UTF-8"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:module:Magento_DataExporter:etc/et_schema.xsd">
    <record name="Export">
        <field name="storeHierarchy" type="Website" repeated="true"
               provider="Magento\QueryXml\Model\DataExporterProvider">
            <using field="websiteId"/>
        </field>
    </record>
    <record name="Website">
        <field name="websiteId" type="ID"/>
        <field name="websiteCode" type="String"/>
        <field name="name" type="String"/>
        <field name="storeGroups"
               type="StoreGroup"
               repeated="true"
               provider="Magento\QueryXml\Model\DataExporterProvider"
        >
            <using field="websiteId"/>
        </field>
    </record>
    <record name="StoreGroup">
        <field name="storeGroupId" type="ID"/>
        <field name="storeGroupCode" type="String"/>
        <field name="name" type="String"/>
        <field name="storeViews"
               type="StoreView"
               repeated="true"
               provider="Magento\QueryXml\Model\DataExporterProvider"
        >
            <using field="storeGroupId"/>
        </field>
    </record>
    <record name="StoreView">
        <field name="storeViewId" type="ID"/>
        <field name="storeGroupId" type="String"/>
        <field name="storeViewCode" type="String"/>
        <field name="name" type="String"/>
    </record>
</config>
```

#### Usage 

```php
    /** @var \Magento\DataExporter\Export\Processor $exportProcessor */
    $exportProcessor = $this->_objectManager->get(\Magento\DataExporter\Export\Processor::class );
    $website = $exportProcessor->process('storeHierarchy', [['websiteId' => 1]]);

    echo "<pre>";
    echo json_encode($website, JSON_PRETTY_PRINT);
```

#### Output

```json
[
    {
        "websiteId": "1",
        "websiteCode": "base",
        "name": "Main Website",
        "storeGroups": [
            {
                "storeGroupId": "1",
                "storeGroupCode": "main_website_store",
                "name": "Main Website Store",
                "storeViews": [
                    {
                        "storeViewId": "1",
                        "storeGroupId": "1",
                        "storeViewCode": "default",
                        "name": "Default Store View"
                    },
                    {
                        "storeViewId": "2",
                        "storeGroupId": "1",
                        "storeViewCode": "store_view_2",
                        "name": "Store view 2 - website_id_1 - group_id_1"
                    },
                    {
                        "storeViewId": "3",
                        "storeGroupId": "1",
                        "storeViewCode": "store_view_3",
                        "name": "Store view 3 - website_id_1 - group_id_1"
                    },
                    {
                        "storeViewId": "4",
                        "storeGroupId": "1",
                        "storeViewCode": "store_view_4",
                        "name": "Store view 4 - website_id_1 - group_id_1"
                    }
                ]
            }
        ]
    }
]
```
