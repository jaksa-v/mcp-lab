<?php

namespace Tests\Support;

use App\Mcp\Servers\DirectoryServer;
use Closure;
use Illuminate\Http\Response;
use Laravel\Mcp\Client\Contracts\Transport as ClientTransport;
use Laravel\Mcp\Server\Contracts\Transport as ServerTransport;
use LogicException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InProcessDirectoryTransport implements ClientTransport
{
    private CapturingDirectoryTransport $serverTransport;

    /** @var list<string> */
    private array $replies = [];

    private bool $started = false;

    public function __construct()
    {
        $this->serverTransport = new CapturingDirectoryTransport;
    }

    public function connect(): void
    {
        if ($this->started) {
            return;
        }

        (new DirectoryServer($this->serverTransport))->start();
        $this->started = true;
    }

    public function disconnect(): void
    {
        $this->replies = [];
    }

    public function setTimeoutSeconds(float $seconds): void
    {
        //
    }

    public function setProtocolVersion(string $version): void
    {
        //
    }

    /**
     * @return array<string, mixed>
     */
    public function recipe(): array
    {
        return ['driver' => 'in-process'];
    }

    public function send(string $message): void
    {
        $this->serverTransport->reply = null;
        $this->serverTransport->handle($message);

        if (is_string($this->serverTransport->reply)) {
            $this->replies[] = $this->serverTransport->reply;
        }
    }

    public function receive(): string
    {
        $reply = array_shift($this->replies);

        if (! is_string($reply)) {
            throw new LogicException('Directory server did not reply.');
        }

        return $reply;
    }
}

class CapturingDirectoryTransport implements ServerTransport
{
    /** @var (Closure(string): void)|null */
    public ?Closure $handler = null;

    public ?string $reply = null;

    public function onReceive(Closure $handler): void
    {
        $this->handler = $handler;
    }

    public function send(string $message, ?string $sessionId = null): void
    {
        $this->reply = $message;
    }

    public function handle(string $message): void
    {
        if (! $this->handler instanceof Closure) {
            throw new LogicException('Directory server is not listening.');
        }

        ($this->handler)($message);
    }

    public function run(): Response|StreamedResponse
    {
        return response('');
    }

    public function sessionId(): ?string
    {
        return 'in-process-directory';
    }

    public function stream(Closure $stream): void
    {
        //
    }
}
