<?php

namespace Glamstack\GoogleAuth;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthClient
{
    // Standard parameters for buildling JWT request with Google OAuth Server.
    // They are put here for easy changing if neccessary
    private string $auth_base_url = 'https://oauth2.googleapis.com/token';
    private string $auth_algorithm = 'RS256';
    private string $auth_type = 'JWT';
    private string $auth_grant_type = 'urn:ietf:params:oauth:grant-type:jwt-bearer';
    private string $encrypt_method = 'sha256';

    private string $private_key;
    private string $client_email;
    private string $jwt;
    private string $api_scopes;
    private string $access_token;
    private string $file_path;

    /**
     * This function takes care of configuring the JWT that is used for the
     * API request sent to Google's OAuth Servers.
     *
     * @see https://developers.google.com/identity/protocols/oauth2/service-account
     *
     * @param array $api_scopes The Google API Scopes that will be used with 
     * with the token
     * 
     * @param string $file_path (Optional) The file path of the Google JSON key
     * used for Service Account authentication. The varaible is set, it will 
     * override the config/glamstack-google-auth.php and/or .env value for 
     * where the file is stored
     *
     * @return void
     */
    public function __construct(array $api_scopes, string $file_path = null) : void
    {

        // Create a comma space string of the provided $api_scopes
        $this->api_scopes = collect($api_scopes)->implode(' ');

        // Set the class file_path variable
        $this->setFilePath($file_path);

        // Get the file contents from the Google JSON key
        $file_contents = $this->parseJsonFile($this->file_path);

        // Set the Google Authorization Parameters from the $file_contents
        $this->setAuthParameters($file_contents);

        // Create the encrypted JWT Headers
        $jwt_headers = $this->createJwtHeader();

        // Create the encrypted JWT Claim
        $jwt_claim = $this->createJwtClaim();

        // Create the signature to append to the JWT
        $signature = $this->createSignature($jwt_headers, $jwt_claim);

        // Set the class jwt varaible to the Google OAuth2 required string
        $this->jwt = $jwt_headers.'.'.$jwt_claim.'.'.$signature;
    }

    /**
     * Set the class variable $file_path to either the provided $file_path
     * parameter from the construct method. If no $file_path parameter is
     * provided in the construct method then set the class varaible
     * $file_path to the file path provided in the "GOOGLE_JSON_FILE_PATH" env
     * variable.
     *
     * @param ?string $file_path The file path to set for the Google JSON token
     *
     * @return void
     */
    protected function setFilePath(?string $file_path){
        if($file_path == null){
            $this->file_path = config(
                'glamstack-google-workspace.google-auth.google_json_file_path'
            );
        }
        else{
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
        $file_contents = json_decode(
            (string) file_get_contents(base_path($file_path))
        );
        return $file_contents;
    }

    /**
     * Utilize the Google JSON key contents to set the class varaibles
     * `private_key` and `client_email`
     *
     * @param object $file_contents The json_decoded Google JSON key token
     *
     * @return void
     */
    protected function setAuthParameters(object $jsonFileContents) : void
    {
        $this->private_key = $jsonFileContents->private_key;
        $this->client_email = $jsonFileContents->client_email;
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
            json_encode($jwt_header)
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
            'iat' => time()
        ];
        $encoded_jwt_claim = $this->base64_url_encode(
            json_encode($jwt_claim)
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
        // encrytion method
        openssl_sign(
            $jwt_header.'.'.$jwt_claim,
            $this->private_key,
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
        $auth_response = $this->sendAuthRequest();

        // If there is more than a single scope the Google OAuth2 Server will
        // send back an `id_token` instead of an `access_token`
        if(property_exists($auth_response, 'id_token')){
            return $auth_response->id_token;
        }
        elseif(property_exists($auth_response, 'access_token')){
            return $auth_response->access_token;
        }
    }
}
