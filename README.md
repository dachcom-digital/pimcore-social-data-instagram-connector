# Pimcore Social Data - Instagramm Connector

[![Software License](https://img.shields.io/badge/license-GPLv3-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Latest Release](https://img.shields.io/packagist/v/dachcom-digital/social-data-instagram-connector.svg?style=flat-square)](https://packagist.org/packages/dachcom-digital/social-data-instagram-connector)
[![Tests](https://img.shields.io/github/workflow/status/dachcom-digital/pimcore-social-data-instagram-connector/Codeception?style=flat-square&logo=github&label=codeception)](https://github.com/dachcom-digital/pimcore-social-data-instagram-connector/actions?query=workflow%3A%22Codeception%22)
[![PhpStan](https://img.shields.io/github/workflow/status/dachcom-digital/pimcore-social-data-instagram-connector/PHP%20Stan?style=flat-square&logo=github&label=phpstan%20level%202)](https://github.com/dachcom-digital/pimcore-social-data-instagram-connector/actions?query=workflow%3A%22PHP%20Stan%22)

This Connector allows you to fetch social posts from Instagram. 
Before you start be sure you've checked out the [Setup Instructions](../00_Setup.md).

![image](https://user-images.githubusercontent.com/700119/95104131-c7b32680-0735-11eb-8bf2-696ca98c220d.png)

#### Requirements
* [Pimcore Social Data Bundle](https://github.com/dachcom-digital/pimcore-social-data)

## Installation

### I. Add Dependency
```json
"require" : {
    "dachcom-digital/social-data-instagram-connector" : "~1.0.0",
}
```

### II. Register Connector Bundle
```php
// src/AppKernel.php
use Pimcore\Kernel;
use Pimcore\HttpKernel\BundleCollection\BundleCollection;

class AppKernel extends Kernel
{
    public function registerBundlesToCollection(BundleCollection $collection)
    {
        $collection->addBundle(new SocialData\Connector\Instagram\SocialDataInstagramConnectorBundle());
    }
}
```

### III. Install Assets
```bash
bin/console assets:install web --relative --symlink
```

## Third-Party Requirements
To use this connector, this bundle requires some additional packages:
- [facebook/graph-sdk](https://github.com/facebookarchive/php-graph-sdk): Required for business API (Mostly already installed within a Pimcore Installation)
- [instagram-basic-display-php](https://github.com/espresso-dev/instagram-basic-display-php): Required for private API

## Enable Connector

```yaml
# app/config/config.yml
social_data:
    social_post_data_class: SocialPost
    available_connectors:
        -   connector_name: instagram
```

## Connector Configuration
![image](https://user-images.githubusercontent.com/700119/95104195-dac5f680-0735-11eb-9818-de5619b129b8.png)

Now head back to the backend (`System` => `Social Data` => `Connector Configuration`) and checkout the instagram tab.
- Click on `Install`
- Click on `Enable`
- Before you hit the `Connect` button, you need to fill you out the Connector Configuration. After that, click "Save".
- Click `Connect`
  
## Connection
![image](https://user-images.githubusercontent.com/700119/95104255-e7e2e580-0735-11eb-8058-6274e27e737e.png)

This will guide you through the instagram token generation. 
After hitting the "Connect" button, a popup will open to guide you through instagram authentication process. 
If everything worked out fine, the connection setup is complete after the popup closes.
Otherwise, you'll receive an error message. You may then need to repeat the connection step.

## Feed Configuration

| Name | Description
|------|----------------------|
| `Limit` | Define a limit to restrict the amount of social posts to import (Default: 50) |

## Extended Connector Configuration
Normally you don't need to modify connector (`connector_config`) configuration, so most of the time you can skip this step.
However, if you need to change some core setting of a connector, you're able to change them of course.

```yaml
# app/config/config.yml
social_data:
    available_connectors:
        -   connector_name: instagram
            connector_config:
                api_connect_permission_business: ['pages_show_list', 'instagram_basic'] # default value
```

***

## Copyright and license
Copyright: [DACHCOM.DIGITAL](http://dachcom-digital.ch)  
For licensing details please visit [LICENSE.md](LICENSE.md)  

## Upgrade Info
Before updating, please [check our upgrade notes!](UPGRADE.md)
