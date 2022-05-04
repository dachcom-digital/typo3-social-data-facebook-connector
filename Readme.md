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

In the backend, prepare sysfolders for storing the feed and the posts.
Create a new record of type "Feed" which is found under the "Social Data" section.

### Connect a Feed

Use `DachcomDigital\SocialDataFacebook\Connector\FacebookConnectorDefinition` as connector.
After filling out the connector configuration, save, and click on the "connect" button.

After confirming the modal, a popup opens which guides you through the facebook authentication process.
If everything went fine, the connection setup is completed after the popup closes and the connector status displays "connected".
Otherwise, an error message is shown.

### Create a wall

To display feed data on your page, create a new record of type "Wall" which is found under the "Social Data" section.
Select the feeds you want to be shown.

### Fetch posts

The extension provides a console command `social-data:fetch:posts`, which can be used from cli (for testing).  
> To keep the posts in sync, create a scheduled task of type "Execute console commands", and select the above command. 

### Display the data

Use the Plugin "Social Data" plugin and select the desired wall to output the posts in the frontend.
