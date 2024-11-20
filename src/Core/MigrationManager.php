<?php
namespace Vendimia\Core;

use Vendimia\Database\Driver\ConnectorInterface;
use Vendimia\Database\Driver\Manager;
use Vendimia\Database\Migration\Schema;
use Vendimia\Database\Migration\Migrator;
use Vendimia\Database\FieldType;
use Vendimia\Database\DatabaseException;
use const Vendimia\PROJECT_PATH;

/**
 * Manages migrations for a Vendimia project
 */
class MigrationManager
{
    /** Info about the migrations found, indexed by serial number */
    private array $migrations;

    /** Migration grouped by module */
    private array $modules;

    /**
     * Obtains the last migration applied to the project
     */
    private function getLastMigration(): int
    {
        // RAW
        try {
            $query = $this->connector->prepare('SELECT {value:si} FROM {vendimia:si} WHERE {name:si}={last_migration_serial:s}',
                value: 'value',
                vendimia: 'vendimia',
                name: 'name',
            );
            $result = $this->connector->execute($query);

            return $result->fetch()['value'] ?? 0;
        } catch (DatabaseException) {
            // Si falla, es muy probable que la tabla no exista. la creamos
            $schema = new Schema('vendimia');
            $schema->field('name', FieldType::Char, length:48);
            $schema->field('value', FieldType::BigInt);
            $schema->primaryKey('name');
            $this->manager->createTable('vendimia', $schema);
            $this->connector->insert('vendimia', [
                'name' => 'last_migration_serial',
                'value' => 0,
            ]);

            return 0;
        }
    }

    /**
     * Saves the last migration serial applied
     */
    private function updateLastMigration($serial)
    {
        // Esto no debería fallar...
        $result = $this->connector->update('vendimia', [
            'value' => $serial
        ], $this->connector->prepare('{name:si}={last_migration_serial:s}'));

    }

    public function __construct(
        private ConnectorInterface $connector,
        private Manager $manager,
        private Migrator $migrator,
    )
    {
        $migrations = [];
        $serials = [];
        $modules = [];

        // Buscamos todas las migrations
        $modules = glob(PROJECT_PATH . '/modules/*',  GLOB_ONLYDIR);

        foreach ($modules as $m_path) {
            $module = basename($m_path);

            $m_files = glob($m_path . '/Database/migrations/*');

            foreach ($m_files as $file) {
                $serial = basename($file, '.php');

                $this->migrations[$serial] = [
                    'module' => $module,
                    'file' => $file,
                ];
                $this->modules[$module] = $this->migrations[$serial];
            }
        }
    }

    public function apply()
    {
        // Obtenemos la migración actual
        $last_migration = $this->getLastMigration();

        // Buscamos las migraciones que faltan aplicar
        $to_apply = array_filter(
            $this->migrations,
            fn($id) => $id > $last_migration,
            ARRAY_FILTER_USE_KEY
        );

        $count = count($to_apply);
        if ($count == 0) {
            // FIXME: Esto debe usar Logging
            echo "Database is up to date\n";
            return;
        }

        // Ordenamos los serials
        ksort($to_apply);

        // FIXME: Esto debe usar Logging
        echo "Applying $count migration(s)...\n";

        foreach ($to_apply as $serial => $data) {
            // FIXME: Esto debe usar Logging
            echo "- {$serial}:{$data['module']}...\n";
            $this->migrator->execute($data['file']);

            // Vamos grabando uno por uno
            $this->updateLastMigration($serial);
        }
    }
}
