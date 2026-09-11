<?php

declare(strict_types=1);

namespace Tests\Architecture\Support;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;

/**
 * Lightweight static-dependency-graph checker for the tests/Architecture
 * suite (CPNC §4.5). Deliberately narrow: this codebase's own conventions
 * (every cross-namespace reference goes through an explicit top-of-file
 * `use` statement — see CPNC §3, "no Hungarian prefixes", strict style
 * throughout) make scanning `use` imports sufficient without needing a
 * full symbol-resolution pass.
 */
final class DependencyScanner
{
    /** @return string[] absolute paths of every *.php file directly under $root (recursive) */
    public static function phpFiles(string $root): array
    {
        if (! is_dir($root)) {
            return [];
        }

        $finder = (new Finder)->in($root)->files()->name('*.php');

        return array_map(
            static fn ($file): string => $file->getPathname(),
            iterator_to_array($finder, false),
        );
    }

    /** @return string[] every class/interface/trait name imported via a `use` statement in this file */
    public static function importedNames(string $filePath): array
    {
        $finder = new NodeFinder;
        $ast = self::ast($filePath);
        $names = [];

        foreach ($finder->findInstanceOf($ast, Use_::class) as $use) {
            foreach ($use->uses as $useUse) {
                $names[] = $useUse->name->toString();
            }
        }

        foreach ($finder->findInstanceOf($ast, GroupUse::class) as $groupUse) {
            $prefix = $groupUse->prefix->toString();
            foreach ($groupUse->uses as $useUse) {
                $names[] = $prefix.'\\'.$useUse->name->toString();
            }
        }

        return $names;
    }

    /** The fully-qualified name of the first class/interface/trait declared in this file, if any. */
    public static function declaredTypeName(string $filePath): ?string
    {
        $ast = self::ast($filePath);
        $finder = new NodeFinder;

        $classLike = $finder->findFirstInstanceOf($ast, ClassLike::class);

        if ($classLike === null || $classLike->name === null) {
            return null;
        }

        $namespace = $finder->findFirstInstanceOf($ast, Namespace_::class);
        $prefix = $namespace?->name?->toString();

        return $prefix !== null ? "{$prefix}\\{$classLike->name->toString()}" : $classLike->name->toString();
    }

    /** @return string[] fully-qualified names this file's class `implements` (resolved against its `use` imports) */
    public static function implementedInterfaces(string $filePath): array
    {
        $class = (new NodeFinder)->findFirstInstanceOf(self::ast($filePath), Class_::class);

        if ($class === null || $class->implements === []) {
            return [];
        }

        $imports = self::importAliasMap($filePath);

        return array_map(
            static fn (Node\Name $name): string => $imports[$name->toString()] ?? $name->toString(),
            $class->implements,
        );
    }

    /** @return array<string, string> short-name => fully-qualified-name, from this file's `use` imports */
    private static function importAliasMap(string $filePath): array
    {
        $map = [];

        foreach (self::importedNames($filePath) as $fqcn) {
            $short = substr((string) strrchr($fqcn, '\\'), 1) ?: $fqcn;
            $map[$short] = $fqcn;
        }

        return $map;
    }

    /** @return Node[] */
    private static function ast(string $filePath): array
    {
        static $cache = [];

        return $cache[$filePath] ??= (new ParserFactory)
            ->createForNewestSupportedVersion()
            ->parse(file_get_contents($filePath) ?: '') ?? [];
    }
}
