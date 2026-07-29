<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Tests\Unit\Application\Framework\Events;

use Invoker\InvokerInterface;
use PHPUnit\Framework\TestCase;
use Vconnect\IntegrityChecker\Application\Framework\Events\Manager;
use Vconnect\IntegrityChecker\Application\Framework\Events\ObserverInterface;

class ManagerTest extends TestCase
{
    public function testDispatchesViaInvokerWithRawEventData(): void
    {
        $received = null;

        $observer = new class ($received) implements ObserverInterface {
            public function __construct(private &$received)
            {
            }
            public function execute(array $eventData): void
            {
                $this->received = $eventData;
            }
        };

        // Test double invoker that actually performs the call (for tests that want real execution)
        $invoker = new class () implements InvokerInterface {
            public function call($callable, array $parameters = []): mixed
            {
                return $callable(...$parameters);
            }
        };

        $manager = new Manager($invoker, [
            'php_file_analysis' => [$observer],
        ]);

        $payload = [
            'fileContent' => '<?php echo "hi";',
            'package' => (object)['name' => 'test/pkg'],
            'file' => 'some/file.php',
        ];

        $manager->dispatchEvent('php_file_analysis', $payload);

        $this->assertSame($payload, $received);
        $this->assertArrayHasKey('fileContent', $received);
    }

    public function testDispatchesToMultipleObservers(): void
    {
        $calls = [];

        $observer1 = new class ($calls) implements ObserverInterface {
            public function __construct(private array &$calls)
            {
            }
            public function execute(array $eventData): void
            {
                $this->calls[] = 'obs1';
            }
        };
        $observer2 = new class ($calls) implements ObserverInterface {
            public function __construct(private array &$calls)
            {
            }
            public function execute(array $eventData): void
            {
                $this->calls[] = 'obs2';
            }
        };

        $invoker = new class () implements InvokerInterface {
            public function call($callable, array $parameters = []): mixed
            {
                return $callable(...$parameters);
            }
        };

        $manager = new Manager($invoker, [
            'some_event' => [$observer1, $observer2],
        ]);

        $manager->dispatchEvent('some_event', ['foo' => 'bar']);

        $this->assertSame(['obs1', 'obs2'], $calls);
    }

    public function testDelegatesToInvokerWithClassName(): void
    {
        $invoker = $this->createMock(InvokerInterface::class);
        $invoker->expects($this->once())
            ->method('call')
            ->with(
                ['Some\\ObserverClass', 'execute'],
                $this->callback(fn (array $p): bool => $p[0] === ['key' => 'value'])
            );

        $manager = new Manager($invoker, [
            'evt' => ['Some\\ObserverClass'],
        ]);

        $manager->dispatchEvent('evt', ['key' => 'value']);
    }

    public function testDelegatesToInvokerWithInstance(): void
    {
        $observer = $this->createMock(ObserverInterface::class);
        $observer->expects($this->once())
            ->method('execute')
            ->with(['data' => 123]);

        // When listener is an instance, the invoker will be asked to call [instance, 'execute']
        $invoker = $this->createMock(InvokerInterface::class);
        $invoker->expects($this->once())
            ->method('call')
            ->with(
                [$observer, 'execute'],
                [['data' => 123]]
            )
            ->willReturnCallback(
                // Simulate what a real invoker would do
                fn ($callable, $params): mixed => call_user_func($callable, ...$params)
            );

        $manager = new Manager($invoker, [
            'my_event' => [$observer],
        ]);

        $manager->dispatchEvent('my_event', ['data' => 123]);
    }

    public function testNoopWhenNoListeners(): void
    {
        $invoker = $this->createMock(InvokerInterface::class);
        $invoker->expects($this->never())->method('call');

        $manager = new Manager($invoker, []);
        $manager->dispatchEvent('unknown', ['anything' => 123]);
    }
}
