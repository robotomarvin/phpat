<?php

namespace PhpAT\Parser\Ast;

use PhpAT\Parser\Ast\Type\PhpType;
use PhpAT\Parser\Relation\AbstractRelation;
use PhpAT\Parser\Relation\Composition;
use PhpAT\Parser\Relation\Dependency;
use PhpAT\Parser\Relation\Inheritance;
use PhpAT\Parser\Relation\Mixin;

final class Classmap
{
    private static array $classmap = [];

    public static function registerClass(
        FullClassName $className,
        string $pathname,
        string $classType,
        ?int $flag
    ) {
        if (!isset(static::$classmap[$className->getFQCN()])) {
            static::$classmap[$className->getFQCN()] = [
                'pathname' => $pathname,
                'type' => $classType,
                'flag' => $flag
            ];
        }
    }

    public static function registerClassImplements(FullClassName $classImplementing, FullClassName $classImplemented)
    {
        static::$classmap[$classImplementing->getFQCN()]['implements'][] = $classImplemented;
    }

    public static function registerClassExtends(FullClassName $classExtending, FullClassName $classExtended)
    {
        static::$classmap[$classExtending->getFQCN()]['extends'][] = $classExtended;
    }

    public static function registerClassIncludesTrait(FullClassName $classUsing, FullClassName $classUsed)
    {
        static::$classmap[$classUsing->getFQCN()]['includes-trait'][] = $classUsed;
    }

    public static function registerClassDepends(FullClassName $classDepending, FullClassName $classDepended)
    {
        if (PhpType::isBuiltinType($classDepended->getFQCN()) || PhpType::isSpecialType($classDepended->getFQCN())) {
            return;
        }

        static::$classmap[$classDepending->getFQCN()]['depends'][] = $classDepended;
    }

    public static function getClassmap(): array
    {
        return static::translateClassmap(static::$classmap);
    }

    /**
     * Temporary BC structure
     */
    private static function translateClassmap(array $classmap): array
    {
        foreach ($classmap as $className => $properties) {
            $srcNodes[$className] = new SrcNode(
                $properties['pathname'],
                FullClassName::createFromFQCN($className),
                array_merge(
                    static::addRelations(Dependency::class, $properties['depends'] ?? []),
                    static::addRelations(Inheritance::class, $properties['extends'] ?? []),
                    static::addRelations(Composition::class, $properties['implements'] ?? []),
                    static::addRelations(Mixin::class, $properties['includes-trait'] ?? [])
                )
            );
        }

        return $srcNodes ?? [];
    }

    /**
     * @param FullClassName[] $className
     * @return AbstractRelation[]
     */
    private static function addRelations(string $type, array $className): array
    {
        foreach ($className as $name) {
            $result[] = new $type(0, $name);
        }

        return $result ?? [];
    }
}
