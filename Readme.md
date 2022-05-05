# TYPO3 Social Data | Facebook Connector

This connector allows you to fetch social posts from Facebook.

## Installation

```json
"require" : {
    "dachcom-digital/typo3-social-data-facebook-connector" : "~1.0.0",
}
```

## Prerequisites

### set cookieSameSite to lax
Otherwise, the oauth connection won't work.
> If you have any hints to allow processing an oauth connection within `strict` mode,
> please [tell us](https://github.com/dachcom-digital/typo3-social-data-facebook-connector/issues).

```php
$GLOBALS['TYPO3_CONF_VARS']['BE']['cookieSameSite'] = 'lax';
```

## Facebook Backoffice

First, you need to create a facebook app.
 * add Facebook Login product to your app
   * enable OAuth login for web
   * add `https://YOURDOMAIN/typo3/social-data/connector/facebook/connect-callback` to the `Valid OAuth Redirect URIs`.

## Configuration

### Connect a Feed

Use `facebook` as connector.  
After filling out the connector configuration, save, and click on the "connect" button.

![facebook-connector-configuration](https://user-images.githubusercontent.com/7903333/166699535-ee1257e2-51ce-4bd5-93b0-2d9ddb773364.png)

After confirming the modal, a popup opens which guides you through the facebook authentication process.
If everything went fine, the connection setup is completed after the popup closes and the connector status displays "connected".
Otherwise, an error message is shown.
