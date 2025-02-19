<?php

declare (strict_types=1);
namespace Rector\DowngradePhp81\Rector\FunctionLike;

use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\AssignOp\Coalesce as AssignCoalesce;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\UnionType;
use Rector\Core\Exception\ShouldNotHappenException;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\ValueObject\MethodName;
use Rector\Php72\NodeFactory\AnonymousFunctionFactory;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/new_in_initializers
 *
 * @see \Rector\Tests\DowngradePhp81\Rector\FunctionLike\DowngradeNewInInitializerRector\DowngradeNewInInitializerRectorTest
 */
final class DowngradeNewInInitializerRector extends \Rector\Core\Rector\AbstractRector
{
    /**
     * @var \Rector\Php72\NodeFactory\AnonymousFunctionFactory
     */
    private $anonymousFunctionFactory;
    public function __construct(\Rector\Php72\NodeFactory\AnonymousFunctionFactory $anonymousFunctionFactory)
    {
        $this->anonymousFunctionFactory = $anonymousFunctionFactory;
    }
    public function getRuleDefinition() : \Symplify\RuleDocGenerator\ValueObject\RuleDefinition
    {
        return new \Symplify\RuleDocGenerator\ValueObject\RuleDefinition('Replace New in initializers', [new \Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function __construct(
        private Logger $logger = new NullLogger,
    ) {
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function __construct(
        private ?Logger $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger;
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [\PhpParser\Node\FunctionLike::class];
    }
    /**
     * @param FunctionLike $node
     */
    public function refactor(\PhpParser\Node $node) : ?\PhpParser\Node\FunctionLike
    {
        if ($this->shouldSkip($node)) {
            return null;
        }
        $node = $this->convertArrowFunctionToClosure($node);
        return $this->replaceNewInParams($node);
    }
    private function shouldSkip(\PhpParser\Node\FunctionLike $functionLike) : bool
    {
        foreach ($functionLike->getParams() as $param) {
            if (!$param->default instanceof \PhpParser\Node\Expr\New_) {
                continue;
            }
            if ($param->type instanceof \PhpParser\Node\IntersectionType) {
                continue;
            }
            return \false;
        }
        return \true;
    }
    private function convertArrowFunctionToClosure(\PhpParser\Node\FunctionLike $functionLike) : \PhpParser\Node\FunctionLike
    {
        if (!$functionLike instanceof \PhpParser\Node\Expr\ArrowFunction) {
            return $functionLike;
        }
        $stmts = [new \PhpParser\Node\Stmt\Return_($functionLike->expr)];
        return $this->anonymousFunctionFactory->create($functionLike->params, $stmts, $functionLike->returnType, $functionLike->static);
    }
    private function replaceNewInParams(\PhpParser\Node\FunctionLike $functionLike) : \PhpParser\Node\FunctionLike
    {
        $isConstructor = $functionLike instanceof \PhpParser\Node\Stmt\ClassMethod && $this->isName($functionLike, \Rector\Core\ValueObject\MethodName::CONSTRUCT);
        $stmts = [];
        foreach ($functionLike->getParams() as $param) {
            if (!$param->default instanceof \PhpParser\Node\Expr\New_) {
                continue;
            }
            // check for property promotion
            if ($isConstructor && $param->flags > 0) {
                $propertyFetch = new \PhpParser\Node\Expr\PropertyFetch(new \PhpParser\Node\Expr\Variable('this'), $param->var->name);
                $coalesce = new \PhpParser\Node\Expr\BinaryOp\Coalesce($param->var, $param->default);
                $assign = new \PhpParser\Node\Expr\Assign($propertyFetch, $coalesce);
                if ($param->type !== null) {
                    $param->type = $this->ensureNullableType($param->type);
                }
            } else {
                $assign = new \PhpParser\Node\Expr\AssignOp\Coalesce($param->var, $param->default);
            }
            $stmts[] = new \PhpParser\Node\Stmt\Expression($assign);
            $param->default = $this->nodeFactory->createNull();
        }
        $functionLike->stmts = $functionLike->stmts ?? [];
        $functionLike->stmts = \array_merge($stmts, $functionLike->stmts);
        return $functionLike;
    }
    /**
     * @param \PhpParser\Node\ComplexType|\PhpParser\Node\Identifier|\PhpParser\Node\Name $type
     * @return \PhpParser\Node\NullableType|\PhpParser\Node\UnionType
     */
    private function ensureNullableType($type)
    {
        if ($type instanceof \PhpParser\Node\NullableType) {
            return $type;
        }
        if (!$type instanceof \PhpParser\Node\ComplexType) {
            return new \PhpParser\Node\NullableType($type);
        }
        if ($type instanceof \PhpParser\Node\UnionType) {
            if (!$this->hasNull($type)) {
                $type->types[] = new \PhpParser\Node\Name('null');
            }
            return $type;
        }
        throw new \Rector\Core\Exception\ShouldNotHappenException();
    }
    private function hasNull(\PhpParser\Node\UnionType $unionType) : bool
    {
        foreach ($unionType->types as $type) {
            if ($type->toLowerString() === 'null') {
                return \true;
            }
        }
        return \false;
    }
}
