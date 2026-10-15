# cscli-api

An HTTP API that hands back source code. It knows about a tree of example
programs organised one directory per language, and it answers three questions
about it: which languages are there, which programs are in a language, and
what is in a program.

It exists to be the backend for [`cscli`](../cscli), a command-line client in
the sibling directory. Everything about it is shaped by that: it answers in
plain text so a terminal can print the response without parsing it, and it
answers in JSON when a client asks, so `cscli` does not have to scrape the
plain text.

## Running it

```sh
php -S localhost:6300 index.php
```

Any port will do. **The `index.php` argument is not optional.** PHP's built-in
server decides for itself whether a request path looks like a real file before
it calls anything of yours, and `/view/factorial.php` ends in `.php`, so
without a router script the server 404s — or worse, finds `src/php/factorial.php`
and *runs* it — before this project's code is reached.

There is nothing to install. No Composer dependencies, no database, no
configuration file. `composer.json` is there to declare the PHP version and
the licence.

Behind a real web server, point the document root at this directory and route
everything that is not a real file to `index.php`. Do not serve `src/` as
static files: the programs in it are meant to be read, and one of them runs if
a server executes it.

## What it answers

```
GET /                              a one-line greeting
GET /api                           the endpoint reference, as text
GET /languages                     the languages, one per directory in src/
GET /list/{language}               the programs in a language
GET /view/{program}                a program, searched for in every language
GET /view/{language}/{program}     a program in a named language
```

A program name on its own is looked for in each language in alphabetical
order and the first match answers. That is ambiguous if two languages have a
file with the same name, so every successful `/view` also carries an
`X-Cscli-Language` header naming the language it came from, and the
two-segment form lets a caller say which one it wants.

Names are taken from the URL percent-decoded, which matters more than it
sounds: two of the nine languages here are `c#` and `c++`, and a correct
client sends those as `c%23` and `c%2B%2B`.

## Plain text or JSON

Plain text by default, so `curl localhost:6300/languages` and a browser
address bar both give something readable:

```
Supported Languages:
- c
- c#
- c++
- fortran
...
```

Send `Accept: application/json` for the same data structured:

```sh
curl -H "Accept: application/json" localhost:6300/languages
{"languages":["c","c#","c++","fortran","java","javascript","php","prolog","python"]}
```

That applies to errors too. A failure in text is the message and then a line
saying what to try instead; in JSON it is `error` and `hint`.

## Status codes

| | |
| --- | --- |
| `200` | here it is |
| `304` | you already have this; see caching below |
| `400` | the request is missing something — `/list` with no language |
| `404` | no such route, language or program |
| `413` | the program exists but is larger than the server will serve |
| `500` | the program is listed but could not be read |

## Caching

Successful responses carry an `ETag` — a SHA-256 of the body — and
`Cache-Control: public, max-age=300`. Send the tag back as `If-None-Match` and
an unchanged answer is a `304` with no body:

```sh
curl -I localhost:6300/view/factorial.php
curl -H 'If-None-Match: "…"' localhost:6300/view/factorial.php
```

The tag is derived from the body rather than from a file's modification time,
because the list endpoints have no single file behind them and because a
checkout changes mtimes without changing bytes.

Error responses carry no `ETag`, so nothing caches a 404.

## Adding a language or a program

Make a directory under `src/` and put files in it. That is the whole
mechanism — there is no registry and nothing to edit. The directory name is
the language name as it appears in the API.

Files whose names begin with a dot are never listed. Everything else that is a
regular file is.

## How it is put together

```
index.php            reads the request, sends the response, decides nothing
utils/Router.php     a request path -> a list of decoded segments
utils/Catalog.php    the src/ tree: which languages, which programs, what is in one
utils/dispatch.php   the route table: a request -> a Response
utils/helpers.php    values -> bodies, in text or JSON
utils/Response.php   a decided response, before it reaches the socket
src/                 the programs being served
tests/               the suite
```

The split is not decoration. `dispatch()` takes a `Router` and a `Catalog` and
returns a `Response`; it reads no superglobal, sends no header and echoes
nothing. That is what lets the suite cover every route, every error and every
traversal payload without a socket — and it is why `index.php` is fifteen
lines of wiring.

### The one rule worth knowing

`Catalog` never builds a filesystem path out of a name the caller supplied
until that exact name has been found in a directory listing it read itself.
There is no check for `..`, no `realpath()` comparison, no list of forbidden
characters. Those are all attempts to describe the set of bad names; the set
of good names is three directory reads away and is the thing to compare
against.

This is why decoding `%23` in the router is safe, and it is the order the two
changes have to be made in. Decoding without the allowlist would have turned a
near miss into an arbitrary file read.

## Tests

```sh
php tests/run.php
```

86 tests, no dependencies, about two seconds. An optional argument runs one
file: `php tests/run.php Catalog`.

| | |
| --- | --- |
| `CatalogTest` | the source tree: listings, refusals, traversals, size limits |
| `RouterTest` | paths to segments, percent-decoding, malformed URIs |
| `DispatchTest` | every route and every status code, against a fixture tree |
| `ServerTest` | the same over a real socket, against the real `src/` |
| `OriginalTest` | what the code did before it was fixed |

`OriginalTest` is the one that needs explaining. It keeps a byte-exact copy of
the original `helpers.php` and `Router.php` under `tests/original/`, runs them
in a subprocess against a throwaway tree, and asserts that they *do* leak the
file names, and that they *do* refuse `c%23`. Its first test checks the copies
still match the baseline commit in git, so a copy that drifts fails loudly
rather than quietly asserting nothing.

`ServerTest` starts `php -S` on a port the OS picks, drives it over a raw
socket, and stops it afterwards. If it cannot get a port, those tests skip
rather than fail.

## Licence

Free for personal, non-commercial use. You may use, study and modify this
software for yourself, but you may not use it commercially, and you may not
share or publish it, in its original form or modified. See [LICENSE](LICENSE)
for the full terms.
