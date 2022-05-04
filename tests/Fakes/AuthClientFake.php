<?php

namespace Glamstack\GoogleAuth\Tests\Fakes;

use Glamstack\GoogleAuth\AuthClient;
use Illuminate\Support\Facades\Http;

class AuthClientFake extends AuthClient
{
    public function getFilePath(): string|null
    {
        return parent::getFilePath(); // TODO: Change the autogenerated stub
    }

    public function getJsonKeyString(): string|null
    {
        return parent::getJsonKeyString(); // TODO: Change the autogenerated stub
    }

    public function setConnectionConfig(array $connection_configuration): void
    {
        parent::setConnectionConfig($connection_configuration); // TODO: Change the autogenerated stub
    }

    public function getApiScopes(): string
    {
        return parent::getApiScopes(); // TODO: Change the autogenerated stub
    }

    public function parseJsonFile(string $file_path): object
    {
        return parent::parseJsonFile($file_path); // TODO: Change the autogenerated stub
    }

    public function getPrivateKey(object $json_file_contents): string
    {
        return parent::getPrivateKey($json_file_contents); // TODO: Change the autogenerated stub
    }

    public function getClientEmail(object $json_file_contents): string
    {
        return parent::getClientEmail($json_file_contents); // TODO: Change the autogenerated stub
    }

    public function getSubjectEmail(): string|null
    {
        return parent::getSubjectEmail(); // TODO: Change the autogenerated stub
    }

    public function createJwtHeader(): string
    {
        return parent::createJwtHeader(); // TODO: Change the autogenerated stub
    }

    public function createJwtClaim(string $client_email, string $parsed_api_scopes, string $subject_email): string
    {
        return parent::createJwtClaim($client_email, $parsed_api_scopes, $subject_email); // TODO: Change the autogenerated stub
    }

    public function createSignature(string $jwt_header, string $jwt_claim, string $private_key): string
    {
        return parent::createSignature($jwt_header, $jwt_claim, $private_key); // TODO: Change the autogenerated stub
    }

    public function base64_url_encode(string $input): string
    {
        return parent::base64_url_encode($input); // TODO: Change the autogenerated stub
    }

    public function sendAuthRequest(string $jwt): object
    {
        return parent::sendAuthRequest($jwt); // TODO: Change the autogenerated stub
    }

    public function convertHeadersToArray(array $header_response): array
    {
        return parent::convertHeadersToArray($header_response); // TODO: Change the autogenerated stub
    }

    public function parseApiResponse(object $response): object
    {

        return parent::parseApiResponse($response); // TODO: Change the autogenerated stub
    }

    public function setKeyContents(?string $json_key_string, ?string $file_path): object
    {
        return parent::setKeyContents($json_key_string, $file_path); // TODO: Change the autogenerated stub
    }
}