<?php

declare(strict_types=1);

namespace kintai\Tests\Bundles\Messaging\Database;

use Illuminate\Database\Capsule\Manager as Capsule;
use kintai\Bundles\Installed\Messaging\Repositories\DatabaseMessageRepository;
use kintai\Core\Database\BundleMigrationRunner;
use PHPUnit\Framework\TestCase;

/**
 * Migration du bundle (reprise du Core à l'extraction), jouée par le vrai BundleMigrationRunner : crée les tables sur
 * une installation neuve, ne touche pas à celles d'une instance existante ; le dépôt du bundle fonctionne dessus.
 */
final class MessagingMigrationTest extends TestCase
{
    private function migrationsPath(): string
    {
        return dirname(__DIR__, 2) . '/database/migrations';
    }

    /** @return array{0: BundleMigrationRunner, 1: Capsule} */
    private function freshRunner(): array
    {
        $capsule = new Capsule();
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $runner = (new \ReflectionClass(BundleMigrationRunner::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(BundleMigrationRunner::class, 'capsule'))->setValue($runner, $capsule);

        return [$runner, $capsule];
    }

    public function testFreshInstallCreatesTheTablesAndTheRepositoryWorks(): void
    {
        [$runner, $capsule] = $this->freshRunner();

        $this->assertSame(['2026_10_14_000001_create_messaging_tables'], $runner->runPendingFor('messaging', $this->migrationsPath()));
        $schema = $capsule->getConnection()->getSchemaBuilder();
        foreach (['message_threads', 'thread_participants', 'thread_messages'] as $table) {
            $this->assertTrue($schema->hasTable($table), $table);
        }

        $repo = new DatabaseMessageRepository();
        $thread = $repo->saveThread(['store_id' => 1, 'creator_id' => 1, 'subject' => 'Planning', 'created_at' => '2026-10-14 09:00:00']);
        $repo->saveParticipant(['thread_id' => $thread['id'], 'user_id' => 2, 'is_read' => 0]);
        $repo->saveMessage(['thread_id' => $thread['id'], 'sender_id' => 1, 'body' => 'Bonjour']);

        $this->assertSame(1, $repo->countUnread(2));
        $this->assertCount(1, $repo->findMessagesByThread((int) $thread['id']));
        $this->assertSame([], $runner->runPendingFor('messaging', $this->migrationsPath()), 'rejouée : rien');
    }

    /** Instance existante : les tables créées par le Core et leurs données restent telles quelles. */
    public function testExistingTablesAndDataAreLeftUntouched(): void
    {
        [$runner, $capsule] = $this->freshRunner();
        $schema = $capsule->getConnection()->getSchemaBuilder();
        $schema->create('message_threads', function ($table) {
            $table->increments('id');
            $table->integer('store_id');
        });
        $capsule->table('message_threads')->insert(['store_id' => 3]);

        $runner->runPendingFor('messaging', $this->migrationsPath());

        $this->assertSame(1, $capsule->table('message_threads')->count());
        $this->assertFalse($schema->hasColumn('message_threads', 'subject'), 'table existante non recréée');
    }
}
