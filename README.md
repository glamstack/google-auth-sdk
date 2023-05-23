# Google Auth SDK

## Overview

The Google Auth SDK is an open source [Composer](https://getcomposer.org/) package created by [GitLab IT Engineering](https://about.gitlab.com/handbook/it/engineering/dev/) for use in internal Laravel applications for generating API tokens using Google JSON credentials that can be use for REST API endpoints when using the related `gitlab-it/google-*` SDK packages for each respective Google service.

> **Disclaimer:** This is not an official package maintained by the GitLab or Google product and development teams. This is an internal tool that we use in the GitLab IT department that we have open sourced as part of our company values.
>
> Please use at your own risk and create merge requests for any bugs that you encounter.
>
> We do not maintain a roadmap of community feature requests, however we invite you to contribute and we will gladly review your merge requests.

### v2 to v3 Upgrade Guide

There are several breaking changes with v3.0. See the [v3.0 changelog](changelog/3.0.md) to learn more.

#### What's Changed

- The v3.0 release does not add any features and is focused on namespace breaking changes.
- The `glamstack/google-auth-sdk` has been abandoned and has been renamed to `gitlab-it/google-auth-sdk`.
- The namespace changed from `Glamstack\GoogleAuth` to `GitlabIt\GoogleAuth`.
- Changed from a modified version of [Calendar Versioning (CalVer)](https://calver.org/) to using [Semantic Versioning (SemVer)](https://semver.org/).
- License changed from `Apache 2.0` to `MIT`

#### Migration Steps

> This package is included as a dependency in `gitlab-it/google-*` SDK packages. These steps are only necessary if `glamstack/google-auth-sdk` exists in your `composer.json` file. Otherwise, simply update the `gitlab-it/google-*` SDK packages to `^3.0`.

1. Remove `glamstack/google-auth-sdk` from `composer.json` and add `"gitlab-it/google-auth-sdk": "^3.0"`, then run `composer update`.
2. Perform a find and replace across your code base from `Glamstack\GoogleAuth` to `GitlabIt\GoogleAuth`.

### Maintainers

| Name                                                                   | GitLab Handle                                          |
| ---------------------------------------------------------------------- | ------------------------------------------------------ |
| [Dillon Wheeler](https://about.gitlab.com/company/team/#dillonwheeler) | [@dillonwheeler](https://gitlab.com/dillonwheeler)     |
| [Jeff Martin](https://about.gitlab.com/company/team/#jeffersonmartin)  | [@jeffersonmartin](https://gitlab.com/jeffersonmartin) |

### How It Works

This package is used to authenticate with the Google OAuth2 Sever utilizing a [Google Service Account](https://cloud.google.com/iam/docs/service-accounts) **JSON API key**.

The OAUTH service will return a **short-lived API token** that can be used with the [Laravel HTTP Client](https://laravel.com/docs/8.x/http-client) to perform `GET`, `POST`, `PATCH`, `DELETE`, etc. API requests that can be found in the [Google API Explorer](https://developers.google.com/apis-explorer) documentation.

To provide a streamlined developer experience, this SDK will either take a `file_path` parameter that points to your JSON API key's storage path, or you can provide the JSON API key as a string in the `json_key` parameter.

This SDK does not utilize any `.env` files or configuration files, rather those should be configured in the application calling this SDK.

### SDK Initialization

#### api_scopes

Authentication will fail if the api scopes requested is not configured for the Google Account.

You can learn more about the Authorization Scopes required by referencing the [Google API Explorer](https://developers.google.com/apis-explorer) documentation for the specific REST endpoint.

```php
// Option 1
$google_auth = new \Gitlabit\GoogleAuth\AuthClient([
    // ...
    'api_scopes' => ['https://www.googleapis.com/auth/admin.directory.user'],
]);
```

```php
// Option 2
$api_scopes = [
    https://www.googleapis.com/auth/cloud-platform
    https://www.googleapis.com/auth/cloudplatformprojects
];

$google_auth = new \Gitlabit\GoogleAuth\AuthClient([
    // ...
    'api_scopes' => $api_scopes,
]);
```

#### file_path

> You can provide either the `file_path` or the `json_key` as a string.

```php
$google_auth = new \Gitlabit\GoogleAuth\AuthClient([
    // ...
    'file_path' => storage_path('keys/google_json_api_key.json'),
]);
```

#### json_key

> You can provide either the `file_path` or the `json_key` as a string.

**Security Warning:** You should never commit your service account key into your source code as a variable to avoid compromising your credentials for your GCP organization or projects.

```php
// Get service account from your model (`GoogleServiceAccount` is an example)
$service_account = \App\Models\GoogleServiceAccount::where('id', '123456')->firstOrFail();

// Get JSON key string from database column that has an encrypted value
$json_key_string = decrypt($service_account->json_key);

$google_auth = new \Gitlabit\GoogleAuth\AuthClient([
    // ...
    'json_key' => $json_key_string,
]);
```

#### subject_email

> This is an optional key.

This is only used by Google Workspace API and other services that use [Domain-Wide Delegation](https://developers.google.com/admin-sdk/directory/v1/guides/delegation). If you are only using the SDK for Google Cloud API services, you do not need to include this variable during initialization.

By default the `client_email` field will be used as the `Subject Email`. However, if you are utilizing this SDK to authenticate with any Google Endpoints that require [Domain-Wide Delegation](https://developers.google.com/admin-sdk/directory/v1/guides/delegation) then you will have to add the `subject_email` key during initialization.

This email address is that of a user account in Google Workspace that contains the appropriate Administrative rights for the APIs that will be utilized. When developing or testing applications, this can be the email address of the developer or test account.

When running in production, this should be the email address of a bot service account that you have created as a Google Workspace user that has permissions scoped to the automation that your application provides.

```php
$google_auth = new \Gitlabit\GoogleAuth\AuthClient([
    // ...
    'subject_email' => 'klibby@example.com'
]);
```

### Inline Usage

```php
// Initialize the SDK using a JSON API key file
$google_auth = new \Gitlabit\GoogleAuth\AuthClient([
    'api_scopes' => ['https://www.googleapis.com/auth/admin.directory.user'],
    'file_path' => storage_path('keys/google_json_api_key.json'),
]);

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

use Gitlabit\GoogleAuth\AuthClient;

class GoogleWorkspaceUserService
{
    protected $auth_token;

    public function __construct()
    {
        $google_auth = new \Gitlabit\GoogleAuth\AuthClient([
            'api_scopes' => ['https://www.googleapis.com/auth/admin.directory.user'],
            'file_path' => storage_path('keys/google_json_api_key.json'),
        ]);
        $this->auth_token = $google_auth->authenticate();
    }

    public function getUser($user_key)
    {
        $response = Http::withToken($this->auth_token)
            ->get('https://admin.googleapis.com/admin/directory/v1/users/' . $user_key);

        return $response->object();
    }
}
```

## Installation

### Requirements

| Requirement | Version |
| ----------- |--------|
| PHP         | >=8.0  |
| Laravel     | >=9.0 || >=10.0  |

### Add Composer Package

> Still Using `glamstack/google-auth-sdk` (v2.x)? See the [v3.0 Upgrade Guide](#v2-to-v3-upgrade-guide) for instructions to upgrade to `gitlab-it/google-auth-sdk:^3.0`.

```
composer require gitlab-it/google-auth-sdk:^3.0
```

If you are contributing to this package, see [CONTRIBUTING.md](CONTRIBUTING.md) for instructions on configuring a local composer package with symlinks.

### Related SDK Packages

This SDK provides authentication to be able to use the generic [Laravel HTTP Client](https://laravel.com/docs/10.x/http-client) with any endpoint that can be found in the [Google API Explorer](https://developers.google.com/apis-explorer).

We have created additional packages that provide defined methods for some of the common service endpoints that GitLab IT uses if you don't want to specify the endpoints yourself.

* [google-workspace-sdk](https://gitlab.com/gitlab-it/google-workspace-sdk)
* [google-cloud-sdk](https://gitlab.com/gitlab-it/google-cloud-sdk)

## Logging Configuration

This package will not handle any logging configurations. Rather it will throw exceptions with applicable error messages. The logging of these should be handing via the calling application.

## Security Best Practices

### Google API Scopes

The default configuration file loaded with the package shows an example of the API scope configuration. Be sure to follow the [Principle of Least Privilege](https://www.cisa.gov/uscert/bsi/articles/knowledge/principles/least-privilege). All of the Google Scopes can be found [here](https://developers.google.com/identity/protocols/oauth2/scopes).

You can learn more about the Authorization Scopes required by referencing the [Google API Explorer](https://developers.google.com/apis-explorer) documentation for the specific REST endpoint.

### JSON Key Storage

Do not store your JSON key file anywhere that is not included in the `.gitignore` file. This is to avoid committing your credentials to your repository (secret leak)

It is a recommended to store a copy of each JSON API key in your preferred password manager (ex. 1Password, LastPass, etc.) and/or secrets vault (ex. HashiCorp Vault, Ansible, etc.).

## Exceptions

These are the list of expected exceptions.

#### Missing Required Array Parameter

```bash
Symfony\Component\OptionsResolver\Exception\MissingOptionsException : The required option "api_scopes" is missing.
```

#### No JSON API Key Parameter Set

```bash
Exception : You must specify either the file_path or json_key in the connection_config array.
```

#### Invalid JSON API Key

```bash
Exception : Google SDK Authentication Error. Invalid JWT Signature.
```

#### Invalid or Mismatched API Scopes

```bash
Exception : Invalid OAuth scope or ID token audience provided.
```

## Testing

There are both Unit and Feature test for this SDK. All unit test can be run out of the box, while feature test will require two keys to be loaded into the `tests/Storage/keys/` directory.

### Feature Test Key Names

1. `integration_test_key.json`
   - This should be a valid JSON key
   - Used to verify `authenticate` function will return a proper Google API OAuth Token
1. `incorrect_key.json`
   - This should **NOT** be a valid JSON key
   - Used to verify `authenticate` function will throw a proper exception message

## Issue Tracking and Bug Reports

> **Disclaimer:** This is not an official package maintained by the GitLab or Google product and development teams. This is an internal tool that we use in the GitLab IT department that we have open sourced as part of our company values.
>
> Please use at your own risk and create merge requests for any bugs that you encounter.
>
> We do not maintain a roadmap of community feature requests, however we invite you to contribute and we will gladly review your merge requests.

For GitLab team members, please create an issue in [gitlab-it/google-auth-sdk](https://gitlab.com/gitlab-it/google-auth-sdk/-/issues) (public) or [gitlab-com/it/dev/issue-tracker](https://gitlab.com/gitlab-com/it/dev/issue-tracker) (confidential).

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) to learn more about how to contribute.
