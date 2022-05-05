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

This package is used to authenticate with the Google OAuth2 Sever utilizing a [Google Service Account](https://cloud.google.com/iam/docs/service-accounts) **JSON API key**.

The OAUTH service will return a **short-lived API token** that can be used with the [Laravel HTTP Client](https://laravel.com/docs/8.x/http-client) to perform `GET`, `POST`, `PATCH`, `DELETE`, etc. API requests that can be found in the [Google API Explorer](https://developers.google.com/apis-explorer) documentation.

To provide a streamlined developer experience, this SDK will either take a `file_path` parameter that points to your JSON API key's storage path, or you can provide the JSON API key as a string in the `json_key` parameter. 

This SDK does not utilize any `.env` files or configuration files, rather those should be configured in the application calling this SDK.

### SDK Initialization

#### Required Array Keys

When initializing the SDK the following are required to be set:
1. `api_scopes`
    - Array of strings
    - The api scopes that will be used by the auth token
    - Can contain multiple scopes
    - See [API Scopes](#api-scopes) for more details

1. `file_path` or `json_key`
    - The SDK will require one of them to be set

#### Optional Array Keys

1. `subject_email`
    - Subject email is generally only used for Google Workspace API calls
    - See [Subject Email](#subject-email) for more details

### Inline Usage

#### Utilizing the `file_path` To Load The JSON API Key File 

```php
// Initialize the SDK using a JSON API key file
$google_auth = new \Glamstack\GoogleAuth\AuthClient([
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

#### Utilizing The `json_key` To Load The JSON API Key String

```php
// Initialize the SDK using a JSON API key file
$json_key_string = '{
  "type": "service_account",
  "project_id": "project_id",
  "private_key_id": "key_id",
  "private_key": "key_data",
  "client_email": "example@example.iam.gserviceaccount.com",
  "client_id": "1234567890",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token",
  "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
  "client_x509_cert_url": "fake_client_cert"
}'

$google_auth = new \Glamstack\GoogleAuth\AuthClient([
    'api_scopes' => ['https://www.googleapis.com/auth/admin.directory.user'],
    'json_key' => $json_key_string,
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

use Glamstack\GoogleAuth\AuthClient;

class GoogleWorkspaceUserService
{
    protected $auth_token;

    public function __construct()
    {
        $google_auth = new \Glamstack\GoogleAuth\AuthClient([
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
| Laravel     | >=9.0  |

### Add Composer Package

This package uses [Calendar Versioning](#calendar-versioning).

We recommend always using a specific version in your `composer.json` file and reviewing the [changelog](changelog/) to see the breaking changes in each release before assuming that the latest release is the right choice for your project.


```bash
composer require glamstack/google-auth-sdk:2.2.1
```

> If you are contributing to this package, see [CONTRIBUTING](CONTRIBUTING.md) for instructions on configuring a local composer package with symlinks.

### Related SDK Packages

This SDK provides authentication to be able to use the generic [Laravel HTTP Client](https://laravel.com/docs/8.x/http-client) with any endpoint that can be found in the [Google API Explorer](https://developers.google.com/apis-explorer).

We have created additional packages that provide defined methods for some of the common service endpoints that GitLab IT uses if you don't want to specify the endpoints yourself.

* [google-workspace-sdk](https://gitlab.com/gitlab-com/business-technology/engineering/access-manager/packages/composer/google-workspace-sdk)
* [google-cloud-sdk](https://gitlab.com/gitlab-com/business-technology/engineering/access-manager/packages/composer/google-cloud-sdk)

### Calendar Versioning

The GitLab IT Engineering team uses a modified version of [Calendar Versioning (CalVer)](https://calver.org/) instead of [Semantic Versioning (SemVer)](https://semver.org/). CalVer has a YY (Ex. 2021 => 21) but having a version `21.xx` feels unintuitive to us. Since our team started this in 2021, we decided to use the last integer of the year only (2021 => 1.x, 2022 => 2.x, etc).

The version number represents the release date in `vY.M.D` format.

#### Why We Don't Use Semantic Versioning

1. We are continuously shipping to `main`/`master`/`production` and make breaking changes in most releases, so having semantic backwards-compatible version numbers is unintuitive for us.
1. We don't like to debate what to call our release/milestone and whether it's a major, minor, or patch release. We simply write code, write a changelog, and ship it on the day that it's done. The changelog publication date becomes the tagged version number (Ex. `2022-02-01` is `v2.2.1`). We may refer to a bigger version number for larger releases (Ex. `v2.2`), however this is only for monthly milestone planning and canonical purposes only. All code tags include the day of release (Ex. `v2.2.1`).
1. This allows us to automate using GitLab CI/CD to automate the version tagging process based on the date the pipeline job runs.
1. We update each of our project `composer.json` files that use this package to specific or new version numbers during scheduled change windows without worrying about differences and/or breaking changes with "staying up to date with the latest version". We don't maintain any forks or divergent branches.
1. Our packages use underlying packages in your existing Laravel application, so keeping your Laravel application version up-to-date addresses most security concerns.

#### API Scopes

Authentication will fail if the api scopes requested is not configured for the Google Account.

You can learn more about the Authorization Scopes required by referencing the [Google API Explorer](https://developers.google.com/apis-explorer) documentation for the specific REST endpoint.

## Google Workspace API Connections

#### Subject Email

> This variable is only used by Google Workspace API and other services that use [Domain-Wide Delegation](https://developers.google.com/admin-sdk/directory/v1/guides/delegation). If you are only using the SDK for Google Cloud API services, you do not need to include this variable during initialization.

By default the `client_email` field will be used as the 'Subject Email'. However, if you are utilizing this SDK to authenticate with any Google Endpoints that require [Domain-Wide Delegation](https://developers.google.com/admin-sdk/directory/v1/guides/delegation) then you will have to add the `subject_email` key during initialization.

This email address is that of a user account in Google Workspace that contains the appropriate Administrative rights for the APIs that will be utilized. When developing or testing applications, this can be the email address of the developer or test account.

When running in production, this should be the email address of a bot service account that you have created as a Google Workspace user that has permissions scoped to the automation that your application provides.

## Google Cloud Platform API Connections

TODO: This is a placeholder for documentation after we have implemented and tested the GCP API endpoints.

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

#### Missing Required Array Parameter

Expected Exception

```bash
Symfony\Component\OptionsResolver\Exception\MissingOptionsException : The required option "api_scopes" is missing.
```

#### No JSON API Key Parameter Set

Expected Exception

```bash
Exception : You must specify either the file_path or json_key in the connection_config array.
```

#### Invalid JSON API Key

Expected Exception

```bash
Exception : Google SDK Authentication Error. Invalid JWT Signature.
```

#### Invalid or Mismatched API Scopes

Expected Exception

```bash
Exception : Invalid OAuth scope or ID token audience provided.
```

## Test Suite

There are both Unit and Feature test for this SDK. All unit test can be run out of the box, while feature test will require two keys to be loaded into the `tests/Storage/keys/` directory.

### Feature Test Key Names
1. `integration_test_key.json`
   - This should be a valid JSON key
   - Used to verify `authenticate` function will return a proper Google API OAuth Token
1. `incorrect_key.json`
   - This should **NOT** be a valid JSON key
   - Used to verify `authenticate` function will throw a proper exception message

## Issue Tracking and Bug Reports

Please visit our [issue tracker](https://gitlab.com/gitlab-com/business-technology/engineering/access-manager/packages/composer/google-auth-sdk/-/issues) and create an issue or comment on an existing issue.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) to learn more about how to contribute.
