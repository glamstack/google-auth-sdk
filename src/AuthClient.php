<?php

namespace Glamstack\GoogleAuth;

use Glamstack\GoogleAuth\Models\AuthClientModel;
use Illuminate\Support\Facades\Http;

class AuthClient
{
    // Standard parameters for building JWT request with Google OAuth Server.
    // They are put here for easy changing if necessary
    const AUTH_BASE_URL = 'https://oauth2.googleapis.com/token';
    const AUTH_ALGORITHM = 'RS256';
    const AUTH_TYPE = 'JWT';
    const AUTH_GRANT_TYPE = 'urn:ietf:params:oauth:grant-type:jwt-bearer';
    const ENCRYPT_METHOD = 'sha256';

    private array $connection_config;
    private AuthClientModel $auth_model;

    /**
     * This function takes care of configuring the JWT that is used for the
     * API request sent to Google's OAuth Servers.
     *
     * @see https://developers.google.com/identity/protocols/oauth2/service-account
     *
     * @param array $connection_config
     *      The connection configuration to use for Google OAuth
     */
    public function __construct(
        array $connection_config = []
    )
    {
        // Create a new AuthClientModel
        $this->auth_model = new AuthClientModel();

        // Utilize the model to ensure we have the proper inputs
        $this->auth_model->verifyConstructor($connection_config);

        // Set the `connection_config` class variable
        $this->setConnectionConfig($connection_config);

        // Verify that either a `file_path` or `json_key` has been provided
        $this->verifyJsonKeyConfig();
    }

    /**
     * Set the connection_config class property array
     *
     * @param array $connection_configuration
     *      Connection configuration array provided during initialization
     *
     * @return void
     */
    protected function setConnectionConfig(array $connection_configuration): void
    {
        $this->connection_config = $connection_configuration;
    }

    /**
     * Verify that at either `file_path` or `json_key` is set
     *
     * @return void
     */
    protected function verifyJsonKeyConfig(): void
    {
        if (
            !array_key_exists('file_path', $this->connection_config) &&
            !array_key_exists('json_key', $this->connection_config)) {
            throw new Exception('testing');
        }
    }

    /**
     * Send authentication request to the Google OAuth2 Server
     *
     * @return string
     */
    public function authenticate(): string
    {
        // Get the file path loaded into the construct method
        $file_path = $this->getFilePath();

        // Get the JSON key string loaded into the construct method
        $json_key_string = $this->getJsonKeyString();

        // Parse the JSON and return an object
        $json_key = $this->setKeyContents($json_key_string, $file_path);

        // Set the class api_scopes variable
        $api_scopes = $this->getApiScopes();

        // Set the Google Subject email
        $subject_email = $this->getSubjectEmail();

        // Set the Google Client email
        $client_email = $this->getClientEmail($json_key);

        // Set the private key to use for authentication
        $private_key = $this->getPrivateKey($json_key);

        // If subject email is not supplied set the subject_email to the client_email
        if (!$subject_email) {
            $subject_email = $client_email;
        }

        // Create the encrypted JWT Headers
        $jwt_headers = $this->createJwtHeader();

        // Create the encrypted JWT Claim
        $jwt_claim = $this->createJwtClaim($client_email, $api_scopes, $subject_email);

        // Create the signature to append to the JWT
        $signature = $this->createSignature($jwt_headers, $jwt_claim, $private_key);

        // Set the class jwt variable to the Google OAuth2 required string
        $jwt = $jwt_headers . '.' . $jwt_claim . '.' . $signature;

        // Send the authentication request with the `jwt` and return the
        // access_token from the response
        return $this->sendAuthRequest($jwt)->object->access_token;
    }

    /**
     * Get the file path from the construct array
     *
     * Will return null if the `file_path` key is not set
     *
     * @return string|null
     */
    protected function getFilePath(): string|null
    {
        if (array_key_exists('file_path', $this->connection_config)) {
            return $this->connection_config['file_path'];
        } else {
            return null;
        }
    }

    /**
     * Get the JSON key from the construct array
     *
     * Will return null if the 'json_key' key is not set
     *
     * @return string|null
     */
    protected function getJsonKeyString(): string|null
    {
        if (array_key_exists('json_key', $this->connection_config)) {
            return $this->connection_config['json_key'];
        } else {
            return null;
        }
    }

    /**
     * Determine rather to use the `json_key` or `file_path`
     *
     * This will set the JSON key used for authentication
     *
     * @param string|null $json_key_string
     *      A Google JSON key formatted string to use for Google OAuth
     *
     * @param string|null $file_path
     *      The file path to the JSON key to use for Google OAuth
     *
     * @return object
     */
    protected function setKeyContents(?string $json_key_string, ?string $file_path): object
    {
        if ($json_key_string != null) {
            return (object)json_decode($json_key_string);
        } else {
            return $this->parseJsonFile($file_path);
        }
    }

    /**
     * Parse the Google JSON key
     *
     * @param string $file_path
     *      The file path of the Google JSON key
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
     * Set the API scopes for the Google Authentication API token.
     *
     * The return is space seperated string
     *
     * @return string
     */
    protected function getApiScopes(): string
    {
        return collect($this->connection_config['api_scopes'])
            ->implode(' ');
    }

    /**
     * Get the Google Subject Email if key exists in construct
     *
     * This will return null if the `subject_email` key is not used
     *
     * @return string | null
     */
    protected function getSubjectEmail(): string|null
    {
        if (array_key_exists('subject_email', $this->connection_config)) {
            return $this->connection_config['subject_email'];
        } else {
            return null;
        }
    }

    /**
     * Get the `client_email` from the Google JSON key
     *
     * @param object $json_file_contents
     *      The json_decoded Google JSON key token
     *
     * @return string
     */
    protected function getClientEmail(object $json_file_contents): string
    {
        return $json_file_contents->client_email;
    }

    /**
     * Set the `private_key` and `client_email` class variables
     *
     * Utilizes the JSON file contents to fetch the information.
     *
     * @param object $json_file_contents
     *      The json_decoded Google JSON key token
     *
     * @return string
     */
    protected function getPrivateKey(object $json_file_contents): string
    {
        return $json_file_contents->private_key;
    }

    /**
     * Create and encode the required JWT Headers for Google OAuth2
     * authentication
     *
     * @see https://developers.google.com/identity/protocols/oauth2/service-account#:~:text=Forming%20the%20JWT%20header
     *
     * @return string
     */
    protected function createJwtHeader(): string
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
     * @param string $client_email
     *      The `client_email` from the Google JSON key
     *
     * @param string $parsed_api_scopes
     *      The `api_scopes` from the construct method
     *
     * @param string $subject_email
     *      The `subject_email` to use for authentication
     *
     * @return string
     */
    protected function createJwtClaim(string $client_email, string $parsed_api_scopes, string $subject_email): string
    {
        $jwt_claim = [
            'iss' => $client_email,
            'scope' => $parsed_api_scopes,
            'aud' => self::AUTH_BASE_URL,
            'exp' => time() + 3600,
            'iat' => time(),
            'sub' => $subject_email
        ];
        $encoded_jwt_claim = $this->base64_url_encode(
            (string)json_encode($jwt_claim)
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
     * @param string $jwt_header
     *      The JWT Header string required for Google OAuth2 authentication
     *
     * @param string $jwt_claim
     *      The JWT Claim string required for Google OAuth2 authentication
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
     * @param string $input
     *      The input string to encode
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
     * @return object
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
    public function authenticate(): string
    {
        return $this->sendAuthRequest()->object->access_token;
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
