<?php

namespace Metabase;

use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;

use Thelia\Module\BaseModule;

class Metabase extends BaseModule
{
    /** @var string */
    const DOMAIN_NAME = 'metabase';

    // Messages
    const SUCCESS_MESSAGE = 'The settings have been successfully updated';
    const ERROR_CONFIG_MESSAGE = 'First, you have to set the module up';
    const ERROR_TOKEN_MESSAGE = 'Error during session token collecting';

    // Configuration parameters
    const CONFIG_KEY_URL = 'metabase_url';
    const CONFIG_SESSION_TOKEN = 'session_token';
    const CONFIG_KEY_TOKEN = 'metabase_token';
    const CONFIG_USERNAME = 'metabase_username';
    const CONFIG_PASS = 'metabase_password';

    /**
     * Autowiring
     *
     * @param ServicesConfigurator $servicesConfigurator
     */
    public static function configureServices(ServicesConfigurator $servicesConfigurator): void
    {
        $servicesConfigurator->load(self::getModuleCode().'\\', __DIR__)
            ->exclude([THELIA_MODULE_DIR . ucfirst(self::getModuleCode()). "/I18n/*"])
            ->autowire(true)
            ->autoconfigure(true);
    }
}
