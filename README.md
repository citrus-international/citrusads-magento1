Citrus Ad Extension for Magento 1.x
=============================

This library includes the files of the Citrus Ad extension.


Note
----


Requirements
------------

- Magento 1.x (to be determined)


Installation
------------

To install the extension on your magento:

1. On your magento admin panel, go to `System -> Magento Connect -> Magento Connect Manager`

2. Upload the `citrusad-[version_num].tgz` file and click on upload


Setup the Citrus Plugin
-----

#### General settings
After the installation, go to `Magento admin panel -> System -> Configuration`, and click on `CITRUS INTEGRATION -> General Setting`.

Enable the plugin in Magento admin panel, input your `Team Id` and `Api Key` from Citrus and also select the `host` correctly.

NB: To find out your `Team Id` and `Api Key`, please login Citrus client and go into the `Integration Settings` from the drop down list at the top right corner.

Click `Save Config`

Then you should be able to sync products, customers, orders and enable ads and banners and add widgets etc.

#### Synchronization settings
Go to `Magento admin panel -> System -> Configuration`, and click on `CITRUS INTEGRATION -> Synchronization Option` on the left sidebar.

`Ads`(display ads) and `Banners` can be switched ON/OFF separately. 

Enable synchronization for both `Customer/Orders` and `Products` and set sync modes as `Real-time`. 

Click `Save Config`.

Click buttons `Add all customers to queue`, `Add all orders to queue` and `Add all products to queue`.

Now that we have all the customers/orders/products staged in the queue, go to `Magento admin panel -> Citrus -> Queue List`, select the items to submit, select `sync` and click `Submit` so that they are actully synced to Citrus.


Troubleshooting
-----

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

The `Citrus_Integration_Block_Product_List` class overrides ```_getProductCollection()``` and adds two new methods 
```getAdResponse()``` and ```sortByIndex()```. 

If your plugins do not override the same methods, you can use class inheritance and make our class extend yours or the 
vice versa and then switch off the corresponding `<rewrite>`. If you want to use merging in this case , you need to 
move the logic in the conflicting class to the one you want to merge to and switch off 
the corresponding `<rewrite>`.   

If your plugins do override the same method of ours, you might need to use merging in this case with or without class 
inheritance. You need to make sure the logic and functionality from both plugins need to be implemented correctly and 
this requires understanding of the code and programming skills.

Furthermore, we implemented our click/impression functionalities in Javascript which depends on the data in `list.phtml` 
so if your plugins have modified this file, you probably need to merge our logic with yours in this file as well. 
Logs
-----
The requests log file is located at `/var/www/html/web/var/log/citrus.log`.


Licence
-----
