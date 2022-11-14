# Upgrade Notes

## 2.0.1
- [IMPROVEMENT] Implement missing image imports for media_type CAROUSEL_ALBUM and VIDEO [#7](https://github.com/dachcom-digital/pimcore-social-data-instagram-connector/pull/7)

## Migrating from Version 1.x to Version 2.0.0

### Global Changes
- ⚠️ You need to set `framework.session.cookie_samesite` to `lax`, otherwise the OAuth connection won't work
- PHP8 return type declarations added: you may have to adjust your extensions accordingly
- Library `facebookarchive/php-graph-sdk` has been removed, we're now using the `league/oauth2-instagram` package which will be installed by default
- Library `instagram-basic-display-php` is not required anymore and will be handled by the `league/oauth2-instagram` package. Remove it from your root composer, if set