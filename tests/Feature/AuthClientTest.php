<?php

namespace Feature;

use Glamstack\GoogleAuth\AuthClient;

it('can authenticate with Google OAuth Server via json file', function(){
    $string_key_connection_config = [
        'api_scopes' => ['https://www.googleapis.com/auth/ndev.clouddns.readwrite'],
        'file_path' => 'tests/Storage/keys/integration_test_key.json'
    ];
    $client = new AuthClient($string_key_connection_config);
    $auth_token = $client->authenticate();
    expect($auth_token)->toBeString();
});