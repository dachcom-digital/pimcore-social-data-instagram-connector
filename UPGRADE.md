# Upgrade Notes

## Update from Version 3.x to Version 4.0.0
- [NEW FEATURE | BREAKING CHANGE]: Supporting "Instagram API with Instagram Login" and "Instagram API with Facebook Login" strategies
    - Migration:
        - ⚠️ `connector_config` has changed:
            - instead of `api_connect_permission_private` please use `api_connect_permission_instagram_login`
            - instead of `api_connect_permission_business` please use `api_connect_permission_facebook_login`
      - Reconnect to instagram in configuration pannel
      - Resave your walls
      - **Important**: If you're using the "Instagram API with Facebook Login" type, you also need to install the `"league/oauth2-facebook": "^2.0"` extension.
        You also need to select the instagram account in all instagram related walls!

***

Version 3.x Upgrade Notes: https://github.com/dachcom-digital/pimcore-social-data-instagram-connector/blob/3.x/UPGRADE.md