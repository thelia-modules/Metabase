# Metabase

This module allows to connect to your Metabase account and use print some statistics on admin panel 

## Installation

### Manually

* Copy the module into ```<thelia_root>/local/modules/``` directory and be sure that the name of the module is Metabase.
* Activate it in your thelia administration panel

### Composer

Add it in your main thelia composer.json file

```
composer require thelia/metabase-module:~1.0
```

### Usage

Go to the configuration panel

Configure Metabase with your Url, your mail metabase and your password metabase

To get your integration token : go to https://***your-metabase-url***/admin/settings/embedding-in-other-applications and activate Integration

