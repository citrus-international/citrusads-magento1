Citrus Ad Extension for Magento 1.x
=============================

This library includes the files of the Citrus Ad extension.


Note
----


Requirements
------------

- Magento 1.9 (to be determined) 


Installation
------------

To install the extension on your magento:

1. On your magento admin panel, go to `System -> Magento Connect -> Magento Connect Manager`

2. Upload the `citrusad-[version_num].tgz` file and click on upload


Usage
-----

After the installation, Go to the magento admin panel

Go to `System -> Configuration`, and click on `CITRUS INTEGRATION -> General Setting` on the left sidebar

Input your `Team id` and `Api Key` from Citrus and also select the `host` correctly

Click on `Save Config`

Then you should be able to sync products, customers, orders and enable ads and banners and add widgets etc. 



Troubleshooting
-----
In our plugin, we rewrote the class `Mage_Catalog_Block_Product_List` by class `Citrus_Integration_Block_Product_List`.
```$xslt
<blocks>
    <catalog>
        <rewrite>
            <product_list>Citrus_Integration_Block_Product_List</product_list>
        </rewrite>
    </catalog>
</blocks>
```
There are conflicts when two (or more) modules rewrite the same class. In that case, the class will only be overwritten 
by one module, so the rest of the modules will not work properly, which in some cases can have a fatal impact on your 
platform. So before you install our plugin, please make sure that there will be no conflicts happening in your installed 
plugins with our plugin. This can be done by checking the content of the <rewrite> in the "config.xml" files on all 
installed modules in your platform. Alternatively, you can use this tool module to help you detect the conflicts easily

https://marketplace.magento.com/alekseon-modules-conflict-detector.html
.

If there is no conflict, you are all good to go. 

If there are conflicts between the plugins, you need to search your way to solve them. You can use 

either 
* Merging - merge the code from one conflicting file into another and switch off the rewrite config.xml in one

or 
* Class inheritance - switch off the rewrite in one config.xml and then make the conflicting extension PHP file extend 
the other extension

or 
* both 

which does depend on the conflicts themselves.

Logs
-----
The requests log file is located at `/var/www/html/web/var/log/citrus.log`.


Licence
-----