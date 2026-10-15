<?php

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Fixture.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'utils' . DIRECTORY_SEPARATOR . 'dispatch.php';

/** One request against the fixture tree. */
function request(string $uri, bool $json = false, ?string $ifNoneMatch = null): Response
{
    return dispatch(new Router($uri), new Catalog(Fixture::src()), $json, $ifNoneMatch);
}

test('the root is the welcome text', function () {
    $response = request('/');

    assertSame(200, $response->status, 'status');
    assertSame(
        "Welcome to the backend of CSCLI!\n- use '/api' endpoint to list all the options\n",
        $response->body,
        'the body is unchanged from the original'
    );
});

test('/api is the reference text and ignores Accept', function () {
    // It is prose for a human either way; there is nothing to structure.
    assertSame(request('/api')->body, request('/api', true)->body, 'the same text for both');
    assertContains('CSCLI API Reference', request('/api')->body, 'the heading');
});

test('/api documents the two-segment view route', function () {
    assertContains('GET /view/{language_name}/{program_name}', request('/api')->body, 'the new route is listed');
});

test('/languages lists them as text', function () {
    $response = request('/languages');

    assertSame(200, $response->status, 'status');
    assertSame("Supported Languages:\n- alpha\n- beta\n- c#\n- c++\n", $response->body, 'the body');
    assertSame('text/plain; charset=utf-8', $response->contentType, 'content type');
});

test('/languages lists them as JSON when asked', function () {
    $response = request('/languages', true);

    assertSame('application/json', $response->contentType, 'content type');
    assertSame(
        ['languages' => ['alpha', 'beta', 'c#', 'c++']],
        json_decode($response->body, true),
        'the decoded body'
    );
});

test('/list with no language is a 400 that says what to type', function () {
    $response = request('/list');

    assertSame(400, $response->status, 'status');
    assertContains('Missing language name.', $response->body, 'what went wrong');
    assertContains('/list/{language_name}', $response->body, 'and what to do about it');
});

test('/list/ with a trailing slash is the same 400', function () {
    assertSame(400, request('/list/')->status, 'an empty segment is an absent one');
});

test('/list/{language} lists its programs', function () {
    $response = request('/list/beta');

    assertSame(200, $response->status, 'status');
    assertSame("Programs in beta:\n- one.txt\n- two.txt\n", $response->body, 'the body');
});

test('/list/{language} in JSON', function () {
    assertSame(
        ['programs' => ['one.txt', 'two.txt']],
        json_decode(request('/list/beta', true)->body, true),
        'the decoded body'
    );
});

test('/list of an unknown language is a 404 pointing at /languages', function () {
    $response = request('/list/nosuchlanguage');

    assertSame(404, $response->status, 'status');
    assertContains("'nosuchlanguage' is currently not supported.", $response->body, 'what went wrong');
    assertContains('/languages', $response->body, 'and where to look');
});

test('/list/.. is a 404 and names nothing outside the source tree', function () {
    // The headline bug. Against the original this route returned 200 and a
    // list of the files in the project root.
    $response = request('/list/..');

    assertSame(404, $response->status, 'status');
    assertNotContains('secret.txt', $response->body, 'nothing from outside src/ is named');
    assertContains("'..' is currently not supported.", $response->body, 'it is just an unknown language');
});

test('/list of an encoded traversal is a 404 too', function () {
    foreach (['/list/%2E%2E', '/list/..%2F..', '/list/%2e%2e%2f%2e%2e'] as $uri) {
        $response = request($uri);

        assertSame(404, $response->status, "$uri is refused");
        assertNotContains('secret.txt', $response->body, "$uri names nothing outside src/");
    }
});

test('/list of a language whose name needed encoding now works', function () {
    // This is what `cscli programs c#` sends, and against the original it
    // was a 404 saying the language is not supported.
    $response = request('/list/c%23');

    assertSame(200, $response->status, 'c%23 reaches c#');
    assertSame("Programs in c#:\n- sharp.cs\n", $response->body, 'the body');

    $plus = request('/list/c%2B%2B');

    assertSame(200, $plus->status, 'c%2B%2B reaches c++');
    assertSame("Programs in c++:\n- plus.cpp\n", $plus->body, 'the body');
});

test('/view with no program is a 400 that says what to type', function () {
    $response = request('/view');

    assertSame(400, $response->status, 'status');
    assertContains('Missing program name.', $response->body, 'what went wrong');
    assertContains('/view/{program_name}', $response->body, 'and what to do about it');
});

test('/view/{program} returns the source and names its language', function () {
    $response = request('/view/two.txt');

    assertSame(200, $response->status, 'status');
    assertSame("beta two\n", $response->body, 'the bytes');
    assertSame('text/plain; charset=utf-8', $response->contentType, 'content type');
    assertSame('beta', $response->allHeaders()['X-Cscli-Language'], 'which language answered');
});

test('/view/{program} of an ambiguous name resolves in language order', function () {
    $response = request('/view/one.txt');

    assertSame("alpha one\n", $response->body, 'alpha sorts first');
    assertSame('alpha', $response->allHeaders()['X-Cscli-Language'], 'and says so');
});

test('/view/{language}/{program} picks the other one', function () {
    $response = request('/view/beta/one.txt');

    assertSame(200, $response->status, 'status');
    assertSame("beta one\n", $response->body, 'the file in beta, not the one in alpha');
    assertSame('beta', $response->allHeaders()['X-Cscli-Language'], 'and says so');
});

test('/view/{language}/{program} of an unknown language is a 404', function () {
    $response = request('/view/nosuchlanguage/one.txt');

    assertSame(404, $response->status, 'status');
    assertContains("'nosuchlanguage' is currently not supported.", $response->body, 'the language is named');
});

test('/view/{language}/{program} of a program in the wrong language is a 404', function () {
    $response = request('/view/alpha/two.txt');

    assertSame(404, $response->status, 'status');
    assertContains("Program 'two.txt' not found.", $response->body, 'the program is named');
});

test('/view of an unknown program is a 404 pointing at /list', function () {
    $response = request('/view/nosuchfile.txt');

    assertSame(404, $response->status, 'status');
    assertContains("Program 'nosuchfile.txt' not found.", $response->body, 'what went wrong');
    assertContains('/list/{language_name}', $response->body, 'and where to look');
});

test('a traversal through /view returns nothing from outside the source tree', function () {
    $payloads = [
        '/view/..%2F..%2Fsecret.txt',
        '/view/%2E%2E%2Fsecret.txt',
        '/view/alpha/..%2F..%2Fsecret.txt',
        '/view/..%2F..%2Findex.php',
        '/view/secret.txt',
        '/view/%2Fetc%2Fpasswd',
    ];

    foreach ($payloads as $uri) {
        $response = request($uri);

        assertSame(404, $response->status, "$uri is a 404");
        assertNotContains('top secret', $response->body, "$uri returns no file contents");
    }
});

test('a NUL byte in a segment is a 404, not a 500', function () {
    $response = request('/view/one.txt%00.png');

    assertSame(404, $response->status, 'status');
});

test('an unknown endpoint is a 404 that echoes the path as it was sent', function () {
    $response = request('/nope');

    assertSame(404, $response->status, 'status');
    assertContains("Unknown endpoint: '/nope'.", $response->body, 'the path is echoed');
    assertContains('/api', $response->body, 'and points at the reference');
});

test('errors carry the hint in a hint field when JSON was asked for', function () {
    $decoded = json_decode(request('/list/nosuchlanguage', true)->body, true);

    assertSame('application/json', request('/list/nosuchlanguage', true)->contentType, 'content type');
    assertSame("'nosuchlanguage' is currently not supported.", $decoded['error'], 'the error');
    assertContains('/languages', $decoded['hint'], 'the hint');
});

test('a 200 carries an ETag and a Cache-Control', function () {
    $headers = request('/languages')->allHeaders();

    assertMatches('/^"[0-9a-f]{64}"$/', $headers['ETag'], 'a quoted sha256');
    assertSame('public, max-age=300', $headers['Cache-Control'], 'cache control');
});

test('errors carry no ETag', function () {
    // A validator on an error body would let a client cache a 404.
    assertFalse(isset(request('/nope')->allHeaders()['ETag']), 'no ETag on a 404');
    assertFalse(isset(request('/list')->allHeaders()['ETag']), 'no ETag on a 400');
});

test('the ETag follows the body, not the route', function () {
    $alpha = request('/view/alpha/one.txt')->allHeaders()['ETag'];
    $beta = request('/view/beta/one.txt')->allHeaders()['ETag'];

    assertNotSame($alpha, $beta, 'different bytes, different tag');
    assertSame($alpha, request('/view/one.txt')->allHeaders()['ETag'], 'same bytes, same tag');
});

test('a matching If-None-Match is a 304 with no body', function () {
    $etag = request('/view/two.txt')->allHeaders()['ETag'];
    $response = request('/view/two.txt', false, $etag);

    assertSame(304, $response->status, 'status');
    assertSame($etag, $response->allHeaders()['ETag'], 'the tag is repeated');
});

test('a stale If-None-Match is a fresh 200', function () {
    $response = request('/view/two.txt', false, '"not-the-tag"');

    assertSame(200, $response->status, 'status');
    assertSame("beta two\n", $response->body, 'the body is sent');
});

test('If-None-Match accepts a list, a weak tag and a star', function () {
    $etag = request('/view/two.txt')->allHeaders()['ETag'];

    assertSame(304, request('/view/two.txt', false, '*')->status, 'a star matches anything');
    assertSame(304, request('/view/two.txt', false, 'W/' . $etag)->status, 'a weak tag matches');
    assertSame(304, request('/view/two.txt', false, '"other", ' . $etag)->status, 'one of a list matches');
    assertSame(200, request('/view/two.txt', false, '"a", "b"')->status, 'a list of misses does not');
});

test('an oversized program is refused with a 413 rather than read into memory', function () {
    $path = Fixture::src() . DIRECTORY_SEPARATOR . 'beta' . DIRECTORY_SEPARATOR . 'big.txt';
    file_put_contents($path, str_repeat('x', Catalog::MAX_FILE_BYTES + 1));

    try {
        $response = request('/view/beta/big.txt');

        assertSame(413, $response->status, 'status');
        assertContains("Program 'big.txt' is too large to serve.", $response->body, 'what went wrong');
        assertContains((string) (Catalog::MAX_FILE_BYTES + 1), $response->body, 'and by how much');
    } finally {
        unlink($path);
    }
});

test('no response body carries a PHP warning', function () {
    // The original called glob() and file_get_contents() unguarded, so an
    // unreadable directory printed a warning into the middle of the body -
    // which for a JSON client meant an undecodable response.
    $catalog = new Catalog(Fixture::root() . DIRECTORY_SEPARATOR . 'nosuchdirectory');

    ob_start();
    $response = dispatch(new Router('/languages'), $catalog, true);
    $stray = ob_get_clean();

    assertSame('', $stray, 'nothing was printed while deciding');
    assertSame(['languages' => []], json_decode($response->body, true), 'and the JSON decodes');
});

test('an empty listing says so rather than printing an empty bullet', function () {
    $empty = Fixture::src() . DIRECTORY_SEPARATOR . 'gamma';
    mkdir($empty);

    try {
        assertSame(
            "Programs in gamma: (none found)\n",
            request('/list/gamma')->body,
            'the text form'
        );
        assertSame(
            ['programs' => []],
            json_decode(request('/list/gamma', true)->body, true),
            'the JSON form'
        );
    } finally {
        rmdir($empty);
    }
});
