# Magento 2 Remarketing Returning Customer Extension

[![Latest Stable Version](https://poser.pugx.org/bryanyeh/emailevent/v/stable)](https://packagist.org/packages/bryanyeh/emailevent)
[![Latest Stable Version](https://poser.pugx.org/bryanyeh/emailevent/v/stable)](https://packagist.org/packages/bryanyeh/emailevent)
[![License](https://poser.pugx.org/bryanyeh/emailevent/license)](https://packagist.org/packages/bryanyeh/emailevent)

This Magento 2 Extension emails returning customer highest % off coupon code

## Requirements
  * Magento Community Edition 2.2.x

## Installation Method 1 - Installing via composer
  * Open command line
  * Using command "cd" navigate to your magento2 root directory
  * Run commands: 
  
```
composer require bryanyeh/emailevent
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy
```
  

## Installation Method 2 - Installing using archive
  * Download [ZIP Archive](https://github.com/BryanYeh/Remarketing-Returning-Customer/archive/1.0.3.zip)
  * Extract files
  * In your Magento 2 root directory create folder app/code/Gss/EmailEvent
  * Copy files and folders inside the Remarketing-Returning-Customer folder from archive to that folder 
  * In command line, using "cd", navigate to your Magento 2 root directory
  * Run commands:
```
php bin/magento setup:upgrade
php bin/magento setup:static-content:deploy
```

## License
The code is licensed under [The MIT License](https://opensource.org/licenses/MIT).