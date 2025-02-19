<?php

declare (strict_types=1);
namespace Rector\Core\DependencyInjection\Collector;

use Rector\Core\Contract\Rector\ConfigurableRectorInterface;
use RectorPrefix20211201\Symfony\Component\DependencyInjection\Definition;
use RectorPrefix20211201\Symplify\PackageBuilder\Yaml\ParametersMerger;
final class ConfigureCallValuesCollector
{
    /**
     * @var mixed[]
     */
    private $configureCallValuesByRectorClass = [];
    /**
     * @var \Symplify\PackageBuilder\Yaml\ParametersMerger
     */
    private $parametersMerger;
    public function __construct()
    {
        $this->parametersMerger = new \RectorPrefix20211201\Symplify\PackageBuilder\Yaml\ParametersMerger();
    }
    /**
     * @return mixed[]
     */
    public function getConfigureCallValues(string $rectorClass) : array
    {
        return $this->configureCallValuesByRectorClass[$rectorClass] ?? [];
    }
    /**
     * @param class-string<ConfigurableRectorInterface> $className
     */
    public function collectFromServiceAndClassName(string $className, \RectorPrefix20211201\Symfony\Component\DependencyInjection\Definition $definition) : void
    {
        foreach ($definition->getMethodCalls() as $methodCall) {
            if ($methodCall[0] !== 'configure') {
                continue;
            }
            $this->addConfigureCallValues($className, $methodCall[1]);
        }
    }
    /**
     * @param class-string<ConfigurableRectorInterface> $rectorClass
     * @param mixed[] $configureValues
     */
    private function addConfigureCallValues(string $rectorClass, array $configureValues) : void
    {
        foreach ($configureValues as $configureValue) {
            // is nested or unnested value?
            if (\is_array($configureValue) && \count($configureValue) === 1) {
                \reset($configureValue);
                $firstKey = \key($configureValue);
                if (\is_array($configureValue[$firstKey])) {
                    $configureValue = $configureValue[$firstKey];
                }
            }
            if (!isset($this->configureCallValuesByRectorClass[$rectorClass])) {
                $this->configureCallValuesByRectorClass[$rectorClass] = $configureValue;
            } else {
                $mergedParameters = $this->parametersMerger->merge($this->configureCallValuesByRectorClass[$rectorClass], $configureValue);
                $this->configureCallValuesByRectorClass[$rectorClass] = $mergedParameters;
            }
        }
    }
}
