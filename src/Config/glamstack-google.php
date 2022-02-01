<?php

return [

    /**
     * ------------------------------------------------------------------------
     * Google Auth Configuration
     * ------------------------------------------------------------------------
     *
     * @param string $default_connection The connection key (array key) of the 
     *.     connection that you want to use if not specified when instantiating 
     *.     the AuthClient.
     * 
     * @param array $log_channels The Google Workspace log channels to send
     *      all related info and error logs to. If you leave this at the value
     *      of `['single']`, all API call logs will be sent to the default log
     *      file for Laravel that you have configured in config/logging.php
     *      which is usually storage/logs/laravel.log.
     *
     *      If you would like to see Google API logs in a separate log file
     *      that is easier to triage without unrelated log messages, you can
     *      create a custom log channel and add the channel name to the
     *      array. For example, we recommend creating a custom channel
     *      (ex. `glamstack-google-workspace`), however you can choose any
     *      name you would like.
     *      Ex. ['single', 'glamstack-google-example']
     *
     *      You can also add additional channels that logs should be sent to.
     *      Ex. ['single', 'glamstack-google-example', 'slack']
     *
     *      @see https://laravel.com/docs/8.x/logging
     */

    'auth' => [
        'default_connection' => env('GOOGLE_AUTH_DEFAULT_CONNECTION', 'workspace'),
        'log_channels' => ['single'],
    ],

    /**
     * ------------------------------------------------------------------------
     * Connections Configuration
     * ------------------------------------------------------------------------
     *
     * To allow for least privilege access and multiple API keys, the SDK uses
     * this configuration section for configuring each of the API keys that
     * you use and configuring the different API Scopes for each token, as well
     * as any other optional variables that are needed for any specific Google 
     * API endpoints.
     *
     * Each connection has an array key that we refer to as the "connection key"
     * that contains a array of configuration values and is used when the SDK
     * for the respective service ApiClient is instantiated.
     *
     * ```php
     * $google_auth = new \Glamstack\GoogleWorkspace\ApiClient('workspace');
     * ```
     *
     * You can add the `GOOGLE_AUTH_DEFAULT_CONNECTION` variable in your .env
     * file so you don't need to pass the connection key into the ApiClient.
     * The `workspace` connection key is used if the `.env` variable is not set.
     *
     * ```php
     * $google_auth = new \Glamstack\GoogleWorkspace\ApiClient();
     * ```
     *
     * The JSON API key file that you generate and download should be added to
     * your locally cloned repository in the `storage/keys/google-auth/sdk`
     * directory with the filename that matches the connection key.
     * `storage/keys/google-auth-sdk/workspace.json`
     *
     * On your production web/app server, this should be added to
     * this directory using infrastructure-as-code automation (ex. Ansible).
     *
     * You should never commit this JSON file to your Git repository since this
     * exposes your credentials, and the `storage/keys/` directory must be
     * added to your `.gitignore` file.
     *
     * By default the SDK will use configuration for the connection_key
     * `workspace`, unless you override this in your `.env` file using
     * the `GOOGLE_AUTH_DEFAULT_CONNECTION` variable, or pass the connection
     * key as an argument when using the `ApiClient`.
     *
     * The list of OAUTH scopes for Google APIs can be found in the docs.
     * See the `README.md` for more instructions and security practices
     * for using scopes with your service account JSON keys.
     * 
     * @see https://developers.google.com/identity/protocols/oauth2/scopes
     */

    'connections' => [

        /**
         * --------------------------------------------------------------------
         * Google Workspace
         * --------------------------------------------------------------------
         *
         * @param string $subject_email The email address that will be used to
         *      impersonate within Google workspace.
         *
         * @param string $domain The Google Domain the Google Workspace APIs
         *      will be used on.
         *
         * @param string $customer_id The Google Customer ID to use with the
         *      API calls.
         *
         * @param array $api_scopes The API scopes that will be needed for the
         *      workspace APIs that will be called.
         *
         *      Example Scopes:
         *      ```php
         *      [
         *          'https://www.googleapis.com/auth/admin.directory.user',
         *          'https://www.googleapis.com/auth/admin.directory.group',
         *          'https://www.googleapis.com/auth/admin.directory.group.member',
         *          'https://www.googleapis.com/auth/admin.directory.orgunit',
         *          'https://www.googleapis.com/auth/drive',
         *          'https://www.googleapis.com/auth/spreadsheets',
         *          'https://www.googleapis.com/auth/presentations',
         *          'https://www.googleapis.com/auth/apps.groups.settings',
         *          'https://www.googleapis.com/auth/admin.reports.audit.readonly',
         *          'https://www.googleapis.com/auth/admin.reports.usage.readonly'
         *      ]
         *      ```
         *
         * @param array $log_channels The Google Workspace log channels to send
         *      all related info and error logs to. If you leave this at the
         *      value of `['single']`, all API call logs will be sent to the
         *      default log file for Laravel that you have configured in
         *      `config/logging.php` which logs to `storage/logs/laravel.log`.
         *
         *      If you would like to see Google API logs in a separate log file
         *      that is easier to triage without unrelated log messages, you can
         *      create a custom log channel and add the channel name to the
         *      array. For example, we recommend creating a custom channel
         *      (ex. `glamstack-google-workspace`), however you can choose any
         *      name you would like.
         *      Ex. ['single', 'glamstack-google-example']
         *
         *      You can also add additional channels that logs should be sent to.
         *      Ex. ['single', 'glamstack-google-example', 'slack']
         *
         *      @see https://laravel.com/docs/8.x/logging
         */

        'workspace' => [
            'api_scopes' => [
                'https://www.googleapis.com/auth/admin.directory.user',
            ],
            'customer_id' => env('GOOGLE_WORKSPACE_CUSTOMER_ID'),
            'domain' => env('GOOGLE_WORKSPACE_DOMAIN'),
            'email' => env('GOOGLE_AUTH_WORKSPACE_EMAIL'),
            'log_channels' => ['single']
        ],

        /**
         * --------------------------------------------------------------------
         * Google Cloud Platform
         * --------------------------------------------------------------------
         *
         * TODO: Documentation needs to be updated for Cloud APIs when building
         * out the SDK for GCP.
         *
         */

        'gcp_project_1' => [
            'api_scopes' => [
                'https://www.googleapis.com/auth/cloud-platform',
                //'https://www.googleapis.com/auth/compute',
                //'https://www.googleapis.com/auth/ndev.clouddns.readwrite',
                //'https://www.googleapis.com/auth/cloud-billing',
                //'https://www.googleapis.com/auth/monitoring.read',
            ],
            'log_channels' => ['single']
        ]

    ]
];
