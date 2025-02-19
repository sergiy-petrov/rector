<?php

declare (strict_types=1);
namespace Rector\ReadWrite\ReadNodeAnalyzer;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Stmt\Class_;
use Rector\Core\PhpParser\Node\BetterNodeFinder;
use Rector\Core\PhpParser\NodeFinder\PropertyFetchFinder;
use Rector\NodeNameResolver\NodeNameResolver;
use Rector\ReadWrite\Contract\ReadNodeAnalyzerInterface;
/**
 * @implements ReadNodeAnalyzerInterface<PropertyFetch|StaticPropertyFetch>
 */
final class LocalPropertyFetchReadNodeAnalyzer implements \Rector\ReadWrite\Contract\ReadNodeAnalyzerInterface
{
    /**
     * @var \Rector\ReadWrite\ReadNodeAnalyzer\JustReadExprAnalyzer
     */
    private $justReadExprAnalyzer;
    /**
     * @var \Rector\Core\PhpParser\NodeFinder\PropertyFetchFinder
     */
    private $propertyFetchFinder;
    /**
     * @var \Rector\NodeNameResolver\NodeNameResolver
     */
    private $nodeNameResolver;
    /**
     * @var \Rector\Core\PhpParser\Node\BetterNodeFinder
     */
    private $betterNodeFinder;
    public function __construct(\Rector\ReadWrite\ReadNodeAnalyzer\JustReadExprAnalyzer $justReadExprAnalyzer, \Rector\Core\PhpParser\NodeFinder\PropertyFetchFinder $propertyFetchFinder, \Rector\NodeNameResolver\NodeNameResolver $nodeNameResolver, \Rector\Core\PhpParser\Node\BetterNodeFinder $betterNodeFinder)
    {
        $this->justReadExprAnalyzer = $justReadExprAnalyzer;
        $this->propertyFetchFinder = $propertyFetchFinder;
        $this->nodeNameResolver = $nodeNameResolver;
        $this->betterNodeFinder = $betterNodeFinder;
    }
    /**
     * @param \PhpParser\Node\Expr $expr
     */
    public function supports($expr) : bool
    {
        return $expr instanceof \PhpParser\Node\Expr\PropertyFetch || $expr instanceof \PhpParser\Node\Expr\StaticPropertyFetch;
    }
    /**
     * @param \PhpParser\Node\Expr $expr
     */
    public function isRead($expr) : bool
    {
        $class = $this->betterNodeFinder->findParentType($expr, \PhpParser\Node\Stmt\Class_::class);
        if (!$class instanceof \PhpParser\Node\Stmt\Class_) {
            // assume worse to keep node protected
            return \true;
        }
        $propertyName = $this->nodeNameResolver->getName($expr->name);
        if ($propertyName === null) {
            // assume worse to keep node protected
            return \true;
        }
        $propertyFetches = $this->propertyFetchFinder->findLocalPropertyFetchesByName($class, $propertyName);
        foreach ($propertyFetches as $propertyFetch) {
            if ($this->justReadExprAnalyzer->isReadContext($propertyFetch)) {
                return \true;
            }
        }
        return \false;
    }
}
