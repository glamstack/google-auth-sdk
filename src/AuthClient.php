<?php

namespace Glamstack\GoogleAuth;

use Glamstack\GoogleAuth\Traits\ResponseLog;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthClient
{
    use ResponseLog;

    // Standard parameters for building JWT request with Google OAuth Server.
    // They are put here for easy changing if necessary
    const AUTH_BASE_URL = 'https://oauth2.googleapis.com/token';
    const AUTH_ALGORITHM = 'RS256';
    const AUTH_TYPE = 'JWT';
    const AUTH_GRANT_TYPE = 'urn:ietf:params:oauth:grant-type:jwt-bearer';
    const ENCRYPT_METHOD = 'sha256';

    private string $access_token;
    private string $api_scopes;
    private string $client_email;
    private array $connection_config;
    private string $connection_key;
    private string $file_path;
    private string $jwt;
    private string $private_key;
    private string $subject_email;

    /**
     * This function takes care of configuring the JWT that is used for the
     * API request sent to Google's OAuth Servers.
     *
     * @see https://developers.google.com/identity/protocols/oauth2/service-account
     *
     * @param string $connection_key (Optional) The connection key to use from
     * the configuration file to set the appropriate Google Auth Settings.
     * Default: `workspace`
     *
     * @param array $api_scopes (Optional) The Google API Scopes that will be
     * used with the token
     *
     * @param string $file_path (Optional) The file path of the Google JSON key
     * used for Service Account authentication. This parameter should only be
     * used if you are storing your JSON key outside of the
     * `storage/keys/glamstack-google/` directory of your application
     */
    public function __construct(
        string $connection_key = null,
        array $api_scopes = [],
        string $file_path = null
    ) {
        // Set the class connection_key variable.
        $this->setConnectionKey($connection_key);

        // Set the class connection_configuration variable
        $this->setConnectionConfig();

        // Set the class api_scopes variable.
        $this->setApiScopes($api_scopes);

        // Set the class file_path variable
        $this->setFilePath($file_path);

        // Get the file contents from the Google JSON key
        $file_contents = $this->parseJsonFile($this->file_path);

        // Set the Google Authorization Parameters from the $file_contents
        $this->setAuthParameters($file_contents);

        // Set the Google Subject email
        $this->setSubjectEmail();

        // Create the encrypted JWT Headers
        $jwt_headers = $this->createJwtHeader();

        // Create the encrypted JWT Claim
        $jwt_claim = $this->createJwtClaim();

        // Create the signature to append to the JWT
        $signature = $this->createSignature($jwt_headers, $jwt_claim);

        // Set the class jwt variable to the Google OAuth2 required string
        $this->jwt = $jwt_headers.'.'.$jwt_claim.'.'.$signature;
    }

    /**
     * Set the connection_key class variable. The connection_key variable by default
     * will be set to `workspace`. This can be overridden when initializing the
     * SDK with a different connection key which is passed into this function to
     * set the class variable to the provided key.
     *
     * @param string $connection_key (Optional) The connection key to use from the
     * configuration file.
     *
     * @return void
     */
    protected function setConnectionKey(?string $connection_key) : void
    {
        if ($connection_key == null) {
            /** @phpstan-ignore-next-line */
            $this->connection_key = config('glamstack-google.auth.default_connection');
        } else {
            $this->connection_key = $connection_key;
        }
    }

    /**
     * Set the connection_config class property array
     *
     * Define an array in the class using the connection configuration in the
     * glamstack-google.php connections array. If connection key is not specified,
     * an error log will be created and a 501 abort error will be thrown.
     *
     * @return void
     */
    protected function setConnectionConfig(): void
    {
        if (array_key_exists($this->connection_key, config('glamstack-google.connections'))) {
            $this->connection_config = config('glamstack-google.connections.' . $this->connection_key);
        } else {
            $error_message = 'The Google connection key is not defined in ' .
                '`config/glamstack-google.php` connections array. Without this ' .
                'array config, there is no API configuration to connect with.';

            Log::stack((array) config('glamstack-google.auth.log_channels'))
                ->critical($error_message, [
                    'event_type' => 'google-api-config-missing-error',
                    'class' => get_class(),
                    'status_code' => '501',
                    'message' => $error_message,
                    'connection_key' => $this->connection_key,
                ]);

            abort(501, $error_message);
        }
    }

    /**
     * Set the API scopes for the Google Authentication API token. The scope
     * will default to the configuration file for the connection, and can be
     * overridden with the $api_scopes variable being set during initialization.
     *
     * @param ?array $api_scopes (Optional) API Scopes to be set. This will
     * override the configuration file API Scope settings.
     *
     * @return void
     */
    protected function setApiScopes(?array $api_scopes) : void
    {
        if (!$api_scopes) {
            $this->api_scopes = collect($this->connection_config['api_scopes'])
                ->implode(' ');
        } else {
            $this->api_scopes = collect($api_scopes)->implode(' ');
        }

        // If api_scopes array is empty, create a log entry and abort
        if (count(explode(" ", $this->api_scopes)) == 0) {
            $error_message = 'The Google API scopes array is empty in ' .
                '`config/glamstack-google.php` connections array. Without this ' .
                'array config, there are no valid scopes for making API calls ' .
                'to endpoints.';

            Log::stack((array) config('glamstack-google.auth.log_channels'))
                ->critical($error_message, [
                    'event_type' => 'google-api-config-missing-error',
                    'class' => get_class(),
                    'status_code' => '501',
                    'message' => $error_message,
                    'connection_key' => $this->connection_key,
                ]);

            abort(501, $error_message);
        }
    }

    /**
     * Set the class variable $file_path to either the provided $connection_key
     * configuration or the $file_path provided from class initialization.
     *
     * @param ?string $file_path The file path to set for the Google JSON token
     *
     * @return void
     */
    protected function setFilePath(?string $file_path)
    {
        if ($file_path == null) {
            $this->file_path = storage_path(
                'keys/glamstack-google/'. $this->connection_key . '.json'
            );
        } else {
            $this->file_path = $file_path;
        }

        // If file does not exist, create a log entry and abort
        if (file_exists($this->file_path) == false) {
            $error_message = 'The Google JSON API key for the connection key ' .
            'cannot be found in `storage/keys/glamstack-google/{key}.json`';

            Log::stack((array) config('glamstack-google.auth.log_channels'))
                ->critical($error_message, [
                    'event_type' => 'google-api-key-missing-error',
                    'class' => get_class(),
                    'status_code' => '501',
                    'message' => $error_message,
                    'connection_key' => $this->connection_key,
                ]);

            abort(501, $error_message);
        }
    }

    /**
     * Parse the Google JSON key
     *
     * @param string $file_path The file path of the Google JSON key
     *
     * @return object
     */
    protected function parseJsonFile(string $file_path) : object
    {
        $file_contents = (object) json_decode(
            (string) file_get_contents($file_path)
        );
        return $file_contents;
    }

    /**
     * Utilize the Google JSON key contents to set the class variables
     * `private_key` and `client_email`
     *
     * @param object $json_file_contents The json_decoded Google JSON key token
     *
     * @return void
     */
    protected function setAuthParameters(object $json_file_contents) : void
    {
        $this->private_key = $json_file_contents->private_key;
        $this->client_email = $json_file_contents->client_email;
    }

    /**
     * Check if the 'GOOGLE_SUBJECT_EMAIL' variable is set in `.env`. If it is
     * set the class variable `subject_email` to the environment variable.
     * If it is not set we will use the client_email from the JSON token.
     *
     * @return void
     */
    protected function setSubjectEmail() : void
    {
        if ($this->connection_config['email'] != null) {
            /** @phpstan-ignore-next-line */
            $this->subject_email = $this->connection_config['email'];
        } else {
            $this->subject_email = $this->client_email;
        }
    }

    /**
     * Create and encode the required JWT Headers for Google OAuth2
     * authentication
     *
     * @see https://developers.google.com/identity/protocols/oauth2/service-account#:~:text=Forming%20the%20JWT%20header
     *
     * @return string
     */
    protected function createJwtHeader()
    {
        $jwt_header = [
            'alg' => self::AUTH_ALGORITHM,
            'typ' => self::AUTH_TYPE,
        ];
        $encoded_jwt_header = $this->base64_url_encode(
            (string) json_encode($jwt_header)
        );
        return $encoded_jwt_header;
    }

    /**
     * Create and encode the required JWT Claims for Google OAuth2
     * authentication
     *
     * @see https://developers.google.com/identity/protocols/oauth2/service-account#:~:text=Forming%20the%20JWT%20claim%20set
     *
     * @return string
     */
    protected function createJwtClaim()
    {
        $jwt_claim = [
            'iss' => $this->client_email,
            'scope' => $this->api_scopes,
            'aud' => self::AUTH_BASE_URL,
            'exp' => time()+3600,
            'iat' => time(),
            'sub' => $this->subject_email
        ];
        $encoded_jwt_claim = $this->base64_url_encode(
            (string) json_encode($jwt_claim)
        );
        return $encoded_jwt_claim;
    }

    /**
     * Create a signature using JWT Header and Claim and the private_key from
     * the Google JSON key
     *
     * @see https://developers.google.com/identity/protocols/oauth2/service-account#:~:text=Computing%20the-,signature,-JSON%20Web%20Signature
     *
     * @see https://datatracker.ietf.org/doc/html/rfc7515
     *
     * @see https://www.php.net/manual/en/function.openssl-pkey-get-private.php
     *
     * @param string $jwt_header The JWT Header string required for Google
     * OAuth2 authentication
     *
     * @param string $jwt_claim The JWT Claim string required for Google OAuth2
     * authentication
     *
     * @return string
     */
    protected function createSignature(string $jwt_header, string $jwt_claim) : string
    {
        // Parse the private key and prepare it for use
        $key_id = openssl_pkey_get_private($this->private_key);

        // Create the open SSL Signature using the provided inputs and
        // encryption method
        openssl_sign(
            $jwt_header.'.'.$jwt_claim,
            $this->private_key,
            /** @phpstan-ignore-next-line */
            $key_id,
            self::ENCRYPT_METHOD
        );

        // Encode the private key
        $encoded_signature = $this->base64_url_encode($this->private_key);

        return $encoded_signature;
    }

    /**
     * Encoding schema utilized by Google OAuth2 Servers
     *
     * @see https://stackoverflow.com/a/65893524
     *
     * @param string $input The input string to encode
     *
     * @return string
     */
    protected function base64_url_encode(string $input) : string
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * Create and send the Google Authentication POST request
     *
     * @see https://developers.google.com/identity/protocols/oauth2/service-account#:~:text=Making%20the%20access%20token%20request
     *
     * $return object
     */
    protected function sendAuthRequest() : object
    {
        $request = Http::asForm()->post(
            self::AUTH_BASE_URL,
            [
                'grant_type' => self::AUTH_GRANT_TYPE,
                'assertion' => $this->jwt
            ]
        );

        $response = $this->parseApiResponse($request);

        $this->logResponse('post', self::AUTH_BASE_URL, $response);

        // If response was not successful, parse Google API response
        if ($response->status->successful == false) {
            if (property_exists($response->object, 'error')) {
                abort($response->status->code, 'Google SDK Authentication Error. ' . $response->object->error_description);
            } else {
                abort(500, 'The Google SDK authentication attempt failed due to an unknown reason in the sendAuthRequest method.');
            }
        }

        return $response;
    }

    /**
     * Send authentication request to the Google OAuth2 Server
     *
     * @return string
     */
    public function authenticate()
    {
        $this->access_token = $this->sendAuthRequest()->object->access_token;
        return $this->access_token;
    }

    /**
     * Convert API Response Headers to Object
     *
     * This method is called from the parseApiResponse method to prettify the
     * Guzzle Headers that are an array with nested array for each value, and
     * converts the single array values into strings and converts to an object for
     * easier and consistent accessibility with the parseApiResponse format.
     *
     * @param array $header_response
     * [
     *     "Date" => array:1 [
     *       0 => "Sun, 30 Jan 2022 01:18:14 GMT"
     *     ]
     *     "Content-Type" => array:1 [
     *       0 => "application/json"
     *     ]
     *     "Transfer-Encoding" => array:1 [
     *       0 => "chunked"
     *     ]
     *     "Connection" => array:1 [
     *       0 => "keep-alive"
     *     ]
     *     "Server" => array:1 [
     *       0 => "nginx"
     *     ]
     *     // ...
     * ]
     *
     * @return array
     * [
     *     "Date" => "Sun, 30 Jan 2022 01:11:44 GMT",
     *     "Content-Type" => "application/json",
     *     "Transfer-Encoding" => "chunked",
     *     "Connection" => "keep-alive",
     *     "Server" => "nginx",
     *     "Public-Key-Pins-Report-Only" => (truncated),
     *     "Vary" => "Accept-Encoding",
     *     "x-okta-request-id" => "A1b2C3D4e5@f6G7H8I9j0k1L2M3",
     *     "x-xss-protection" => "0",
     *     "p3p" => "CP="HONK"",
     *     "x-rate-limit-limit" => "1000",
     *     "x-rate-limit-remaining" => "998",
     *     "x-rate-limit-reset" => "1643505155",
     *     "cache-control" => "no-cache, no-store",
     *     "pragma" => "no-cache",
     *     "expires" => "0",
     *     "content-security-policy" => (truncated),
     *     "expect-ct" => "report-uri="https://oktaexpectct.report-uri.com/r/t/ct/reportOnly", max-age=0",
     *     "x-content-type-options" => "nosniff",
     *     "Strict-Transport-Security" => "max-age=315360000; includeSubDomains",
     *     "set-cookie" => (truncated)
     * ]
     */
    public function convertHeadersToArray(array $header_response): array
    {
        $headers = [];

        foreach ($header_response as $header_key => $header_value) {
            // If array has multiple keys, leave as array
            if (count($header_value) > 1) {
                $headers[$header_key] = $header_value;

            // If array has a single key, convert to a string
            } else {
                $headers[$header_key] = $header_value[0];
            }
        }

        return $headers;
    }

    /**
     * Parse the API response and return custom formatted response for consistency
     *
     * @see https://laravel.com/docs/8.x/http-client#making-requests
     *
     * @param object $response Response object from API results
     *
     * @return object Custom response returned for consistency
     *  {
     *    +"headers": [
     *      "Date" => "Fri, 12 Nov 2021 20:13:55 GMT",
     *      "Content-Type" => "application/json",
     *      "Content-Length" => "1623",
     *      "Connection" => "keep-alive"
     *    ],
     *    +"json": "{"id":12345678}"
     *    +"object": {
     *      +"id": 12345678
     *    }
     *    +"status": {
     *      +"code": 200
     *      +"ok": true
     *      +"successful": true
     *      +"failed": false
     *      +"serverError": false
     *      +"clientError": false
     *   }
     * }
     */
    public function parseApiResponse(object $response): object
    {
        return (object) [
            'headers' => $this->convertHeadersToArray($response->headers()),
            'json' => json_encode($response->json()),
            'object' => $response->object(),
            'status' => (object) [
                'code' => $response->status(),
                'ok' => $response->ok(),
                'successful' => $response->successful(),
                'failed' => $response->failed(),
                'serverError' => $response->serverError(),
                'clientError' => $response->clientError(),
            ],
        ];
    }
}
