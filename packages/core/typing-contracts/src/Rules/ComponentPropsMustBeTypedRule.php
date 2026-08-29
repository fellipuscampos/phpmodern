<?php

declare(strict_types=1);

namespace PhpModern\TypingContracts\Rules;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Enforces the framework's "typed props" contract on every Component
 * subclass: every promoted constructor property must have an explicit,
 * concrete type — no missing type, no `mixed`. This is the direct analogue
 * of banning `any` in strict TypeScript, applied to phpmodern components.
 *
 * Uses PHPStan's InClassNode (not the raw PhpParser Class_ node) because
 * that's the node type PHPStan guarantees a populated ClassReflection for —
 * $scope->getClassReflection() is null when a plain Class_ node is visited.
 *
 * @implements Rule<InClassNode>
 */
final class ComponentPropsMustBeTypedRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     * @return list<RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();

        if (!$classReflection->isSubclassOf('PhpModern\ComponentEngine\Component')) {
            return [];
        }

        $originalNode = $node->getOriginalNode();

        if (!$originalNode instanceof Class_) {
            return [];
        }

        $errors = [];

        foreach ($originalNode->getMethods() as $method) {
            if ($method->name->toLowerString() !== '__construct') {
                continue;
            }

            foreach ($method->params as $param) {
                if ($param->flags === 0) {
                    continue; // not a promoted property
                }

                $propertyName = $param->var->name ?? null;
                if (!is_string($propertyName)) {
                    continue;
                }

                if ($param->type === null) {
                    $errors[] = RuleErrorBuilder::message(sprintf(
                        'Component prop $%s in %s has no type declaration. phpmodern components require every prop to be explicitly typed, the same way a TypeScript component requires a typed prop interface.',
                        $propertyName,
                        $classReflection->getName(),
                    ))->identifier('phpmodern.untypedComponentProp')->line($param->getLine())->build();

                    continue;
                }

                if (self::containsMixed($param->type)) {
                    $errors[] = RuleErrorBuilder::message(sprintf(
                        'Component prop $%s in %s is typed as (or includes) mixed. phpmodern components ban mixed props — declare a concrete type, the same way strict TypeScript bans `any`.',
                        $propertyName,
                        $classReflection->getName(),
                    ))->identifier('phpmodern.mixedComponentProp')->line($param->getLine())->build();
                }
            }
        }

        return $errors;
    }

    private static function containsMixed(Node $type): bool
    {
        if ($type instanceof NullableType) {
            return self::containsMixed($type->type);
        }

        if (property_exists($type, 'types')) {
            /** @var array<Node> $subTypes */
            $subTypes = $type->types;

            foreach ($subTypes as $subType) {
                if (self::containsMixed($subType)) {
                    return true;
                }
            }

            return false;
        }

        return $type instanceof Identifier && strtolower($type->toString()) === 'mixed';
    }
}
