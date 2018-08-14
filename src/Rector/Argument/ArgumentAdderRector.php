<?php declare(strict_types=1);

namespace Rector\Rector\Argument;

use PhpParser\BuilderHelpers;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\ClassMethod;
use Rector\Configuration\Rector\ArgumentAdderRecipe;
use Rector\RectorDefinition\ConfiguredCodeSample;
use Rector\RectorDefinition\RectorDefinition;

final class ArgumentAdderRector extends AbstractArgumentRector
{
    /**
     * @var ArgumentAdderRecipe[]
     */
    private $recipes = [];

    /**
     * @var ArgumentAdderRecipe[]
     */
    private $activeRecipes = [];

    /**
     * @param mixed[] $argumentChangesByMethodAndType
     */
    public function __construct(array $argumentChangesByMethodAndType)
    {
        foreach ($argumentChangesByMethodAndType as $configurationArray) {
            $this->recipes[] = ArgumentAdderRecipe::createFromArray($configurationArray);
        }
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition(
            'This Rector adds new default arguments in calls of defined methods and class types.',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
$someObject = new SomeClass;
$someObject->someMethod();
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
$someObject = new SomeClass;
$someObject->someMethod(true);
CODE_SAMPLE
                    ,
                    [
                        '$argumentChangesByMethodAndType' => [
                            'class' => 'SomeClass',
                            'method' => 'someMethod',
                            'position' => 0,
                            'default_value' => 'true',
                        ],
                    ]
                ),
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
class MyCustomClass extends SomeClass
{
    public function someMethod()
    {
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
class MyCustomClass extends SomeClass
{
    public function someMethod($value = true)
    {
    }
}
CODE_SAMPLE
                    ,
                    [
                        '$argumentChangesByMethodAndType' => [
                            'class' => 'SomeClass',
                            'method' => 'someMethod',
                            'position' => 0,
                            'default_value' => 'true',
                        ],
                    ]
                ),
            ]
        );
    }

    public function getNodeType(): string
    {
        return [MethodCall::class, StaticCall::class, ClassMethod::class];
    }

    /**
     * @param MethodCall|StaticCall|ClassMethod $node
     */
    public function refactor(Node $node): Node
    {
        if (! $this->isValidInstance($node)) {
            return null;
        }
        $this->activeRecipes = $this->matchArgumentChanges($node);
        if ((bool) $this->activeRecipes === false) {
            return null;
        }
        $argumentsOrParameters = $this->getNodeArgumentsOrParameters($node);
        $argumentsOrParameters = $this->processArgumentNodes($argumentsOrParameters);

        $this->setNodeArgumentsOrParameters($node, $argumentsOrParameters);

        return $node;
    }

    /**
     * @return ArgumentAdderRecipe[]
     */
    private function matchArgumentChanges(Node $node): array
    {
        $argumentReplacerRecipes = [];

        foreach ($this->recipes as $argumentReplacerRecipe) {
            if ($this->isNodeToRecipeMatch($node, $argumentReplacerRecipe)) {
                $argumentReplacerRecipes[] = $argumentReplacerRecipe;
            }
        }

        return $argumentReplacerRecipes;
    }

    /**
     * @param mixed[] $argumentNodes
     * @return mixed[]
     */
    private function processArgumentNodes(array $argumentNodes): array
    {
        foreach ($this->activeRecipes as $argumentReplacerRecipe) {
            $position = $argumentReplacerRecipe->getPosition();

            $argumentNodes[$position] = BuilderHelpers::normalizeValue($argumentReplacerRecipe->getDefaultValue());
        }

        return $argumentNodes;
    }
}
