# Review — cscli-api

First reviewed 2026-09-04. Remediated 2026-09-18 on branch `cleanup/2026-09-18`.
86 tests, 216 assertions, 0 failures, about two seconds (`php tests/run.php`).

**Touched again on 2026-09-19, after the pass that finished it,** for one
argument in `tests/ServerTest.php`. It is a test-harness fix and changes nothing
about the project under test; the suite passes at the same numbers. See
"The `proc_open` orphan" at the end of this file. It is on the same branch, so
`git diff main` is still the whole of the work on this project.

## Corrections to the first review

Two things in the first review were not quite right, and both were checked by
running the original code rather than by reading it.

**The information leak is real, but narrower than described.** The first
review said `/list/..` disclosed "the names of every file there, `index.php`
and any config or dotfile included". It does not include dotfiles. The
original globbed `src/{lang}/*.*`, and `glob()` does not match a leading dot,
so a `.env` in the project root was never named — and neither was any file
without an extension. Stood up on a socket against a root containing a
planted `.env` and `config.php`, the original answers:

```
HTTP/1.0 200 OK
Programs in ..:
- config.php
- index.php
```

`config.php` is quite bad enough, so the finding stands and the severity does
not change. But "any dotfile" would have sent the next reader looking for a
disclosure that is not there.

**"Safe by accident" was the right diagnosis and the accident was already
costing something.** The first review said a full traversal could not get
through because the router never url-decodes, and warned that adding
`urldecode()` would turn this into an arbitrary file read. That is correct.
What it missed is that the missing decode was *itself a live bug*: the `cscli`
client builds its URLs with `rawurlencode()`, so `cscli programs c#` sent
`/list/c%23`, and the original answered

```
'c%23' is currently not supported.
```

Two of the nine languages in `src/` — `c#` and `c++` — could not be listed at
all through the official client. So the "completely natural change" the review
warned about was not hypothetical; it was a change somebody was going to have
to make. It has been made here, with the allowlist validation in the same
commit, which is the order the first review asked for.

## What was wrong, and what was done

### P1 — user-controlled path segments reached the filesystem

`listPrograms()` and `viewProgram()` concatenated a URL segment into a path
and asked `is_dir()` / `is_file()` about the result. Neither compared the
segment against anything.

Fixed by replacing both with `utils/Catalog.php`, which never builds a path
out of a caller's string until that exact string has been found in a directory
listing it read itself. There is deliberately no `..` check, no `realpath()`
comparison and no character blacklist — those describe the set of bad names,
and the set of good names is three directory reads away.

That single rule closes more than the reported leak:

- `/list/..` is now an ordinary 404, and the encoded forms (`%2E%2E`,
  `..%2F..`) are too.
- A NUL byte in a segment is a 404 rather than a PHP `ValueError`, because the
  membership test runs before any filesystem call.
- `is_dir()` on Windows matches case-insensitively and ignores trailing dots
  and spaces, so `/list/PHP`, `/list/php.` and `/list/php%20` all reached
  `src/php` on this machine and none of them would on a Linux host. Exact
  comparison makes the API answer the same on both.

### P1 (missed by the first review) — percent-encoded segments were never decoded

`Router` did `explode('/', $uri)` on the raw path. Evidence above. Fixed by
decoding each segment with `rawurldecode()` *after* the split, so a `%2F`
produces a segment containing a slash rather than a new route segment — and
that segment then matches no language and no program.

`rawurldecode()`, not `urldecode()`: `+` means a space only in a query string,
and `src/c++` is a real directory.

### P2 — `viewProgram` searched every language and the winner was undefined

It looped over `listLanguages()` and returned the first filename that matched,
in whatever order `glob()` produced. Two changes, both backward compatible,
because `Cscli\Api\ApiClient::viewProgram()` next door calls `/view/{program}`
and changing the route shape would break it:

- the languages are sorted, so the winner is defined;
- every successful `/view` carries `X-Cscli-Language`, so a caller can tell
  which one answered;
- and a new `/view/{language}/{program}` route says which one to use.

Nothing was changed in `Web/PHP/cscli`. The one-segment route behaves exactly
as it did.

### P3 — caching, size, filesystem errors

- Successful responses carry `ETag` (SHA-256 of the body) and
  `Cache-Control: public, max-age=300`; `If-None-Match` gets a 304. The tag is
  computed from the body because the list endpoints have no single file behind
  them, and because a checkout changes mtimes without changing bytes. Error
  responses deliberately carry no validator, so nothing caches a 404.
- `Catalog::MAX_FILE_BYTES` (1 MiB) is checked with `filesize()` *before*
  `file_get_contents()`, so an oversized file is never read into memory. It is
  refused with a 413 naming the size and the limit.
- Every filesystem call is guarded. An unreadable root reports no languages
  instead of printing a warning into the middle of the body — which for a JSON
  client meant a response that would not decode. There is a test that asserts
  nothing is echoed while a response is being decided.

### P3 — `response()->head()` exited inside the header setter

Every route had to remember to call `head()` or nothing was sent, and there
was no point at which a response existed as a value, so nothing could be
tested without a socket. Replaced by `utils/Response.php`: `dispatch()` returns
one, `index.php` sends it, in one place.

### P3 — no composer.json, no LICENSE

Both added. MIT, matching the rest of the archive and the sibling client.

### Smaller things found while working

- `$segments[1]` was tested for truthiness, so a language or program named
  `0` reported itself as missing. `Router::segment()` tests for the empty
  string instead.
- `parse_url()` returns `false` for a URI it cannot parse (`//:80`). That went
  straight into `trim()`. Now checked.
- `$_SERVER['REQUEST_URI']` was read unguarded, which is a deprecation notice
  under PHP 8.1+ wherever it is absent, and a notice lands in the response
  body.
- `json_encode()` returns `false` on invalid UTF-8, which would have produced
  a body of just a newline. `JSON_INVALID_UTF8_SUBSTITUTE` is passed now.
- Dotfiles were hidden by the `*.*` glob pattern rather than on purpose, which
  also hid any file with no extension. The rule is written down now:
  everything that is a regular file and does not start with a dot.

## Deliberately not done

**The `src/` tree was not touched.** `src/php/factorial.php` reads
`$_GET["n"]` and runs when a server executes it, and the README now says not
to serve `src/` statically — but the file itself is content being served, not
code being run by this project, and the other 59 files in there are one
person's coursework. Changing them is not this pass's business.

**`Content-Type` gained an explicit `; charset=utf-8`.** Worth flagging as the
one header that changed. In practice it changes nothing under a default PHP
configuration — `default_charset` already appended `;charset=UTF-8` to
`text/plain`, as the captured original response above shows — but it no longer
depends on an ini setting.

**The route shape was not changed, only added to.** See P2.

**History was not rewritten and nothing was pushed.** The repository has a
public remote (`github.com/nishantmajhi/cscli-api`). The leak was in the code,
not in the history, so there is nothing here needing the author's decision —
but the deployed copy, wherever it is, serves the original until this branch
reaches it.

## Notes for a future session

The working tree had already moved on from the single `first commit` when this
pass started — the content negotiation, the structured errors and eight of the
nine `src/` directories were uncommitted. All of it is in one commit whose
subject begins `Baseline: working tree as found`, so `git diff` against that
commit is this pass and nothing else. `tests/OriginalTest.php` looks that
commit up by its subject.

Two traps cost time here and are worth knowing:

**Do not use `file_get_contents()` with an `http://` context to test a server
from PHP.** The suite's end-to-end half went from two minutes to one second
when it switched to a raw socket. The stream wrapper follows redirects, and
the traversal payloads (`/view/..%2F..%2Findex.php`) are exactly the paths
that provoke one — so each of those assertions was paying for a redirect chain
before it got an answer. It also means the status code being asserted is not
necessarily the status code the server sent. `fsockopen` with an explicit
`HTTP/1.0` and `Connection: close` is about fifteen lines and is what
`tests/ServerTest.php` does now.

**Do not give `proc_open` a pipe for a dev server's stderr unless something
reads it.** `php -S` logs a line per request; the pipe fills after a few
kilobytes and the server blocks mid-response, which looks exactly like a hung
test. `['file', $log, 'a']` for both streams.

**And on Windows, give `proc_open` `['bypass_shell' => true]`.** That one is
below, because it was missed here and found a pass later.

If the sibling `cscli` client is ever worked on, `/view/{language}/{program}`
and the `X-Cscli-Language` header are both there to be used, and
`cscli programs 'c#'` should be checked against a running server — it is the
case that was broken for the whole life of the project.

## The `proc_open` orphan

Fixed 2026-09-19. Found while writing the equivalent `tests/ServerTest.php` for
`Web/PHP/FraGenesis`, which hit the same thing.

`tests/ServerTest.php` started the dev server with `proc_open()` and no
`bypass_shell` option. On Windows that runs the command through `cmd.exe`, so the
process handle `proc_open()` returns is the shell's — and `proc_terminate()`
killed the shell and left `php -S` running underneath it. Two consequences, the
second of which is the one that wastes an afternoon:

- **Every run of this suite left a `php.exe` listening on a random port.** Four
  were found on this machine on 2026-09-19 before anybody went looking for them.
- **An orphan started that way has inherited the write end of whatever pipe the
  suite's own stdout is going to,** so `php tests/run.php | tail` never saw EOF.
  The suite itself finished in two seconds; the *pipeline* then sat there for as
  long as the orphan lived. Measured before the fix: two seconds redirected to a
  file, **eight minutes** through a pipe, passing at 86/216 either way.

That was demonstrated rather than assumed, because "the tests are slow" and "the
pipeline cannot see EOF" look identical from outside. A piped run was started in
the background and was still blocked twenty seconds later with one orphan alive;
killing the orphan released it instantly, and the completed output — all 86 tests
passing — was there the moment it did. The tests had never been slow.

The fix is the two extra arguments to `proc_open()`: `null` for the environment,
and `['bypass_shell' => true]` for the options. Verified by running the suite
three times piped and once redirected: two seconds each, 86/216 each, and zero
`php.exe` processes left behind afterwards.

Nothing in `src/` or `utils/` was touched, and no test assertion changed.

## Licence, 2026-10-02

The author chose one licence for all of their projects: free for personal,
non-commercial use, modifiable for the user's own use, and not to be
distributed, original or modified. No standard licence says that, so `LICENSE`
is a short custom text, the same in every project apart from the year and what
it excludes. The README's licence section says the same, and `composer.json`
declares `proprietary`, Composer's word for a licence with no SPDX identifier.
Anything above that calls the licence an open question, or calls the project
MIT, is superseded.

## CI, 2026-10-02

`.github/workflows/ci.yml` runs `tests/run.php` on PHP 8.4, on
`ubuntu-latest`, with full history for `OriginalTest`. Nothing has been
pushed, so it has not run on GitHub yet. Every job was run before committing,
from a fresh clone of this branch, the way a runner would run it: Linux jobs
in a throwaway Ubuntu 24.04 (the image `ubuntu-latest` uses) with the same
tool versions. 86.
