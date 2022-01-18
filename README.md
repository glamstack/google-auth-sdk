# Google Auth SDK

## Overview

The Google Auth SDK is an open source [Composer](https://getcomposer.org/) package
created by [GitLab IT Engineering](https://about.gitlab.com/handbook/business-technology/engineering/)
for use in the [GitLab Access Manager](https://gitlab.com/gitlab-com/business-technology/engineering/access-manager)
Laravel application for connecting to Google API endpoints for
provisioning and deprovisioning of users, groups, group membership, and
other related functionality.

> **Disclaimer:** This is not an official package maintained by the
> Google product and development teams. This is an internal tool that we use
> in the GitLab IT department that we have open sourced as part of our company
> values.
>
> Please use at your own risk and create issues for any bugs that you encounter.
>
> We do not maintain a roadmap of community feature requests, however we
> invite you to contribute and we will gladly review your merge requests.

### Maintainers

| Name                                                                   | GitLab Handle                                          |
| ---------------------------------------------------------------------- | ------------------------------------------------------ |
| [Dillon Wheeler](https://about.gitlab.com/company/team/#dillonwheeler) | [@dillonwheeler](https://gitlab.com/dillonwheeler)     |
| [Jeff Martin](https://about.gitlab.com/company/team/#jeffersonmartin)  | [@jeffersonmartin](https://gitlab.com/jeffersonmartin) |

### How It Works

This package is only intended to authenticate with the Google OAuth2 Sever
utilizing a [Google Service Account](https://cloud.google.com/iam/docs/service-accounts)
token.

The token will be in the form of a JSON file which by default the package will
look under the `storage/keys` directory of your laravel application. For a
file named `google_service_account.json`. The file path is specified in
`config/glamstack-google-auth.php` and can be overridden by setting the
`GOOGLE_JSON_FILE_PATH` environment variable in your `.env` file.

The initialization of the SDK takes a single required parameter that is an
array of the scopes required by the Google Service, followed by an additional
optional string parameter that is the file path to your Google Service Account
JSON file.

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

> If you are contributing to this package, see `CONTRIBUTING.md` for
> instructions on configuring a local composer package with symlinks.

### Custom Logging Configuration

By default, we use the `single` channel for all logs that is configured in
your application's `config/logging.php` file. This sends all Google Auth
log messages to the `storage/logs/laravel.log` file.

If you would like to see Google Auth logs in a separate log file that is easier
to triage without unrelated log messages, you can create a custom log channel.
For example, we recommend using the value of `glamstack-google-auth`, however
you can choose any name you would like.

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

Update the `channels.stack.channels` array to include the array key (ex.
`glamstack-google-auth`) of your custom channel. Be sure to add
`glamstack-google-auth` to the existing array values and not replace the
existing values.

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

#### Google Service Account JSON File

If you want to change the file path from
`storage/keys/google_service_account.json` to load your
[Google Service Account Key](https://cloud.google.com/iam/docs/service-accounts)
from, add the following variable to your `.env` file.

```bash
GOOGLE_JSON_FILE_PATH=""
```

#### Google Subject Email

By default the `client_email` field will be used as the 'Subject Email'.
However, if you are utilizing this SDK to authenticate with any Google
Endpoints that require
[Domain-Wide Delegation](https://developers.google.com/admin-sdk/directory/v1/guides/delegation)
then you will have to add the following variable to your `.env` file.

```bash
GOOGLE_SUBJECT_EMAIL=""
```

This email address is that of a user account in Google Workspace that contains
the appropriate Administrative rights for the APIs that will be utilized.

#### Access Token Storage

Do not store your JSON token anywhere that is not included in the `.gitignore`
file. This is to avoid committing your credentials to your repository (secret
leak)

It is a recommended to store a copy of each access token in your preferred
password manager (ex. 1Password, LastPass, etc.) and/or secrets vault
(ex. HashiCorp Vault, Ansible, etc.).

## Authentication Requests

You can make an Authentication request to Google's OAuth2 Servers.

#### Inline Usage

```php
// Initialize the SDK
$google_auth = new \Glamstack\GoogleAuth\AuthClient(
    ['https://www.googleapis.com/auth/drive']
);

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
        $google_auth = new \Glamstack\GoogleAuth\AuthClient(
            ['https://www.googleapis.com/auth/drive']
        )
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

Please visit our [issue tracker](https://gitlab.com/gitlab-com/business-technology/engineering/access-manager/packages/composer/google-auth-sdk/-/issues)
and create an issue or comment on an existing issue.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) to learn more about how to contribute.-
