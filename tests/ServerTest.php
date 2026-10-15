<?php

declare(strict_types=1);

/**
 * End-to-end, over a real socket, against the real src/ tree.
 *
 * Everything else in the suite tests a decision as a value. This file tests
 * that the decision reaches the wire: that index.php wires the right things
 * together, that the status code and the headers survive, and that the
 * built-in server routes the way the README says it does.
 *
 * It is the only part of the suite that needs a port. If one cannot be had
 * the tests skip rather than fail, so the suite still runs on a machine where
 * nothing may listen.
 */

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'helpers.php';

final class DevServer
{
    /** @var resource|null */
    private static $process = null;
    private static ?int $port = null;
    private static bool $tried = false;
    private static ?string $log = null;

    /** The port the server is listening on, or null if it could not be started. */
    public static function port(): ?int
    {
        if (self::$tried) {
            return self::$port;
        }

        self::$tried = true;

        $port = self::freePort();

        if ($port === null) {
            return null;
        }

        // Both streams go to a file rather than a pipe. The built-in server
        // logs a line per request to stderr, and a pipe nothing ever reads
        // fills after a few kilobytes and blocks the server mid-response -
        // which looks exactly like a hung test.
        self::$log = tempnam(sys_get_temp_dir(), 'cscli-api-server-');

        $descriptors = [
            1 => ['file', self::$log, 'a'],
            2 => ['file', self::$log, 'a'],
        ];
        $pipes = [];

        // The index.php argument is not optional: without it the built-in
        // server sees a request path ending in .php, decides it is asking for
        // a script, and 404s before any of this project's code runs.
        //
        // bypass_shell is not optional either, on Windows. Without it the
        // command runs through cmd.exe, so the handle proc_open() hands back is
        // the shell's and proc_terminate() kills only that — leaving php -S
        // alive. An orphan started this way has inherited the write end of
        // whatever pipe this process's own stdout is going to, so
        // `php tests/run.php | tail` never sees EOF: the suite finishes in two
        // seconds and the pipeline then sits there for as long as the orphan
        // lives, which looks exactly like a hung test. It is also a server
        // still listening on a random port after the suite has gone.
        $process = @proc_open(
            sprintf(
                '%s -S 127.0.0.1:%d %s',
                escapeshellarg(PHP_BINARY),
                $port,
                escapeshellarg('index.php')
            ),
            $descriptors,
            $pipes,
            dirname(__DIR__),
            null,
            ['bypass_shell' => true]
        );

        if (!is_resource($process)) {
            return null;
        }

        self::$process = $process;
        self::$port = $port;

        register_shutdown_function([self::class, 'stop']);

        // The server takes a moment to bind. Poll rather than sleeping a
        // guessed interval, so a slow machine does not produce a flake and a
        // fast one does not pay for it.
        for ($attempt = 0; $attempt < 100; $attempt++) {
            $socket = @fsockopen('127.0.0.1', $port, $errno, $error, 0.2);

            if ($socket !== false) {
                fclose($socket);
                return self::$port;
            }

            usleep(50_000);
        }

        self::stop();

        return self::$port = null;
    }

    public static function stop(): void
    {
        if (is_resource(self::$process)) {
            proc_terminate(self::$process);
            proc_close(self::$process);
            self::$process = null;
        }

        if (self::$log !== null && is_file(self::$log)) {
            unlink(self::$log);
            self::$log = null;
        }
    }

    /** Asks the OS for a port nobody is using, then gives it straight back. */
    private static function freePort(): ?int
    {
        $socket = @stream_socket_server('tcp://127.0.0.1:0', $errno, $error);

        if ($socket === false) {
            return null;
        }

        $name = stream_socket_get_name($socket, false);
        fclose($socket);

        $port = (int) substr((string) $name, strrpos((string) $name, ':') + 1);

        return $port > 0 ? $port : null;
    }
}

/**
 * One real HTTP request, written onto a socket by hand.
 *
 * Deliberately not file_get_contents() with an http:// context. That wrapper
 * follows redirects, which would hide the status code this is here to assert,
 * and it decides for itself what to do with a path like
 * "/view/..%2F..%2Findex.php" - the traversal payloads are the whole point,
 * so they have to reach the server exactly as written.
 *
 * HTTP/1.0 with an explicit Connection: close, so the body ends at EOF and
 * there is no keep-alive timeout to wait out.
 *
 * @param array<string, string> $headers
 * @return array{status: int, headers: array<string, string>, body: string}
 */
function httpGet(string $path, array $headers = []): array
{
    $port = DevServer::port();

    if ($port === null) {
        throw new RuntimeException('no dev server');
    }

    $socket = @fsockopen('127.0.0.1', $port, $errno, $error, 5);

    if ($socket === false) {
        throw new RuntimeException("could not connect to the dev server: $error");
    }

    stream_set_timeout($socket, 10);

    $request = "GET $path HTTP/1.0\r\nHost: 127.0.0.1:$port\r\nConnection: close\r\n";
    foreach ($headers as $name => $value) {
        $request .= "$name: $value\r\n";
    }

    fwrite($socket, $request . "\r\n");

    $raw = '';
    while (!feof($socket)) {
        $chunk = fread($socket, 8192);

        if ($chunk === false || $chunk === '') {
            break;
        }

        $raw .= $chunk;
    }

    fclose($socket);

    [$head, $body] = array_pad(explode("\r\n\r\n", $raw, 2), 2, '');
    $lines = explode("\r\n", $head);
    $status = preg_match('#^HTTP/\S+\s+(\d{3})#', array_shift($lines) ?? '', $match) === 1
        ? (int) $match[1]
        : 0;

    $parsed = [];
    foreach ($lines as $line) {
        [$name, $value] = array_pad(explode(':', $line, 2), 2, '');
        $parsed[strtolower(trim($name))] = trim($value);
    }

    return ['status' => $status, 'headers' => $parsed, 'body' => $body];
}

/** Runs a test only if a server could be started. */
function serverTest(string $name, callable $body): void
{
    test($name, function () use ($name, $body) {
        if (DevServer::port() === null) {
            echo "  skip  $name (could not start the built-in server)\n";
            return;
        }

        $body();
    });
}

serverTest('the real tree lists all nine languages', function () {
    $response = httpGet('/languages');

    assertSame(200, $response['status'], 'status');
    assertSame(
        "Supported Languages:\n- c\n- c#\n- c++\n- fortran\n- java\n- javascript\n- php\n- prolog\n- python\n",
        $response['body'],
        'every language directory under src/, in order'
    );
});

serverTest('the charset reaches the wire', function () {
    assertSame('text/plain; charset=utf-8', httpGet('/languages')['headers']['content-type'], 'content type');
});

serverTest('a JSON client gets JSON', function () {
    $response = httpGet('/languages', ['Accept' => 'application/json']);

    assertSame('application/json', $response['headers']['content-type'], 'content type');
    assertSame(
        ['c', 'c#', 'c++', 'fortran', 'java', 'javascript', 'php', 'prolog', 'python'],
        json_decode($response['body'], true)['languages'],
        'the decoded body'
    );
});

serverTest('the two languages the cscli client could not reach now answer', function () {
    // rawurlencode("c#") and rawurlencode("c++"), which is exactly what
    // Cscli\Api\ApiClient::listPrograms() puts in the URL. Against the
    // original both of these were "'c%23' is currently not supported".
    $sharp = httpGet('/list/c%23');
    $plus = httpGet('/list/c%2B%2B');

    assertSame(200, $sharp['status'], 'c%23');
    assertContains('factorial.cs', $sharp['body'], 'and it lists the C# program');

    assertSame(200, $plus['status'], 'c%2B%2B');
    assertContains('factorial.cpp', $plus['body'], 'and it lists the C++ program');
});

serverTest('viewing a program returns its source', function () {
    $response = httpGet('/view/factorial.php');

    assertSame(200, $response['status'], 'status');
    assertContains('function factorial($n)', $response['body'], 'the source');
    assertSame('php', $response['headers']['x-cscli-language'], 'which language answered');
});

serverTest('a .php program name is routed rather than executed', function () {
    // The built-in server will happily run a .php file it thinks is being
    // asked for, which is why the README insists on the index.php argument.
    // Asserting on "Factorial of" would prove nothing - that string is in the
    // source as well as in the output - so this asserts on the things only
    // the source has.
    $response = httpGet('/view/php/factorial.php');

    assertContains('<?php', $response['body'], 'the source, including its opening tag');
    assertContains('$_GET', $response['body'], 'and the code, not what the code prints');
    assertContains('function factorial($n)', $response['body'], 'the function as written');
});

serverTest('the status line for a missing program is 404', function () {
    $response = httpGet('/view/nosuchfile.php');

    assertSame(404, $response['status'], 'status');
    assertContains("Program 'nosuchfile.php' not found.", $response['body'], 'the message');
});

serverTest('the status line for a missing language is 404', function () {
    assertSame(404, httpGet('/list/nosuchlanguage')['status'], 'status');
});

serverTest('the status line for a missing argument is 400', function () {
    assertSame(400, httpGet('/list')['status'], 'status');
    assertSame(400, httpGet('/view')['status'], 'status');
});

serverTest('/list/.. no longer names the project files', function () {
    // This is the bug, over a real socket. Against the original this
    // returned 200 and "README.md, index.php, review.md".
    $response = httpGet('/list/..');

    assertSame(404, $response['status'], 'status');
    assertNotContains('index.php', $response['body'], 'nothing from the project root is named');
    assertNotContains('README', $response['body'], 'nor the README');
});

serverTest('an encoded traversal reads nothing', function () {
    foreach (['/view/..%2F..%2Findex.php', '/view/..%2FREADME.md', '/list/..%2F..'] as $path) {
        $response = httpGet($path);

        assertSame(404, $response['status'], "$path is a 404");
        assertNotContains('<?php', $response['body'], "$path returns no source");
    }
});

serverTest('a conditional request over the wire is a 304', function () {
    $first = httpGet('/view/factorial.php');
    $etag = $first['headers']['etag'];

    assertMatches('/^"[0-9a-f]{64}"$/', $etag, 'the first response carries a tag');

    $second = httpGet('/view/factorial.php', ['If-None-Match' => $etag]);

    assertSame(304, $second['status'], 'status');
    assertSame('', $second['body'], 'and no body');
});

serverTest('the welcome text is still the welcome text', function () {
    assertSame(
        "Welcome to the backend of CSCLI!\n- use '/api' endpoint to list all the options\n",
        httpGet('/')['body'],
        'unchanged from the original'
    );
});

serverTest('/api is served as text', function () {
    $response = httpGet('/api');

    assertSame(200, $response['status'], 'status');
    assertContains('CSCLI API Reference', $response['body'], 'the reference');
});

serverTest('an unknown endpoint is a 404', function () {
    $response = httpGet('/nope');

    assertSame(404, $response['status'], 'status');
    assertContains("Unknown endpoint: '/nope'.", $response['body'], 'the message');
});
