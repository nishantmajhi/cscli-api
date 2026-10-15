<?php

/**
 * Builds a throwaway source tree under the system temp directory.
 *
 * Every test that touches the filesystem runs against one of these rather than
 * against the real `src/`, for two reasons. The real tree would have to grow a
 * second language directory containing a file with the same name as one in the
 * first before the "which language wins" tests could say anything, and the
 * traversal tests need a file *outside* `src/` that it is safe to leak.
 *
 * The layout is deliberately awkward in the four ways the code has to survive:
 *
 *   root/secret.txt          a file outside src/ that `/list/..` used to name
 *   root/src/alpha/one.txt   \_ the same program name in two languages, so that
 *   root/src/beta/one.txt    /  "first directory wins" is observable
 *   root/src/beta/two.txt
 *   root/src/c#/sharp.cs     a language whose name does not survive a URL intact
 *   root/src/c++/plus.cpp    the other one
 *   root/src/alpha/.hidden   a dotfile, which must never be listed
 */
final class Fixture
{
    private static ?string $root = null;

    /** Returns the fixture root, building it on first use. */
    public static function root(): string
    {
        if (self::$root !== null) {
            return self::$root;
        }

        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cscli-api-tests-' . getmypid();
        self::removeTree($root);

        self::write($root . '/secret.txt', "top secret\n");
        self::write($root . '/src/alpha/one.txt', "alpha one\n");
        self::write($root . '/src/alpha/.hidden', "should never be listed\n");
        self::write($root . '/src/beta/one.txt', "beta one\n");
        self::write($root . '/src/beta/two.txt', "beta two\n");
        self::write($root . '/src/c#/sharp.cs', "sharp\n");
        self::write($root . '/src/c++/plus.cpp', "plus\n");

        register_shutdown_function(static fn () => self::removeTree($root));

        return self::$root = $root;
    }

    /** The `src/` directory inside the fixture. */
    public static function src(): string
    {
        return self::root() . DIRECTORY_SEPARATOR . 'src';
    }

    private static function write(string $path, string $contents): void
    {
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException("could not create $dir");
        }
        file_put_contents($path, $contents);
    }

    private static function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            if (is_file($path)) {
                unlink($path);
            }
            return;
        }

        foreach (scandir($path) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                self::removeTree($path . DIRECTORY_SEPARATOR . $entry);
            }
        }

        rmdir($path);
    }
}
