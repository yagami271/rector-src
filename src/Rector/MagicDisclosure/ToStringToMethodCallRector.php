<?php declare(strict_types=1);

namespace Rector\Rector\MagicDisclosure;

use PhpParser\Node;
use PhpParser\Node\Expr\Cast\String_;
use PhpParser\Node\Expr\MethodCall;
use Rector\Builder\IdentifierRenamer;
use Rector\Node\MethodCallNodeFactory;
use Rector\NodeAnalyzer\MethodCallAnalyzer;
use Rector\NodeTypeResolver\NodeTypeResolver;
use Rector\Rector\AbstractRector;
use Rector\RectorDefinition\ConfiguredCodeSample;
use Rector\RectorDefinition\RectorDefinition;

final class ToStringToMethodCallRector extends AbstractRector
{
    /**
     * @var string[][]
     */
    private $typeToMethodCalls = [];

    /**
     * @var string
     */
    private $activeTransformation;

    /**
     * @var MethodCallAnalyzer
     */
    private $methodCallAnalyzer;

    /**
     * @var IdentifierRenamer
     */
    private $identifierRenamer;

    /**
     * @var MethodCallNodeFactory
     */
    private $methodCallNodeFactory;

    /**
     * @var NodeTypeResolver
     */
    private $nodeTypeResolver;

    /**
     * Type to method call()
     *
     * @param string[][] $typeToMethodCalls
     */
    public function __construct(
        array $typeToMethodCalls,
        MethodCallAnalyzer $methodCallAnalyzer,
        IdentifierRenamer $identifierRenamer,
        MethodCallNodeFactory $methodCallNodeFactory,
        NodeTypeResolver $nodeTypeResolver
    ) {
        $this->typeToMethodCalls = $typeToMethodCalls;
        $this->methodCallAnalyzer = $methodCallAnalyzer;
        $this->identifierRenamer = $identifierRenamer;
        $this->methodCallNodeFactory = $methodCallNodeFactory;
        $this->nodeTypeResolver = $nodeTypeResolver;
    }

    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Turns defined code uses of "__toString()" method  to specific method calls.', [
            new ConfiguredCodeSample(
<<<'CODE_SAMPLE'
$someValue = new SomeObject;
$result = (string) $someValue;
$result = $someValue->__toString();
CODE_SAMPLE
                ,
<<<'CODE_SAMPLE'
$someValue = new SomeObject;
$result = $someValue->someMethod();
$result = $someValue->someMethod();
CODE_SAMPLE
                ,
                [
                    '$typeToMethodCalls' => [
                        'SomeObject' => [
                            'toString' => 'getPath',
                        ],
                    ],
                ]
            ),
        ]);
    }

    public function getNodeType(): string
    {
        return [String_::class, MethodCall::class];
    }

    /**
     * @param String_|MethodCall $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof String_) {
            if ($this->processStringCandidate($node) === false) {
                return null;
            }
        }
        if ($node instanceof MethodCall) {
            if ($this->processMethodCallCandidate($node) === false) {
                return null;
            }
        }
        return null;
        if ($node instanceof String_) {
            return $this->methodCallNodeFactory->createWithVariableAndMethodName(
                $node->expr,
                $this->activeTransformation
            );
        }

        $this->identifierRenamer->renameNode($node, $this->activeTransformation);

        return $node;
    }

    private function processStringCandidate(String_ $stringNode): bool
    {
        $nodeTypes = $this->nodeTypeResolver->resolve($stringNode->expr);

        foreach ($this->typeToMethodCalls as $type => $transformation) {
            if (in_array($type, $nodeTypes, true)) {
                $this->activeTransformation = $transformation['toString'];

                return true;
            }
        }

        return false;
    }

    private function processMethodCallCandidate(MethodCall $methodCallNode): bool
    {
        foreach ($this->typeToMethodCalls as $type => $transformation) {
            if ($this->methodCallAnalyzer->isTypeAndMethod($methodCallNode, $type, '__toString')) {
                $this->activeTransformation = $transformation['toString'];

                return true;
            }
        }

        return false;
    }
}
