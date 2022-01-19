<?php

namespace Glamstack\GoogleAuth;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthClient
{
    // Standard parameters for building JWT request with Google OAuth Server.
    // They are put here for easy changing if necessary
    private string $auth_base_url = 'https://oauth2.googleapis.com/token';
    private string $auth_algorithm = 'RS256';
    private string $auth_type = 'JWT';
    private string $auth_grant_type = 'urn:ietf:params:oauth:grant-type:jwt-bearer';
    private string $encrypt_method = 'sha256';

    private string $instance_key;
    private string $private_key;
    private string $client_email;
    private string $jwt;
    private string $api_scopes;
    private string $access_token;
    private string $file_path;
    private string $subject_email;

    /**
     * This function takes care of configuring the JWT that is used for the
     * API request sent to Google's OAuth Servers.
     *
     * @see https://developers.google.com/identity/protocols/oauth2/service-account
     *
     * @param string $instance_key (Optional) The instance key to use from the
     * configuration file to set the appropriate Google Auth Settings.
     * Default: `workspace`
     *
     * @param array $api_scopes (Optional) The Google API Scopes that will be
     * used with the token
     *
     * @param string $file_path (Optional) The file path of the Google JSON key
     * used for Service Account authentication. This parameter should only be
     * used if you are storing your JSON key outside of the
     * `storage/keys/glamstack-google-auth/` directory of your application
     */
    public function __construct(
        string $instance_key = null,
        array $api_scopes = [],
        string $file_path = null
    )
    {

        // Set the class instance_key variable.
        $this->setInstanceKey($instance_key);

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
     * Set the instance_key class variable. The instance_key variable by default
     * will be set to `workspace`. This can be overridden when initializing the
     * SDK with a different instance key which is passed into this function to
     * set the class variable to the provided key.
     *
     * @param string $instance_key (Optional) The instance key to use from the
     * configuration file.
     *
     * @return void
     */
    protected function setInstanceKey(?string $instance_key) : void
    {
        if($instance_key == null){
            /** @phpstan-ignore-next-line */
            $this->instance_key = config(
                'glamstack-google-auth.instance'
            );
        } else {
            $this->instance_key = $instance_key;
        }
    }


    /**
     * Set the API scopes for the Google Authentication API token. The scope
     * will default to the configuration file for the instance, and can be
     * overridden with the $api_scopes variable being set during initialization.
     *
     * @param ?array $api_scopes (Optional) API Scopes to be set. This will
     * override the configuration file API Scope settings.
     *
     * @return void
     */
    protected function setApiScopes(?array $api_scopes) : void
    {
        if(!$api_scopes){
            $this->api_scopes = collect(
                config('glamstack-google-auth.' . $this->instance_key .
                '.api_scopes')
            )->implode(' ');
        }
        else{
            $this->api_scopes = collect($api_scopes)->implode(' ');
        }
    }

    /**
     * Set the class variable $file_path to either the provided $instance_key
     * configuration or the $file_path provided from class initialization.
     *
     * @param ?string $file_path The file path to set for the Google JSON token
     *
     * @return void
     */
    protected function setFilePath(?string $file_path){
        if($file_path == null){
            $this->file_path = storage_path(
                'keys/glamstack-google-auth/'. $this->instance_key . '.json'
            );
        } else {
            $this->file_path = $file_path;
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
        if(config('glamstack-google-auth.' . $this->instance_key . '.email') != null){
            /** @phpstan-ignore-next-line */
            $this->subject_email = config(
                'glamstack-google-auth.' . $this->instance_key . '.email'
            );
        }
        else{
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
    protected function createJwtHeader(){
        $jwt_header = [
            'alg' => $this->auth_algorithm,
            'typ' => $this->auth_type,
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
    protected function createJwtClaim(){
        $jwt_claim = [
            'iss' => $this->client_email,
            'scope' => $this->api_scopes,
            'aud' => $this->auth_base_url,
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
            $this->encrypt_method
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
        $response = Http::asForm()->post(
            $this->auth_base_url,
            [
                'grant_type' => $this->auth_grant_type,
                'assertion' => $this->jwt
            ]
        );
        return $response->object();
    }

    /**
     * Send authentication request to the Google OAuth2 Server
     *
     * @return string
     */
    public function authenticate(){
        $this->access_token = $this->sendAuthRequest()->access_token;
        return $this->access_token;
    }
}
