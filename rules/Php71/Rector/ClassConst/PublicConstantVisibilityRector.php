<?php

declare (strict_types=1);
namespace Rector\Php71\Rector\ClassConst;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassConst;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\ValueObject\PhpVersionFeature;
use Rector\Privatization\NodeManipulator\VisibilityManipulator;
use Rector\VersionBonding\Contract\MinPhpVersionInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
/**
 * @changelog https://wiki.php.net/rfc/class_const_visibility
 *
 * @see \Rector\Tests\Php71\Rector\ClassConst\PublicConstantVisibilityRector\PublicConstantVisibilityRectorTest
 */
final class PublicConstantVisibilityRector extends \Rector\Core\Rector\AbstractRector implements \Rector\VersionBonding\Contract\MinPhpVersionInterface
{
    /**
     * @var \Rector\Privatization\NodeManipulator\VisibilityManipulator
     */
    private $visibilityManipulator;
    public function __construct(\Rector\Privatization\NodeManipulator\VisibilityManipulator $visibilityManipulator)
    {
        $this->visibilityManipulator = $visibilityManipulator;
    }
    public function getRuleDefinition() : \Symplify\RuleDocGenerator\ValueObject\RuleDefinition
    {
        return new \Symplify\RuleDocGenerator\ValueObject\RuleDefinition('Add explicit public constant visibility.', [new \Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    const HEY = 'you';
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public const HEY = 'you';
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes() : array
    {
        return [\PhpParser\Node\Stmt\ClassConst::class];
    }
    /**
     * @param ClassConst $node
     */
    public function refactor(\PhpParser\Node $node) : ?\PhpParser\Node
    {
        // already non-public
        if (!$node->isPublic()) {
            return null;
        }
        // explicitly public
        if ($node->flags !== 0) {
            return null;
        }
        $this->visibilityManipulator->makePublic($node);
        return $node;
    }
    public function provideMinPhpVersion() : int
    {
        return \Rector\Core\ValueObject\PhpVersionFeature::CONSTANT_VISIBILITY;
    }
}
