Squeezely Magento 2 Plugin
=====================
This is the Squeezely Magento 2 plugin.
For a Squeezely subscription, go to https://www.squeezely.tech
GitHub: https://github.com/squeezely/magento2-plugin

## CONTACTS
* Email: support@squeezely.tech

## INSTALLATION

### COMPOSER INSTALLATION
* run composer command:
>`$> composer require squeezely/magento2-plugin`

### ENABLE EXTENSION
* enable extension (use Magento 2 command line interface):
>`$> php bin/magento module:enable Squeezely_Plugin`

* to make sure that the enabled module is properly registered, run 'setup:upgrade':
>`$> php bin/magento setup:upgrade`

* [if needed] re-compile code and re-deploy static view files:
>`$> php bin/magento setup:di:compile`
>`$> php bin/magento setup:static-content:deploy`