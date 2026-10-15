<?php

declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Catalog.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Response.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'Router.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'helpers.php';

/**
 * The whole route table, as a function from a request to a Response.
 *
 * It takes a Router and a Catalog and returns a value. It does not read a
 * superglobal, does not send a header and does not echo anything, which is
 * the only reason the suite can cover every route and every error path
 * without standing up a server.
 *
 * @param string|null $ifNoneMatch The If-None-Match request header, if any.
 */
function dispatch(Router $router, Catalog $catalog, bool $json = false, ?string $ifNoneMatch = null): Response
{
    $response = route($router, $catalog, $json);

    return cacheable($response, $ifNoneMatch);
}

/** The routing decision itself, before any caching headers are worked out. */
function route(Router $router, Catalog $catalog, bool $json): Response
{
    $uri = $router->getUri();

    if ($uri === '') {
        return new Response(
            200,
            "Welcome to the backend of CSCLI!\n- use '/api' endpoint to list all the options\n"
        );
    }

    switch ($router->getSegments()[0] ?? '') {
        case 'api':
            return new Response(200, showEndpoints());

        case 'languages':
            [$body, $type] = listResponse('Supported Languages', 'languages', $catalog->languages(), $json);
            return new Response(200, $body, $type);

        case 'list':
            return listRoute($router->segment(1), $catalog, $json);

        case 'view':
            return viewRoute($router->segment(1), $router->segment(2), $catalog, $json);

        default:
            [$body, $type] = errorResponse(
                "Unknown endpoint: '/$uri'.",
                "See /api for the list of available endpoints.",
                $json
            );
            return new Response(404, $body, $type);
    }
}

/** GET /list/{language} */
function listRoute(?string $language, Catalog $catalog, bool $json): Response
{
    if ($language === null) {
        [$body, $type] = errorResponse(
            "Missing language name.",
            "Usage: /list/{language_name}, e.g. /list/php. See /api for the full reference.",
            $json
        );
        return new Response(400, $body, $type);
    }

    $programs = $catalog->programs($language);

    if ($programs === null) {
        [$body, $type] = errorResponse(
            "'$language' is currently not supported.",
            "See /languages for the list of supported languages.",
            $json
        );
        return new Response(404, $body, $type);
    }

    [$body, $type] = listResponse("Programs in $language", 'programs', $programs, $json);

    return new Response(200, $body, $type);
}

/**
 * GET /view/{program} and GET /view/{language}/{program}.
 *
 * The one-segment form is the one the cscli client calls and it keeps
 * working exactly as it did: the name is looked for in every language and
 * the first match answers. What it gained is a defined order - the
 * languages are sorted, so the winner no longer depends on the filesystem -
 * and an X-Cscli-Language header naming the language that answered, which
 * is the only way a caller could previously tell that a name was ambiguous.
 *
 * The two-segment form is new and is the unambiguous one.
 */
function viewRoute(?string $first, ?string $second, Catalog $catalog, bool $json): Response
{
    if ($first === null) {
        [$body, $type] = errorResponse(
            "Missing program name.",
            "Usage: /view/{program_name}, e.g. /view/factorial.php. See /api for the full reference.",
            $json
        );
        return new Response(400, $body, $type);
    }

    if ($second === null) {
        $program = $first;
        $result = $catalog->find($program);
    } else {
        $program = $second;

        if (!$catalog->hasLanguage($first)) {
            [$body, $type] = errorResponse(
                "'$first' is currently not supported.",
                "See /languages for the list of supported languages.",
                $json
            );
            return new Response(404, $body, $type);
        }

        $result = $catalog->read($first, $program);
    }

    switch ($result['status']) {
        case 'ok':
            return new Response(
                200,
                $result['contents'],
                'text/plain; charset=utf-8',
                ['X-Cscli-Language' => $result['language']]
            );

        case 'too-large':
            [$body, $type] = errorResponse(
                "Program '$program' is too large to serve.",
                sprintf(
                    'It is %d bytes and the limit is %d.',
                    $result['size'],
                    Catalog::MAX_FILE_BYTES
                ),
                $json
            );
            return new Response(413, $body, $type);

        case 'unreadable':
            [$body, $type] = errorResponse(
                "Program '$program' could not be read.",
                "It is listed but the server could not open it; this is a problem with the server, not the request.",
                $json
            );
            return new Response(500, $body, $type);

        default:
            [$body, $type] = errorResponse(
                "Program '$program' not found.",
                "See /list/{language_name} to browse available programs.",
                $json
            );
            return new Response(404, $body, $type);
    }
}

/**
 * Adds validators to a successful response, and turns it into a 304 if the
 * client already has that exact body.
 *
 * Source files change rarely and the cscli client fetches the same handful
 * over and over, so this is most of what makes repeated use cheap. The tag
 * is a hash of the body rather than an mtime because the list endpoints have
 * no single file behind them, and because an mtime changes when a checkout
 * does even though the bytes do not.
 */
function cacheable(Response $response, ?string $ifNoneMatch): Response
{
    if ($response->status !== 200) {
        return $response;
    }

    $etag = '"' . hash('sha256', $response->body) . '"';

    if ($ifNoneMatch !== null && etagMatches($ifNoneMatch, $etag)) {
        return new Response(304, '', $response->contentType, $response->headers + [
            'ETag' => $etag,
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    return $response->withHeaders([
        'ETag' => $etag,
        'Cache-Control' => 'public, max-age=300',
    ]);
}

/**
 * Whether an If-None-Match header covers a given tag.
 *
 * The header is a comma-separated list, may be "*", and each entry may carry
 * a W/ weak prefix. Weak comparison is the one RFC 9110 specifies for
 * If-None-Match, so W/"x" and "x" match.
 */
function etagMatches(string $ifNoneMatch, string $etag): bool
{
    $header = trim($ifNoneMatch);

    if ($header === '*') {
        return true;
    }

    $strip = static function (string $tag): string {
        $tag = trim($tag);

        return str_starts_with($tag, 'W/') ? substr($tag, 2) : $tag;
    };

    foreach (explode(',', $header) as $candidate) {
        if ($strip($candidate) === $strip($etag)) {
            return true;
        }
    }

    return false;
}
