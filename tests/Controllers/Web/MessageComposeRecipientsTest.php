<?php

declare(strict_types=1);

namespace kintai\Tests\Bundles\Messaging\Controllers\Web;

use kintai\Bundles\Installed\Messaging\Controllers\Web\MessageController;
use kintai\Bundles\Installed\Messaging\Repositories\MessageRepositoryInterface;
use kintai\Contract\Data\StoreRepositoryInterface;
use kintai\Contract\Data\StoreUserRepositoryInterface;
use kintai\Contract\Data\UserRepositoryInterface;
use kintai\Contract\Http\Request;
use kintai\Contract\Services\AuditLogger;
use kintai\Contract\Services\NotificationService;
use kintai\Contract\View\ViewRenderer;
use PHPUnit\Framework\TestCase;

/** Le sélecteur de destinataires ne propose jamais un compte supprimé (anonymisé, ligne conservée). */
final class MessageComposeRecipientsTest extends TestCase
{
    public function testComposeHidesDeletedAccountsForOwners(): void
    {
        $dir = sys_get_temp_dir() . '/kintai-messaging-compose-views';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($dir . '/messages-compose.php', "<?php echo json_encode(array_map(fn(\$u) => \$u['id'], \$all_users));");
        file_put_contents(sys_get_temp_dir() . '/layout.app.php', "<?php echo \$content ?? '';");
        if (!is_dir(sys_get_temp_dir() . '/layout')) {
            mkdir(sys_get_temp_dir() . '/layout', 0777, true);
        }
        file_put_contents(sys_get_temp_dir() . '/layout/app.php', "<?php echo \$content ?? '';");

        $view = new ViewRenderer(sys_get_temp_dir());
        $view->addNamespace('messaging', $dir);

        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findAll')->willReturn([
            ['id' => 1, 'display_name' => 'Moi', 'deleted_at' => null],
            ['id' => 2, 'display_name' => 'Paul', 'deleted_at' => null],
            ['id' => 3, 'display_name' => '[Supprimé]', 'deleted_at' => '2026-11-01 10:00:00'],
        ]);
        $stores = $this->createStub(StoreRepositoryInterface::class);
        $stores->method('findAll')->willReturn([]);

        $controller = new MessageController(
            $view,
            $users,
            $stores,
            $this->createStub(StoreUserRepositoryInterface::class),
            $this->createStub(MessageRepositoryInterface::class),
            new AuditLogger(),
            $this->createStub(NotificationService::class),
        );

        $req = new Request();
        $req->setAttribute('auth_user', ['id' => 1, 'is_admin' => 1]);

        $this->assertSame('[2]', $controller->messagesCompose($req)->body());
    }
}
