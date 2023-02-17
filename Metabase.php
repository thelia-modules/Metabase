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
    const WAIT_MESSAGE = 'Metabase is syncing your database table. It will take few more minutes';
    const READY_MESSAGE = 'Metabase is ready to use you can generate your Metabase Model';
    const METABASE_SUCCESS_MESSAGE = 'Metabase successfully generate model';
    const ERROR_CONFIG_MESSAGE = 'First, you have to set the module up';
    const ERROR_TOKEN_MESSAGE = 'Error during session token collecting';

    // Configuration parameters
    const CONFIG_KEY_URL = 'metabase_url';
    const CONFIG_SESSION_TOKEN = 'session_token';
    const CONFIG_KEY_TOKEN = 'metabase_token';
    const CONFIG_USERNAME = 'metabase_username';
    const CONFIG_PASS = 'metabase_password';
    const CONFIG_METABASE_NAME = 'metabase_name';
    const CONFIG_METABASE_DB_NAME = 'metabase_bd_name';
    const CONFIG_METABASE_ENGINE = 'metabase_engine';
    const CONFIG_METABASE_HOST = 'metabase_host';
    const CONFIG_METABASE_PORT = 'metabase_port';
    const CONFIG_METABASE_DB_ID = 'metabase_db_id';
    const CONFIG_METABASE_DB_USERNAME = 'metabase_db-username';

    /**
     * Autowiring.
     */
    public static function configureServices(ServicesConfigurator $servicesConfigurator): void
    {
        $servicesConfigurator->load(self::getModuleCode().'\\', __DIR__)
            ->exclude([THELIA_MODULE_DIR.ucfirst(self::getModuleCode()).'/I18n/*'])
            ->autowire(true)
            ->autoconfigure(true);
    }
}
