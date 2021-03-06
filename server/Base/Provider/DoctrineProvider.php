<?php

namespace Silo\Base\Provider;

use Doctrine\Common\Cache\ArrayCache;
use Silo\Base\Provider\DoctrineProvider\SQLLogger;
use Silo\Base\Provider\DoctrineProvider\TablePrefix;
use Pimple\ServiceProviderInterface;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Types\Type;
use Silo\Base\Provider\DoctrineProvider\UTCDateTimeType;

/**
 * Doctrine ORM as a Service.
 */
class DoctrineProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(\Pimple\Container $app)
    {
        $app['em.paths'] = [];
        $app['em.cache'] = function() {
            return new ArrayCache(); //new FilesystemCache(sys_get_temp_dir());
        };

        // UTC for datetimes
        Type::overrideType('datetime', UTCDateTimeType::class);
        Type::overrideType('datetimetz', UTCDateTimeType::class);

        $app['em.logger'] = function () {
            return new SQLLogger();
        };

        if (!isset($app['em.dsn'])) {
            throw new \Exception("em.dsn should be set");
        }

        if (!isset($app['em.config'])) {
            $app['em.config'] = function ($app) {
                $config = Setup::createAnnotationMetadataConfiguration(
                    $app['em.paths'],
                    true,
                    null,
                    $app['em.cache'],
                    false
                );
                $config->addEntityNamespace('Inventory', 'Silo\Inventory\Model');
                $config->setSQLLogger($app['em.logger']);

                return $config;
            };
        }

        $app['em.evm'] = function ($app) {
            $evm = new \Doctrine\Common\EventManager();
            $evm->addEventListener(\Doctrine\ORM\Events::loadClassMetadata, new TablePrefix('silo_'));
            $evm->addEventSubscriber(new OperationSubscriber($app['collector']));
            return $evm;
        };

        $app['em'] = function ($app) {
            $em = EntityManager::create(['url' => $app['em.dsn']], $app['em.config'], $app['em.evm']);

            $platform = $em->getConnection()->getDatabasePlatform();
            $platform->registerDoctrineTypeMapping('enum', 'string');

            return $em;
        };

        // Shortcut for getting a Repository instance quick
        $app['re'] = $app->protect(function ($name) use ($app) {
            return $app['em']->getRepository($name);
        });
    }
}
