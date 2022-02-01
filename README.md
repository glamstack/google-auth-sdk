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

This package is used to authenticate with the Google OAuth2 Sever utilizing a [Google Service Account](https://cloud.google.com/iam/docs/service-accounts) **JSON API key file**.

The OAUTH service will return a **short-leved API token** that can be used with the [Laravel HTTP Client](https://laravel.com/docs/8.x/http-client) to perform `GET`, `POST`, `PATCH`, `DELETE`, etc. API requests that can be found in the [Google API Explorer](https://developers.google.com/apis-explorer) documentation.

To provide a streamlined developer experience, your JSON API key is stored in the `storage/keys/glamstack-google-auth/` directory of your Laravel application, and the scopes for each key are pre-configured in the `config/glamstack-google.php` configuration file for each of your "connections" (1:1 relationship with each JSON key file that has defined scopes).

This SDK supports a global default connection that is defined in your `.env` file, as well as multiple connections that can be used throughout your application as needed using the _connection key_ defined in `config/glamstack-google.php`.

### Inline Usage

```php
// Initialize the SDK using the default connection
$google_auth = new \Glamstack\GoogleAuth\AuthClient('workspace');

// You can also initialize the SDK using the default connection that you have
// configured in `config/glamstack-gitlab-config.php` or your .env file.
// $google_auth = new \Glamstack\GoogleAuth\AuthClient();

// Send Auth Request to get JWT token
$api_token = $google_auth->authenticate();

// Perform API Request using short-lived JWT token
// https://developers.google.com/admin-sdk/directory/reference/rest/v1/users/get
$user_key = 'klibby@example.com';
$response = Http::withToken($api_token)
    ->get('https://admin.googleapis.com/admin/directory/v1/users/' . $user_key);

return $response->object;

```

### Class Methods

The examples above show basic inline usage that is suitable for most use cases. If you prefer to use classes and constructors, the example below will provide a helpful example.

```php
<?php

use Glamstack\GoogleAuth\AuthClient;

class GoogleWorkspaceUserService
{
    protected $auth_token;

    public function __construct()
    {
        $google_auth = new \Glamstack\GoogleAuth\AuthClient('workspace')
        $this->auth_token = $google_auth->authenticate();
    }

    public function getUser($user_key)
    {
        $response = Http::withToken($this->auth_token)
            ->get('https://admin.googleapis.com/admin/directory/v1/users/' . $user_key);

        return $response->object;
    }
}
```

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

> If you are contributing to this package, see [CONTRIBUTING](CONTRIBUTING.md) for instructions on configuring a local composer package with symlinks.

### Publish Configuration

```bash
php artisan vendor:publish --tag=glamstack-google
```

#### Related SDK Packages

This SDK provides authentication to be able to use the generic [Laravel HTTP Client](https://laravel.com/docs/8.x/http-client) with any endpoint that found in the [Google API Explorer](https://developers.google.com/apis-explorer).

We have created additional packages that provide defined methods for common service endpoints that we use within GitLab IT that you can use instead.

* [google-workspace-sdk](https://gitlab.com/gitlab-com/business-technology/engineering/access-manager/packages/composer/google-workspace-sdk)
* [google-cloud-sdk](https://gitlab.com/gitlab-com/business-technology/engineering/access-manager/packages/composer/google-cloud-sdk)

## Environment Configuration

### JSON API Key Storage

By default the SDK will load the Google Service Account JSON File from the `storage/keys/glamstack-google/{connection_key}.json`. With the default connection key of `workspace`, this will be `workspace.json`.

1. Create the `storage/keys/glamstack-google/` directory in your Laravel application.

2. Add `/storage/keys/` to the `.gitignore` file in the top level of your application directory. This ensures that your JSON key is not accidentally committed to your code repository.

3. After creating your service account key in Google and downloading the JSON file, you should rename the file to `{connection_key}.json` to match the array key specified in `config/glamstack-google.php` and move it to the `storage/keys/glamstack-google` directory.

4. Be sure to update the [API Scopes](#api-scopes) based on what you have granted your service account access to. A mismatch in scoped permissions will cause unexpected errors when using the SDK.

5. Repeat steps 3 and 4 for each of the other connection keys that you have configured in `config/glamstack-google.php`.

### Connection Keys

We use the concept of **_connection keys_** that refer to a configuration array of variables that are configured in conjunction with each of the JSON API keys that you use to connect to the Google API.

You can configure your connections in `config/glamstack-google-config.php`. The configuration for all Google related SDK's will be set inside of this configuration file.

See the documentation in the config file to learn more about configuration parameters for each of the common services (ex. Google Workspace, Google Cloud Platform, etc).

```php
'connections' => [

    'workspace' => [
        'api_scopes' => [
            'https://www.googleapis.com/auth/admin.directory.user',
            'https://www.googleapis.com/auth/admin.directory.group',
            'https://www.googleapis.com/auth/admin.directory.group.member',
            'https://www.googleapis.com/auth/admin.directory.orgunit',
        ],
        'customer_id' => env('GOOGLE_WORKSPACE_CUSTOMER_ID'),
        'domain' => env('GOOGLE_WORKSPACE_DOMAIN'),
        'email' => env('GOOGLE_AUTH_WORKSPACE_EMAIL'),
        'log_channels' => ['single']
    ],

    'gcp_project_1' => [
        'api_scopes' => [
            'https://www.googleapis.com/auth/cloud-platform',
        ],
        'log_channels' => ['single']
    ]

]
```

#### API Scopes

Each connection key array has `api_scopes` that provide a list of [Google API scopes](https://developers.google.com/identity/protocols/oauth2/scopes) that your JSON API key has access to. See the documentation in the `config/glamstack-google-config.php` file for examples of common scopes that you can copy and paste to your connections depending on what has been configured for your JSON API key. The default configuration includes common scopes that are commented out and can simply be uncommented to use them. You can add additional API scopes to the respective array as needed.

If you're just getting started with using the SDK, you should not need to make many changes to this file except for commenting or uncommenting the scopes that you've granted to your JSON API key.

You can learn more about the Authorization Scopes required by referencing the [Google API Explorer](https://developers.google.com/apis-explorer) documentation for the specific REST endpoint.

#### Default Global Connection

By default, the SDK will use the `workspace` connection key for all API calls across your application unless you change the default connection to a different **_connection key_** defined in the `config/glamstack-google-config.php` file.

You can optionally add the `GOOGLE_AUTH_DEFAULT_CONNECTION` variable to your `.env` file and set the value to the **_connection key_** that you want to use as the default.

```bash
GOOGLE_AUTH_DEFAULT_CONNECTION="my_connection_key"
```

To use the default connection, you do **_not_** need to provide the **_connection key_** to the `AuthClient`.

```php
// Initialize the SDK
$google_auth = new \Glamstack\GoogleAuth\AuthClient();

// Send Auth Request
$api_token = $google_auth->authenticate();
```

#### Using Pre-Configured Connections

If you want to use a specific **_connection key_** when using the `AuthClient` that is different from the `GOOGLE_AUTH_DEFAULT_CONNECTION` global variable, you can pass the **_connection key_** that has been configured in `config/glamstack-google-config.php` as the first construct argument for the `AuthClient`.

```php
// Initialize the SDK
$google_auth = new \Glamstack\GoogleAuth\AuthClient('my_connection_key');

// Send Auth Request
$api_token = $google_auth->authenticate();
```

> If you encounter errors, ensure that the `storage/keys/glamstack-google/{my_connection_key}.json` file exists and verify your scopes are configured correctly.

#### Custom Non-Configured Connections

If you want to connect to a Google API service that you have not pre-configured in `config/glamstack-google-config.php`, you will need to provide an array of scopes and the path to the JSON key to use when initializing the `AuthClient`.

```
// Define scopes array for custom connection
$scopes = [
    'https://www.googleapis.com/auth/cloud-platform',
    'https://www.googleapis.com/auth/compute'
];

// Define file path for JSON key
// https://laravel.com/docs/8.x/helpers#method-storage-path
$json_file_path = storage_path('storage/keys/glamstack-google/my_custom_key.json');

// Not Officially Supported. Use at your own risk.
// If your JSON key is stored in the file system outside of the Laravel application,
// you can use the full path to the file. You may need to adjust permissions based
// on the system user or service that your Laravel application runs with.
// $json_file_path = '/etc/gcloud-keys/my_custom_key.json';

// Initialize Google Auth Client
$google_auth = new \Glamstack\GoogleAuth\AuthClient(null, $scopes, $json_file_path);

// Send Auth Request
$api_token = $google_auth->authenticate();
```

## Google Workspace API Connections

#### Subject Email

> This variable is only used by Google Workspace API and other services that use [Domain-Wide Delegation](https://developers.google.com/admin-sdk/directory/v1/guides/delegation). If you are only using the SDK for Google Cloud API services, you do not need to add this variable to your `.env` file.

By default the `client_email` field will be used as the 'Subject Email'. However, if you are utilizing this SDK to authenticate with any Google Endpoints that require [Domain-Wide Delegation](https://developers.google.com/admin-sdk/directory/v1/guides/delegation) then you will have to add the following variable to your `.env` file.

This email address is that of a user account in Google Workspace that contains the appropriate Administrative rights for the APIs that will be utilized. When developing or testing applications, this can be the email address of the developer or test account.

```bash
GOOGLE_AUTH_WORKSPACE_EMAIL="dmurphy@example.com"
```

When running in production, this should be the email address of a bot service account that you have created as a Google Workspace user that has permissions scoped to the automation that your application provides.

```bash
GOOGLE_AUTH_WORKSPACE_EMAIL="my-production-app-service-account@example.com"
```

#### Domain

The Google Domain is utilized with Google Workspace API Request. Add the following variable to your `.env` file to tell Google which domain the API should be used on.

```bash
GOOGLE_WORKSPACE_DOMAIN="example.com"
```

#### Customer ID

The [Google Customer ID](https://support.google.com/a/answer/10070793?product_name=UnuFlow&hl=en&visit_id=637788489425453961-1161888327&rd=1&src=supportwidget0&hl=en) is required to run Google Workspace API request against your company's account. Add the following variable to your `.env` file.

```bash
GOOGLE_WORKSPACE_CUSTOMER_ID="C12345678"
```

## Google Cloud Platform API Connections

TODO: This is a placeholder for documentation after we have implemented and tested the GCP API endpoints.

## Logging Configuration

By default, we use the `single` channel for all logs that is pre-configured in your application's `config/logging.php` file. This sends all Google Auth log messages to the `storage/logs/laravel.log` file.

You can configure the log channels for this SDK in `config/glamstack-google-config.php`. You can configure the log channels for the AuthClient in `auth.log_channels`. You can also configure the log channels for each of your connections in `connections.{connection_key}.log_channels`.

```php
// config/glamstack-google-config.php

   'auth' => [
        'log_channels' => ['single'],
    ],

    'connections' => [
        'workspace' => [
            // ...
            'log_channels' => ['single'],
        ],
        'gcp_project_1' => [
            // ...
            'log_channels' => ['single'],
        ],
    ]
```

### Creating a Log Channel

If you would like to see Google Auth logs in a separate log file that is easier to triage without unrelated log messages, you can create a custom log channel. For example, you can have all `AuthClient` logs sent to a new log channel named `glamstack-google-auth` (or any name you would like).

Add the custom log channel to `config/logging.php`.

```php
// config/logging.php

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
# config/logging.php

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single','slack', 'glamstack-google-auth'],
            'ignore_exceptions' => false,
        ],
    ],
```

Finally, update the `config/glamstack-google-config.php` configuration.

```php
// config/glamstack-google-config.php

   'auth' => [
        'log_channels' => ['glamstack-google-auth'],
    ],
```

You can repeat these configuration steps to customize any of your connection keys.

## Security Best Practices

### Google API Scopes

The default configuration file loaded with the package shows an example of the API scope configuration. Be sure to follow the [Principle of Least Privilege](https://www.cisa.gov/uscert/bsi/articles/knowledge/principles/least-privilege). All of the Google Scopes can be found [here](https://developers.google.com/identity/protocols/oauth2/scopes).

You can learn more about the Authorization Scopes required by referencing the [Google API Explorer](https://developers.google.com/apis-explorer) documentation for the specific REST endpoint.

### JSON Key Storage

Do not store your JSON key file anywhere that is not included in the `.gitignore` file. This is to avoid committing your credentials to your repository (secret leak)

It is a recommended to store a copy of each JSON API key in your preferred password manager (ex. 1Password, LastPass, etc.) and/or secrets vault (ex. HashiCorp Vault, Ansible, etc.).

## Log Outputs

> The output of error messages is shown in the `README` to allow search engines to index these messages for developer debugging support. Any 5xx error messages will be returned as as `Symfony\Component\HttpKernel\Exception\HttpException` or configuration errors, including any errors in the `__construct()` method.

```php
$google_auth = new \Glamstack\GoogleAuth\AuthClient('workspace');
$api_token = $google_auth->authenticate();
```

#### Valid JSON API Key

```json
[2022-02-01 02:15:01] local.INFO: POST 200 https://oauth2.googleapis.com/token {"api_endpoint":"https://oauth2.googleapis.com/token","api_method":"POST","class":"Glamstack\\GoogleAuth\\AuthClient","connection_key":"workspace","event_type":"google-auth-api-response-info","message":"POST 200 https://oauth2.googleapis.com/token","status_code":200}
```

#### Invalid JSON API Key

```json
[2022-02-01 02:12:51] local.NOTICE: POST 400 https://oauth2.googleapis.com/token {"api_endpoint":"https://oauth2.googleapis.com/token","api_method":"POST","class":"Glamstack\\GoogleAuth\\AuthClient","connection_key":"workspace","event_type":"google-auth-api-response-client-error","google_error_type":"invalid_grant","google_error_description":"Invalid JWT Signature.","message":"POST 400 https://oauth2.googleapis.com/token","status_code":400}
```

#### Missing Connection Key

```json
[2022-02-01 02:21:02] local.CRITICAL: The Google connection key is not defined in `config/glamstack-google.php` connections array. Without this array config, there is no API configuration to connect with. {"event_type":"google-api-config-missing-error","class":"Glamstack\\GoogleAuth\\AuthClient","status_code":"501","message":"The Google connection key is not defined in `config/glamstack-google.php` connections array. Without this array config, there is no API configuration to connect with.","connection_key":"workspace2"}
```

#### Invalid or Mismatched API Scopes

```json
[2022-02-01 02:22:59] local.NOTICE: POST 400 https://oauth2.googleapis.com/token {"api_endpoint":"https://oauth2.googleapis.com/token","api_method":"POST","class":"Glamstack\\GoogleAuth\\AuthClient","connection_key":"workspace","event_type":"google-auth-api-response-client-error","google_error_type":"invalid_scope","google_error_description":"Invalid OAuth scope or ID token audience provided.","message":"POST 400 https://oauth2.googleapis.com/token","status_code":400}
```

## Issue Tracking and Bug Reports

Please visit our [issue tracker](https://gitlab.com/gitlab-com/business-technology/engineering/access-manager/packages/composer/google-auth-sdk/-/issues) and create an issue or comment on an existing issue.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) to learn more about how to contribute.-
