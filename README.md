# Pimcore Social Data - Instagram Connector
[![Software License](https://img.shields.io/badge/license-GPLv3-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Software License](https://img.shields.io/badge/license-DCL-white.svg?style=flat-square&color=%23ff5c5c)](LICENSE.md)
[![Latest Release](https://img.shields.io/packagist/v/dachcom-digital/social-data-instagram-connector.svg?style=flat-square)](https://packagist.org/packages/dachcom-digital/social-data-instagram-connector)
[![Tests](https://img.shields.io/github/actions/workflow/status/dachcom-digital/pimcore-social-data-instagram-connector/.github/workflows/codeception.yml?branch=master&style=flat-square&logo=github&label=codeception)](https://github.com/dachcom-digital/pimcore-social-data-instagram-connector/actions?query=workflow%3ACodeception+branch%3Amaster)
[![PhpStan](https://img.shields.io/github/actions/workflow/status/dachcom-digital/pimcore-social-data-instagram-connector/.github/workflows/php-stan.yml?branch=master&style=flat-square&logo=github&label=phpstan%20level%204)](https://github.com/dachcom-digital/pimcore-social-data-instagram-connector/actions?query=workflow%3A"PHP+Stan"+branch%3Amaster)

This Connector allows you to fetch social posts from Instagram (Currently only via basic display api). 

![image](https://user-images.githubusercontent.com/700119/95104131-c7b32680-0735-11eb-8bf2-696ca98c220d.png)

### Release Plan
| Release | Supported Pimcore Versions | Supported Symfony Versions | Release Date | Maintained     | Branch                                                                                     |
|---------|----------------------------|----------------------------|--------------|----------------|--------------------------------------------------------------------------------------------|
| **3.x** | `11.0`                     | `6.2`                      | 07.11.2023   | Feature Branch | master                                                                                     |
| **2.x** | `10.1` - `10.6`            | `5.4`                      | 05.01.2022   | Unsupported    | [2.x](https://github.com/dachcom-digital/pimcore-social-data-instagram-connector/tree/2.x) |
| **1.x** | `6.0` - `6.9`              | `3.4`, `^4.4`              | 22.10.2020   | Unsupported    | [1.x](https://github.com/dachcom-digital/pimcore-social-data-instagram-connector/tree/1.x) |

## Installation

```json
"require" : {
    "dachcom-digital/social-data" : "~3.1.0",
    "dachcom-digital/social-data-instagram-connector" : "~3.1.0"
}
```

Add Bundle to `bundles.php`:
```php
return [
    SocialData\Connector\Instagram\SocialDataInstagramConnectorBundle::class => ['all' => true],
];
```

### Install Assets
```bash
bin/console assets:install public --relative --symlink
```

## Enable Connector
```yaml
# config/packages/social_data.yaml
social_data:
    social_post_data_class: SocialPost
    available_connectors:
        -   connector_name: instagram
```

### Set Cookie SameSite to Lax
Otherwise, the oauth connection won't work.
> If you have any hints to allow processing an oauth connection within `strict` mode, 
> please [tell us](https://github.com/dachcom-digital/pimcore-social-data-instagram-connector/issues).

```yaml
framework:
    session:
        cookie_samesite: 'lax'
```

## Instagram Backoffice
Some hints to set up your instagram app

### Private
- Create Non-Business Facebook App
- Add Instagram Basic Display Product
   - Add `https://YOURDOMAIN/admin/social-data/connector/instagram/check` in `Valid OAuth Redirect URIs`
   - Add `https://YOURDOMAIN/admin/social-data/connector/instagram/deauthorize` in `Deauthorize` (dummy)
   - Add `https://YOURDOMAIN/admin/social-data/connector/instagram/data-deletion` in `Data Deletion Requests` (dummy)
- Add at least one instagram test account

### Business API
Even if you're allowed to choose between a private and business connection, the business API is currently not supported and will be available soon.
- Create Business Facebook App
- Add Instagram Graph API

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
| Name    | Description                                                                   |
|---------|-------------------------------------------------------------------------------|
| `Limit` | Define a limit to restrict the amount of social posts to import (Default: 50) |

## Extended Connector Configuration
Normally you don't need to modify connector (`connector_config`) configuration, so most of the time you can skip this step.
However, if you need to change some core setting of a connector, you're able to change them of course.

```yaml
# config/packages/social_data.yaml
social_data:
    available_connectors:
        -   connector_name: instagram
            connector_config:
                api_connect_permission_private: ['user_profile', 'user_media'] # default value
                api_connect_permission_business: ['pages_show_list', 'instagram_basic'] # default value
```

***

## Upgrade Info
Before updating, please [check our upgrade notes!](UPGRADE.md)

## License
**DACHCOM.DIGITAL AG**, Löwenhofstrasse 15, 9424 Rheineck, Schweiz  
[dachcom.com](https://www.dachcom.com), dcdi@dachcom.ch  
Copyright © 2024 DACHCOM.DIGITAL. All rights reserved.  

For licensing details please visit [LICENSE.md](LICENSE.md)  
