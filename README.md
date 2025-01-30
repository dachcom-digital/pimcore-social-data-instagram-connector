# Pimcore Social Data - Instagram Connector
[![Software License](https://img.shields.io/badge/license-GPLv3-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Software License](https://img.shields.io/badge/license-DCL-white.svg?style=flat-square&color=%23ff5c5c)](LICENSE.md)
[![Latest Release](https://img.shields.io/packagist/v/dachcom-digital/social-data-instagram-connector.svg?style=flat-square)](https://packagist.org/packages/dachcom-digital/social-data-instagram-connector)
[![Tests](https://img.shields.io/github/actions/workflow/status/dachcom-digital/pimcore-social-data-instagram-connector/.github/workflows/codeception.yml?branch=master&style=flat-square&logo=github&label=codeception)](https://github.com/dachcom-digital/pimcore-social-data-instagram-connector/actions?query=workflow%3ACodeception+branch%3Amaster)
[![PhpStan](https://img.shields.io/github/actions/workflow/status/dachcom-digital/pimcore-social-data-instagram-connector/.github/workflows/php-stan.yml?branch=master&style=flat-square&logo=github&label=phpstan%20level%204)](https://github.com/dachcom-digital/pimcore-social-data-instagram-connector/actions?query=workflow%3A"PHP+Stan"+branch%3Amaster)

This Connector allows you to fetch social posts from Instagram (Currently only via basic display api). 

> [!IMPORTANT]  
> The [Instagram Basic Display API](https://developers.facebook.com/blog/post/2024/09/04/update-on-instagram-basic-display-api) has been shut down.
> Personal Instagram accounts are no longer supported. Therefor this extension **only works with Creator or Business Instagram accounts**! 

![image](https://user-images.githubusercontent.com/700119/95104131-c7b32680-0735-11eb-8bf2-696ca98c220d.png)

### Release Plan
| Release | Supported Pimcore Versions | Supported Symfony Versions | Release Date | Maintained     | Branch                                                                                     |
|---------|----------------------------|----------------------------|--------------|----------------|--------------------------------------------------------------------------------------------|
| **4.x** | `11.0`                     | `6.4`                      | 29.01.2025   | Feature Branch | master                                                                                     |
| **3.x** | `11.0`                     | `6.4`                      | 07.11.2023   | Unsupported    | [3.x](https://github.com/dachcom-digital/pimcore-social-data-instagram-connector/tree/3.x) |
| **2.x** | `10.1` - `10.6`            | `5.4`                      | 05.01.2022   | Unsupported    | [2.x](https://github.com/dachcom-digital/pimcore-social-data-instagram-connector/tree/2.x) |
| **1.x** | `6.0` - `6.9`              | `3.4`, `^4.4`              | 22.10.2020   | Unsupported    | [1.x](https://github.com/dachcom-digital/pimcore-social-data-instagram-connector/tree/1.x) |

## Installation

```json
"require" : {
    "dachcom-digital/social-data" : "~3.1.0",
    "dachcom-digital/social-data-instagram-connector" : "~4.0.0"
}
```

### API via Facebook Login
If you want to use the facebook api, you also have to install the `league/oauth2-facebook` extension:

```json
"require" : {
    "league/oauth2-facebook": "^2.0"
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

## Setup App
Some hints to set up your instagram app

### Instagram API with Instagram Login
- Create Business App
- Add "Instagram" Product
   - Add `https://YOURDOMAIN/admin/social-data/connector/instagram/check` in `Instagram => API setup with Instagram business login
 => 3. Set up Instagram business login => Business login settings`
- Select `Instagram API with Instagram Login` and store `Instagram app ID` and `Instagram app secret` in the pimcore connector section

### Instagram API with Facebook Login
- Create Business App
- Select `Instagram API with Facebook Login` and store `App-ID` and `App Secret` in the pimcore connector section
- Add "Facebook Login for Business" Product
   - Add `https://YOURDOMAIN/admin/social-data/connector/instagram/check` in `Valid OAuth Redirect URIs` in section "Settings"

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
                api_connect_permission_instagram_login: ['instagram_business_basic'] # default value
                api_connect_permission_facebook_login: ['instagram_basic', 'pages_read_engagement', 'pages_show_list', 'business_management'] # default value
```

***

## Upgrade Info
Before updating, please [check our upgrade notes!](UPGRADE.md)

## License
**DACHCOM.DIGITAL AG**, Löwenhofstrasse 15, 9424 Rheineck, Schweiz  
[dachcom.com](https://www.dachcom.com), dcdi@dachcom.ch  
Copyright © 2025 DACHCOM.DIGITAL. All rights reserved.  

For licensing details please visit [LICENSE.md](LICENSE.md)  
