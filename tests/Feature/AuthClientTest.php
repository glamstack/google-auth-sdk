<?php

namespace Feature;

use Gitlabit\GoogleAuth\AuthClient;

it('can authenticate with Google OAuth Server via json file', function(){
    $string_key_connection_config = [
        'api_scopes' => ['https://www.googleapis.com/auth/cloud-platform.read-only'],
        'json_key_file_path' => 'tests/Storage/keys/integration_test_key.json'
    ];
    $client = new AuthClient($string_key_connection_config);
    $auth_token = $client->authenticate();
    expect($auth_token)->toBeString();
});

it('will throw exception if authentication fails', function(){
    $string_key_connection_config = [
        'api_scopes' => ['https://www.googleapis.com/auth/cloud-platform.read-only'],
        'json_key_file_path' => 'tests/Storage/keys/incorrect_key.json'
    ];
    $client = new AuthClient($string_key_connection_config);
    $client->authenticate();
})->expectExceptionMessage('Google SDK Authentication Error. Invalid JWT Signature.');

it('will throw exception is permissions are incorrect', function(){
    $string_key_connection_config = [
        'api_scopes' => [],
        'json_key_file_path' => 'tests/Storage/keys/integration_test_key.json'
    ];
    $client = new AuthClient($string_key_connection_config);
    $auth_token = $client->authenticate();
})->expectExceptionMessage('Invalid OAuth scope or ID token audience provided.');