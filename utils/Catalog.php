<?php

declare(strict_types=1);

/**
 * The source tree: which languages exist, which programs are in each, and
 * what is in a given program.
 *
 * Every name this class is given arrived in a URL, so no method interpolates
 * a caller's string into a path before that exact string has been found in a
 * directory listing this class built itself. That is the whole security
 * posture and it is deliberately the only one: there is no check for "..", no
 * realpath() comparison, no pattern of forbidden characters. Those are all
 * ways of trying to describe the set of bad names, and the set of good names
 * is right here and is three directory reads long.
 *
 * The root is a constructor argument rather than being derived from __DIR__ so
 * that the tests can point it at a throwaway tree.
 */
final class Catalog
{
    /**
     * Refuse to read anything larger than this.
     *
     * Everything in src/ is a few kilobytes of teaching code. The guard is
     * here because file_get_contents() will read whatever it is pointed at
     * into memory and the response is built in memory too, so one large file
     * dropped into src/ by accident would otherwise be a memory limit away
     * from taking the process down.
     */
    public const MAX_FILE_BYTES = 1048576;

    private string $root;

    public function __construct(string $root)
    {
        $this->root = rtrim($root, DIRECTORY_SEPARATOR . '/');
    }

    /**
     * The languages, one per directory under the root, sorted by name.
     *
     * Sorted because the order is visible twice: in the /languages response,
     * and in which directory viewProgram() finds a duplicate filename in
     * first. glob() and scandir() both happen to return sorted results on the
     * platforms this runs on, but neither promises to, and "which one wins"
     * should not be a property of the filesystem.
     *
     * @return list<string>
     */
    public function languages(): array
    {
        $entries = @scandir($this->root);

        if ($entries === false) {
            // An unreadable root is a deployment problem, not a request
            // problem. Report it as no languages rather than letting a
            // warning land in the middle of the response body.
            return [];
        }

        $languages = [];
        foreach ($entries as $entry) {
            if ($this->isHidden($entry)) {
                continue;
            }
            if (is_dir($this->root . DIRECTORY_SEPARATOR . $entry)) {
                $languages[] = $entry;
            }
        }

        sort($languages, SORT_STRING);

        return $languages;
    }

    /** Whether a language exists, by exact name. */
    public function hasLanguage(string $language): bool
    {
        return in_array($language, $this->languages(), true);
    }

    /**
     * The programs in one language, sorted by name, or null if there is no
     * such language.
     *
     * The membership test is what makes the path below safe, and it is an
     * identity comparison on purpose. is_dir() is not one: on Windows it
     * matches case-insensitively, ignores trailing dots and spaces, and
     * resolves 8.3 short names, so "PHP", "php." and "php " would all reach
     * the same directory here and none of them on a Linux host.
     *
     * @return list<string>|null
     */
    public function programs(string $language): ?array
    {
        if (!$this->hasLanguage($language)) {
            return null;
        }

        $path = $this->root . DIRECTORY_SEPARATOR . $language;
        $entries = @scandir($path);

        if ($entries === false) {
            return [];
        }

        $programs = [];
        foreach ($entries as $entry) {
            if ($this->isHidden($entry)) {
                continue;
            }
            if (is_file($path . DIRECTORY_SEPARATOR . $entry)) {
                $programs[] = $entry;
            }
        }

        sort($programs, SORT_STRING);

        return $programs;
    }

    /** Whether a program exists in a language, by exact name. */
    public function hasProgram(string $language, string $program): bool
    {
        return in_array($program, $this->programs($language) ?? [], true);
    }

    /**
     * Reads one program.
     *
     * @return array{status: string, language?: string, contents?: string, size?: int}
     *         status is 'ok', 'missing', 'too-large' or 'unreadable'.
     */
    public function read(string $language, string $program): array
    {
        if (!$this->hasProgram($language, $program)) {
            return ['status' => 'missing'];
        }

        $path = $this->root . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . $program;
        $size = @filesize($path);

        if ($size === false) {
            return ['status' => 'unreadable'];
        }

        if ($size > self::MAX_FILE_BYTES) {
            return ['status' => 'too-large', 'size' => $size];
        }

        $contents = @file_get_contents($path);

        if ($contents === false) {
            return ['status' => 'unreadable'];
        }

        return ['status' => 'ok', 'language' => $language, 'contents' => $contents];
    }

    /**
     * Finds a program by name alone, searching the languages in order.
     *
     * The API has always had a /view/{program} route with no language in it,
     * and the cscli client calls it, so it stays. What changes is that the
     * order is now the sorted language order rather than whatever the
     * filesystem returned, and that the answer says which language it came
     * from so a caller can tell a collision happened.
     *
     * @return array{status: string, language?: string, contents?: string, size?: int}
     */
    public function find(string $program): array
    {
        foreach ($this->languages() as $language) {
            $attempt = $this->read($language, $program);

            if ($attempt['status'] !== 'missing') {
                return $attempt;
            }
        }

        return ['status' => 'missing'];
    }

    /**
     * Every language a program name appears in, in language order.
     *
     * @return list<string>
     */
    public function languagesContaining(string $program): array
    {
        $found = [];

        foreach ($this->languages() as $language) {
            if ($this->hasProgram($language, $program)) {
                $found[] = $language;
            }
        }

        return $found;
    }

    /**
     * Entries that are never listed: the two directory links, and anything
     * beginning with a dot.
     *
     * The original hid dotfiles by accident - its glob pattern was "*.*",
     * and glob() does not match a leading dot - which also meant a file with
     * no extension was invisible. Hiding them is worth keeping and hiding
     * Makefile is not, so the rule is written down instead of emerging from
     * a pattern.
     */
    private function isHidden(string $entry): bool
    {
        return $entry === '' || $entry[0] === '.';
    }
}
