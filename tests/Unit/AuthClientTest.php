<?php

namespace Unit;

use Glamstack\GoogleAuth\Tests\Fakes\AuthClientFake;

it('will throw exception if missing required parameter', function(){
    $client = new AuthClientFake([
       'file_path' => 'tests/fakes/fake_key_file.json'
    ]);
})->expectExceptionMessage('The required option "api_scopes" is missing.');

/**
 * Testing the validation will throw exception if there is not a json key
 * provided
 */
it('will throw exception if file_path or json_key is not set', function(){
   $client = new AuthClientFake([
       'api_scopes' => ['https://www.googleapis.com/auth/ndev.clouddns.readwrite'],
       'subject_email' => 'example@example.com'
   ]);
})->expectExceptionMessage('You must specify either the file_path or json_key in the connection_config array.');

it('get a file path if provided', function() {
    $file_path_connection_config = [
        'api_scopes' => ['https://www.googleapis.com/auth/ndev.clouddns.readwrite'],
        'subject_email' => 'example@example.com',
        'file_path' => 'testing_file_path/key.json'
    ];
    $client = new AuthClientFake($file_path_connection_config);
    $file_path = $client->getFilePath();
    expect($file_path)->toBe('testing_file_path/key.json');
});

it('get json key string from construct', function(){
    $json_string = '{"type": "service_account",
  "project_id": "project_id",
  "private_key_id": "key_id",
  "private_key": "key_data",
  "client_email": "xxxxx@xxxxx.iam.gserviceaccount.com",
  "client_id": "123455667897654",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token",
  "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
  "client_x509_cert_url": "some stuff"}';

    $file_path_connection_config = [
        'api_scopes' => ['https://www.googleapis.com/auth/ndev.clouddns.readwrite'],
        'subject_email' => 'example@example.com',
        'json_key' => $json_string
    ];
    $client = new AuthClientFake($file_path_connection_config);
    $file_path = $client->getJsonKeyString();
    expect($file_path)->toBe($json_string);
});

it('can get API scopes', function(){
    $file_path_connection_config = [
        'api_scopes' => ['https://www.googleapis.com/auth/ndev.clouddns.readwrite',
            'https://www.googleapis.com/auth/cloud-platform'],
        'subject_email' => 'example@example.com',
        'file_path' => 'testing_file_path/key.json'
    ];
    $client = new AuthClientFake($file_path_connection_config);
    $api_scopes = $client->getApiScopes();
    expect($api_scopes)->toBe('https://www.googleapis.com/auth/ndev.clouddns.readwrite https://www.googleapis.com/auth/cloud-platform');
});

it('can parse json file properly', function(){
    $file_path_connection_config = [
        'api_scopes' => ['https://www.googleapis.com/auth/ndev.clouddns.readwrite',
            'https://www.googleapis.com/auth/cloud-platform'],
        'subject_email' => 'example@example.com',
        'file_path' => 'tests/fakes/fake_key_file.json'
    ];
    $client = new AuthClientFake($file_path_connection_config);
    $file_contents = $client->parseJsonFile($file_path_connection_config['file_path']);
    expect($file_contents->type)->toBe('service_account');
    expect($file_contents->project_id)->toBe('project_id');
    expect($file_contents->private_key_id)->toBe('key_id');
    expect($file_contents->client_email)->toBe('example@example.iam.gserviceaccount.com');
    expect($file_contents->client_id)->toBe('1234567890');
    expect($file_contents->auth_uri)->toBe('https://accounts.google.com/o/oauth2/auth');
    expect($file_contents->token_uri)->toBe('https://oauth2.googleapis.com/token');
    expect($file_contents->auth_provider_x509_cert_url)->toBe('https://www.googleapis.com/oauth2/v1/certs');
    expect($file_contents->client_x509_cert_url)->toBe('fake_client_cert');
});

it('can get private key from json file', function(){
    $file_path_connection_config = [
        'api_scopes' => ['https://www.googleapis.com/auth/ndev.clouddns.readwrite',
            'https://www.googleapis.com/auth/cloud-platform'],
        'subject_email' => 'example@example.com',
        'file_path' => 'tests/fakes/fake_key_file.json'
    ];
    $client = new AuthClientFake($file_path_connection_config);
    $file_contents = $client->parseJsonFile($file_path_connection_config['file_path']);
    $private_key = $client->getPrivateKey($file_contents);
    expect($private_key)->toBe('key_data');
});

it('can get client email from json file', function(){
    $file_path_connection_config = [
        'api_scopes' => ['https://www.googleapis.com/auth/ndev.clouddns.readwrite',
            'https://www.googleapis.com/auth/cloud-platform'],
        'subject_email' => 'example@example.com',
        'file_path' => 'tests/fakes/fake_key_file.json'
    ];
    $client = new AuthClientFake($file_path_connection_config);
    $file_contents = $client->parseJsonFile($file_path_connection_config['file_path']);
    $client_email = $client->getClientEmail($file_contents);
    expect($client_email)->toBe('example@example.iam.gserviceaccount.com');
});

it('can get subject email from the connection config array', function(){
    $file_path_connection_config = [
        'api_scopes' => ['https://www.googleapis.com/auth/ndev.clouddns.readwrite',
            'https://www.googleapis.com/auth/cloud-platform'],
        'subject_email' => 'example@example.com',
        'file_path' => 'tests/fakes/fake_key_file.json'
    ];
    $client = new AuthClientFake($file_path_connection_config);
    $subject_email = $client->getSubjectEmail();
    expect($subject_email)->toBe('example@example.com');
});

it('will return null for subject email if one is not provided', function(){
    $file_path_connection_config = [
        'api_scopes' => ['https://www.googleapis.com/auth/ndev.clouddns.readwrite',
            'https://www.googleapis.com/auth/cloud-platform'],
        'file_path' => 'tests/fakes/fake_key_file.json'
    ];
    $client = new AuthClientFake($file_path_connection_config);
    $subject_email = $client->getSubjectEmail();
    expect($subject_email)->toBeNull();
});

it('can create a jwt header', function(){
    $file_path_connection_config = [
        'api_scopes' => ['https://www.googleapis.com/auth/ndev.clouddns.readwrite',
            'https://www.googleapis.com/auth/cloud-platform'],
        'subject_email' => 'example@example.com',
        'file_path' => 'tests/fakes/fake_key_file.json'
    ];
    $client = new AuthClientFake($file_path_connection_config);
    $encoded_jwt_header = $client->createJwtHeader();
    expect($encoded_jwt_header)->toBe('eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9');
});

it('can create JWT Claim', function(){
    $file_path_connection_config = [
        'api_scopes' => ['https://www.googleapis.com/auth/ndev.clouddns.readwrite',
            'https://www.googleapis.com/auth/cloud-platform'],
        'subject_email' => 'example@example.com',
        'file_path' => 'tests/fakes/fake_key_file.json'
    ];
    $client = new AuthClientFake($file_path_connection_config);
    $file_contents = $client->parseJsonFile($file_path_connection_config['file_path']);
    $client_email = $client->getClientEmail($file_contents);
    $parsed_api_scopes = $client->getApiScopes();
    $subject_email = $client->getSubjectEmail();
    $jwt_claim = $client->createJwtClaim($client_email, $parsed_api_scopes, $subject_email);
    expect($jwt_claim)->toBeString();
    expect($jwt_claim)->toStartWith('eyJpc3MiOiJleGF');
});

it('can base64 encode an input', function(){
    $file_path_connection_config = [
        'api_scopes' => ['https://www.googleapis.com/auth/ndev.clouddns.readwrite',
            'https://www.googleapis.com/auth/cloud-platform'],
        'subject_email' => 'example@example.com',
        'file_path' => 'tests/fakes/fake_key_file.json'
    ];
    $client = new AuthClientFake($file_path_connection_config);
   $encoded_string = $client->base64_url_encode('testing_input');
   expect($encoded_string)->toBe('dGVzdGluZ19pbnB1dA');
});

//it('can send auth request', function(){
//    //TODO: add unit test for sending auth request
//});

it('can convert headers to array', function(){
    $file_path_connection_config = [
        'api_scopes' => ['https://www.googleapis.com/auth/ndev.clouddns.readwrite',
            'https://www.googleapis.com/auth/cloud-platform'],
        'subject_email' => 'example@example.com',
        'file_path' => 'tests/fakes/fake_key_file.json'
    ];
    $headers_array = [
        'Date' => [
            0 => "Sun, 30 Jan 2022 01:18:14 GMT"
        ],
        'Content-Type' => [
            0 => "application/json"
        ],
        'Transfer-Encoding' => [
            0 => "chunked"
        ]
    ];
    $client = new AuthClientFake($file_path_connection_config);
    $headers_array = $client->convertHeadersToArray($headers_array);
    expect($headers_array)->toBeArray();
    expect($headers_array['Date'])->toBe('Sun, 30 Jan 2022 01:18:14 GMT');
    expect($headers_array['Content-Type'])->toBe('application/json');
    expect($headers_array['Transfer-Encoding'])->toBe('chunked');
});

it('can set key contents from file', function(){
    $file_path_connection_config = [
        'api_scopes' => ['https://www.googleapis.com/auth/ndev.clouddns.readwrite',
            'https://www.googleapis.com/auth/cloud-platform'],
        'subject_email' => 'example@example.com',
        'file_path' => 'tests/fakes/fake_key_file.json'
    ];
    $client = new AuthClientFake($file_path_connection_config);
    $file_contents = $client->setKeyContents(null, $file_path_connection_config['file_path']);
    expect($file_contents->type)->toBe('service_account');
    expect($file_contents->project_id)->toBe('project_id');
    expect($file_contents->private_key_id)->toBe('key_id');
    expect($file_contents->client_email)->toBe('example@example.iam.gserviceaccount.com');
    expect($file_contents->client_id)->toBe('1234567890');
    expect($file_contents->auth_uri)->toBe('https://accounts.google.com/o/oauth2/auth');
    expect($file_contents->token_uri)->toBe('https://oauth2.googleapis.com/token');
    expect($file_contents->auth_provider_x509_cert_url)->toBe('https://www.googleapis.com/oauth2/v1/certs');
    expect($file_contents->client_x509_cert_url)->toBe('fake_client_cert');
});

it('can set key contents from string json', function(){
    //TODO: Update this to be more realistic
    $json_string = '{"type": "service_account",
  "project_id": "project_id",
  "private_key_id": "key_id",
  "private_key": "key_data",
  "client_email": "xxxxx@xxxxx.iam.gserviceaccount.com",
  "client_id": "1234567890",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token",
  "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
  "client_x509_cert_url": "some stuff"}';

    $file_path_connection_config = [
        'api_scopes' => ['https://www.googleapis.com/auth/ndev.clouddns.readwrite'],
        'subject_email' => 'example@example.com',
        'json_key' => $json_string
    ];
    $client = new AuthClientFake($file_path_connection_config);
    $file_contents = $client->setKeyContents($json_string, null);
    expect($file_contents->type)->toBe('service_account');
    expect($file_contents->project_id)->toBe('project_id');
    expect($file_contents->client_id)->toBe('1234567890');
});
