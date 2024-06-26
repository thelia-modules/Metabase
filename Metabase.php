<?php

namespace Metabase;

use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Thelia\Module\BaseModule;

class Metabase extends BaseModule
{
    /** @var string */
    public const DOMAIN_NAME = 'metabase';

    // Configuration metabase
    public const METABASE_URL_CONFIG_KEY = 'metabase_url';
    public const METABASE_EMBEDDING_KEY_CONFIG_KEY = 'metabase_embedding_key';
    public const METABASE_USERNAME_CONFIG_KEY = 'metabase_username';
    public const METABASE_PASSWORD_CONFIG_KEY = 'metabase_password';

    // Configuration database
    public const METABASE_NAME_CONFIG_KEY = 'metabase_name';
    public const METABASE_DB_NAME_CONFIG_KEY = 'metabase_bd_name';
    public const METABASE_ENGINE_CONFIG_KEY = 'metabase_engine';
    public const METABASE_HOST_CONFIG_KEY = 'metabase_host';
    public const METABASE_PORT_CONFIG_KEY = 'metabase_port';
    public const METABASE_DB_USERNAME_CONFIG_KEY = 'metabase_db-username';
    public const METABASE_DB_ID_CONFIG_KEY = 'metabase_db_id';

    // Metabase Token
    public const METABASE_TOKEN_SESSION_CONFIG_KEY = 'metabase_token_session';
    public const METABASE_TOKEN_EXPIRATION_DATE_CONFIG_KEY = 'metabase_token_expiration_date';

    // Metabase Order Type
    public const METABASE_ORDER_TYPE_CONFIG_KEY = 'metabase_order_type';

    // Metabase config syncing
    public const METABASE_SYNCING_OPTION = 'metabase_syncing_option';
    public const METABASE_SYNCING_SCHEDULE = 'metabase_syncing_schedule';
    public const METABASE_SYNCING_TIME = 'metabase_syncing_time';
    public const METABASE_SCANNING_SCHEDULE = 'metabase_scanning_schedule';
    public const METABASE_SCANNING_TIME = 'metabase_scanning_time';
    public const METABASE_SCANNING_FRAME = 'metabase_scanning_frame';
    public const METABASE_SCANNING_DAY = 'metabase_scanning_day';
    public const METABASE_REFINGERPRINT = 'metabase_refingerprint';

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
