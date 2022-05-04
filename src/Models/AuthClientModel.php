<?php

namespace Glamstack\GoogleAuth\Models;

use Symfony\Component\OptionsResolver\OptionsResolver;

class AuthClientModel
{
    private OptionsResolver $resolver;

    public function __construct(){
        $this->resolver = new OptionsResolver();
    }

    /**
     * Verify required resource for creation of a managed zone
     *
     * @param array $options
     *      The request_data array for managed zone creation
     *
     * @return void
     */
    public function verifyConstructor(array $options = []): void
    {
        $this->constructorOptions($this->resolver);
        $this->resolver->resolve($options);
    }

    /**
     * Verify all required options are set
     *
     * Utilizes `OptionsResolver` for validation
     *
     * @see https://symfony.com/doc/current/components/options_resolver.html
     *
     * @param OptionsResolver $resolver
     *      The request_data array passed in for creating a managed zone
     *
     * @return void
     */
    protected function constructorOptions(OptionsResolver $resolver): void
    {
        $resolver->define('api_scopes')
            ->required()
            ->allowedTypes('array')
            ->info('The Google API Scopes to apply');

        $resolver->define('subject_email')
            ->allowedTypes('string', 'null')
            ->default(null)
            ->info('The email address of the user for which the application is requesting delegated access. Only used for Google Workspace SDK');

        $resolver->define('file_path')
            ->allowedTypes('string', 'null')
            ->default(null)
            ->info('The file path location to the Google JSON key');

        $resolver->define('json_key')
            ->allowedTypes('string', 'null')
            ->default(null)
            ->info('String formatted Google JSON key');
    }
}