<?php

return [

    /**
     * ------------------------------------------------------------------------
     * Log Channels
     * ------------------------------------------------------------------------
     * Throughout the SDK, we use the config('glamstack-google-auth.log_channels')
     * array variable to allow you to set the log channels (custom log stack)
     * that you want API logs to be sent to.
     *
     * If you leave this at the value of `['single']`, all API call logs will
     * be sent to the default log file for Laravel that you have configured
     * in config/logging.php which is usually storage/logs/laravel.log.
     *
     * If you would like to see Google Workspace API logs in a separate log
     * file that is easier to triage without unrelated log messages, you can
     * create a custom log channel and add the channel name to the array. For
     * example, we recommend creating a custom channel with the name
     * `glamstack-google-auth`, however you can choose any name you would
     * like.
     * Ex. ['single', 'glamstack-google-auth']
     *
     * You can also add additional channels that logs should be sent to.
     * Ex. ['single', 'glamstack-google-auth', 'slack']
     *
     * https://laravel.com/docs/8.x/logging
     */

    'log_channels' => ['single'],

    /**
     * ------------------------------------------------------------------------
     * Google Auth Configuration
     * ------------------------------------------------------------------------
     * In order to allow for least privilege access and multiple tokens the
     * SDK uses this configuration section to configure the API Scopes for
     * each token, as well as any other optional configurations that are
     * needed for any specific Google API endpoints
     *
     * Ex. You can configure the Subject Email for a Workspace token that
     * allows for API's that require
     * [Domain-Wide Delegation of Authority](https://developers.google.com/admin-sdk/directory/v1/guides/delegation)
     *
     * The top level of the configuration i.e `workspace` will be the
     * instance_key when initializing the SDK and will be used to determine
     * the filepath of the JSON token as well.
     *
     * Ex.
     * ```php
     * $google_auth = new \Glamstack\GoogleAuth\AuthClient('workspace');
     * ```
     * Will search for the Google JSON file under
     * `storage/keys/google-auth-sdk/workspace.json`
     *
     * By default the SDK will set the instance_key to `workspace`
     */
    'instance' => env('GOOGLE_AUTH_INSTANCE', 'workspace'),
    'workspace' => [
        'api_scopes' => [
            'https://www.googleapis.com/auth/admin.directory.user',
        ],
        'email' => env('GOOGLE_AUTH_WORKSPACE_EMAIL'),
    ],
    'gcp_project_1' => [
        'api_scopes' => env('GOOGLE_AUTH_GCP_PROEJCT_1_API_SCOPES'),
    ]
];
