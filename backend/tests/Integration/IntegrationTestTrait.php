<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Infrastructure\Fixture\BarbershopFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Nette\Bootstrap\Configurator;
use Nette\DI\Container;

trait IntegrationTestTrait
{
    private ?Container $container = null;

    private const TEST_DB_PATH = __DIR__ . '/../../var/test.sqlite';

    protected function getContainer(): Container
    {
        if ($this->container === null) {
            $configurator = new Configurator();
            $configurator->setDebugMode(true);
            $configurator->setTempDirectory(sys_get_temp_dir() . '/nette-test-' . getmypid());
            $configurator->addStaticParameters([
                'appDir' => __DIR__ . '/../../src',
            ]);
            $configurator->addConfig(__DIR__ . '/../../config/config.test.neon');

            $this->container = $configurator->createContainer();
        }

        return $this->container;
    }

    protected function bootIntegrationDatabase(): void
    {
        if (file_exists(self::TEST_DB_PATH)) {
            unlink(self::TEST_DB_PATH);
        }

        $this->container = null;

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->getByType(EntityManagerInterface::class);

        $dbalConn = $em->getConnection();
        $dbalConn->connect();

        $schemaTool = new SchemaTool($em);
        $schemaTool->createSchema($em->getMetadataFactory()->getAllMetadata());

        $fixtures = new BarbershopFixtures();
        $fixtures->load($em);

        $em->flush();
        $em->clear();
    }

    protected function shutdownIntegrationDatabase(): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->getByType(EntityManagerInterface::class);
        $em->getConnection()->close();
        $this->container = null;

        if (file_exists(self::TEST_DB_PATH)) {
            unlink(self::TEST_DB_PATH);
        }
    }
}