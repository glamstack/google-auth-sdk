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
     * If you would like to see Google API logs in a separate log file that 
     * is easier to triage without unrelated log messages, you can create a 
     * custom log channel and add the channel name to the array. For example, 
     * we recommend creating a custom channel (ex. `glamstack-google-auth`), 
     * however you can choose any name you would like.
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
     * In order to allow for least privilege access and multiple tokens, the
     * SDK uses this configuration section to configure the API Scopes for
     * each token, as well as any other optional configurations that are
     * needed for any specific Google API endpoints.
     *
     * Ex. You can configure the Subject Email for a Workspace token that
     * allows for API's that require [Domain-Wide Delegation of Authority](https://developers.google.com/admin-sdk/directory/v1/guides/delegation).
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
     * By default the SDK will use configuration for the instance_key 
     * `workspace`, unless you override this in your `.env` file using 
     * the `GOOGLE_AUTH_INSTANCE` variable, or pass the instance key 
     * as an argument when using the `ApiClient`.
     * 
     * The list of OAUTH scopes for Google APIs can be found in the docs. 
     * See the `README.md` for more instructions and security practices 
     * for using scopes with your service account JSON keys.
     * https://developers.google.com/identity/protocols/oauth2/scopes
     */
    'instance' => env('GOOGLE_AUTH_INSTANCE', 'workspace'),
    'workspace' => [
        'api_scopes' => [
            'https://www.googleapis.com/auth/admin.directory.user',
            //'https://www.googleapis.com/auth/admin.directory.group',
            //'https://www.googleapis.com/auth/admin.directory.group.member',
            //'https://www.googleapis.com/auth/admin.directory.orgunit',
            //'https://www.googleapis.com/auth/drive',
            //'https://www.googleapis.com/auth/spreadsheets',
            //'https://www.googleapis.com/auth/presentations',
            //'https://www.googleapis.com/auth/apps.groups.settings',
            //'https://www.googleapis.com/auth/admin.reports.audit.readonly',
            //'https://www.googleapis.com/auth/admin.reports.usage.readonly',
        ],
        'email' => env('GOOGLE_AUTH_WORKSPACE_EMAIL'),
    ],
    'gcp_project_1' => [
        'api_scopes' => [
            'https://www.googleapis.com/auth/cloud-platform',
            //'https://www.googleapis.com/auth/compute',
            //'https://www.googleapis.com/auth/ndev.clouddns.readwrite',
            //'https://www.googleapis.com/auth/cloud-billing',
            //'https://www.googleapis.com/auth/monitoring.read',
        ],
    ]
];
