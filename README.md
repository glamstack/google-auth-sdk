# Google Auth SDK

## Overview

The Google Auth SDK is an open source [Composer](https://getcomposer.org/) package created by [GitLab IT Engineering](https://about.gitlab.com/handbook/business-technology/engineering/) for use in the [GitLab Access Manager](https://gitlab.com/gitlab-com/business-technology/engineering/access-manager) Laravel application for connecting to Google API endpoints for provisioning and deprovisioning of users, groups, group membership, and other related functionality.

> **Disclaimer:** This is not an official package maintained by the Google or GitLab product and development teams. This is an internal tool that we use in the GitLab IT department that we have open sourced as part of our company values.
>
> Please use at your own risk and create issues for any bugs that you encounter.
>
> We do not maintain a roadmap of community feature requests, however we invite you to contribute and we will gladly review your merge requests.

### Maintainers

| Name                                                                   | GitLab Handle                                          |
| ---------------------------------------------------------------------- | ------------------------------------------------------ |
| [Dillon Wheeler](https://about.gitlab.com/company/team/#dillonwheeler) | [@dillonwheeler](https://gitlab.com/dillonwheeler)     |
| [Jeff Martin](https://about.gitlab.com/company/team/#jeffersonmartin)  | [@jeffersonmartin](https://gitlab.com/jeffersonmartin) |

### How It Works

This package is only intended to authenticate with the Google OAuth2 Sever utilizing a [Google Service Account](https://cloud.google.com/iam/docs/service-accounts) token.

The token will be in the form of a JSON file which by default the package will look under the `storage/keys/glamstack-google-auth/` directory of your Laravel application. For a file named `workspace.json`. The file path is specified in `config/glamstack-google-auth.php` and can be overridden by setting the `GOOGLE_AUTH_INSTANCE` environment variable in your `.env` file or by overriding the `$file_path` during initialization of the SDK.


## Installation

### Requirements

| Requirement | Version |
| ----------- | ------- |
| PHP         | >=8.0   |
| Laravel     | >=8.0   |

### Add Composer Package

```bash
composer require glamstack/google-auth-sdk
```

> If you are contributing to this package, see `CONTRIBUTING.md` for instructions on configuring a local composer package with symlinks.

### Custom Logging Configuration

By default, we use the `single` channel for all logs that is configured in your application's `config/logging.php` file. This sends all Google Auth log messages to the `storage/logs/laravel.log` file.

If you would like to see Google Auth logs in a separate log file that is easier to triage without unrelated log messages, you can create a custom log channel.  For example, we recommend using the value of `glamstack-google-auth`, however you can choose any name you would like.

Add the custom log channel to `config/logging.php`.

```php
    'channels' => [

        // Add anywhere in the `channels` array

        'glamstack-google-auth' => [
            'name' => 'glamstack-google-auth',
            'driver' => 'single',
            'level' => 'debug',
            'path' => storage_path('logs/glamstack-google-auth.log'),
        ],
    ],
```

Update the `channels.stack.channels` array to include the array key (ex.  `glamstack-google-auth`) of your custom channel. Be sure to add `glamstack-google-auth` to the existing array values and not replace the existing values.

```php
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single','slack', 'glamstack-google-auth'],
            'ignore_exceptions' => false,
        ],
    ],
```

### Environment Configuration

#### Google Instance Configuration

We use the concept of "instance keys" to define different pre-configured connections that are used by the `AuthClient` to determine the scopes, email, and filename of the JSON API token to use. By default, the SDK will use the `workspace` instance key. However, this can be changed by updating the `.env` variable `GOOGLE_AUTH_INSTANCE` to a different instance key defined in the `config/glamstack-google-auth.php` file. 

```bash
GOOGLE_AUTH_INSTANCE="my_instance_key"
```

If you want to use a specific instance key when using the `AuthClient` that is different from the `GOOGLE_AUTH_INSTANCE` variable, you can pass the instance key as a construct argument for the `AuthClient`.

```php
$google_auth_client = new \Glamstack\GoogleAuth\AuthClient('my_instance_key');
\```
// FIXME: Remove code block leading \ that escapes backticks due to MR suggestion limitations.

#### Google Service Account JSON File

By default the SDK will load the Google Service Account JSON File from the `storage/keys/glamstack-google-auth/{instance_key}.json`. With the default instance key of `workspace`, this will be `workspace.json`. 

There are two ways to change the default path of which the file is loaded from.
    1. Change the instance from `workspace` to the desired Google Instance Configuration during the initialization of the SDK.

```php
$google_auth = new \Glamstack\GoogleAuth\AuthClient('gcp_project_1');
```

1. This will have the SDK look under the following file path for the Google Service Account JSON file `storage/keys/glamstack-google-auth/gcp_project_1.json`.
2. The second option to override the filepath is by passing in the file_path parameter to the initialization of the SDK. This will set the key to that filepath.

```bash
GOOGLE_AUTH_INSTANCE=""
```

#### Google Subject Email

By default the `client_email` field will be used as the 'Subject Email'.  However, if you are utilizing this SDK to authenticate with any Google Endpoints that require [Domain-Wide Delegation](https://developers.google.com/admin-sdk/directory/v1/guides/delegation) then you will have to add the following variable to your `.env` file.

```bash
GOOGLE_AUTH_WORKSPACE_EMAIL=""
```

This email address is that of a user account in Google Workspace that contains the appropriate Administrative rights for the APIs that will be utilized.

#### Google API Scopes

The default configuration file loaded with the package shows an example of the API scope configuration. Ensure to follow the [Principle of Least Privilege](https://www.cisa.gov/uscert/bsi/articles/knowledge/principles/least-privilege). All of the Google Scopes can be found [here](https://developers.google.com/identity/protocols/oauth2/scopes)

#### Access Token Storage

Do not store your JSON token anywhere that is not included in the `.gitignore` file. This is to avoid committing your credentials to your repository (secret leak)

It is a recommended to store a copy of each access token in your preferred password manager (ex. 1Password, LastPass, etc.) and/or secrets vault (ex. HashiCorp Vault, Ansible, etc.).

## Authentication Requests

You can make an Authentication request to Google's OAuth2 Servers.

### Inline Usage

```php
// Initialize the SDK
$google_auth = new \Glamstack\GoogleAuth\AuthClient();

// Send Auth Request
$api_token = $google_auth->authenticate();
```

### Class Methods

The examples above show basic inline usage that is suitable for most use cases. If you prefer to use classes and constructors, the example below will provide a helpful example.

```php
<?php

use Glamstack\GoogleAuth\AuthClient;

class GoogleService
{
    protected $auth_token;

    public function __construct()
    {
        $google_auth = new \Glamstack\GoogleAuth\AuthClient()
        $this->auth_token = $google_auth->authenticate();
    }

    public function getUser($user_key)
    {
        $response = Http:withToken($this->auth_token)
            ->get('https://admin.googleapis.com/admin/directory/v1/users/'.
            $user_key);

        return $response->object;
    }
}
```

## Issue Tracking and Bug Reports

Please visit our [issue tracker](https://gitlab.com/gitlab-com/business-technology/engineering/access-manager/packages/composer/google-auth-sdk/-/issues) and create an issue or comment on an existing issue.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) to learn more about how to contribute.-
