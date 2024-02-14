<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Analysis\Service\Dependencies\Scanner\GraphQl;


use GraphQL\Error\Error;
use GraphQL\Error\SyntaxError;
use Magento\TestFramework\Inspection\Exception as InspectionException;
use Vconnect\IntegrityChecker\Domain\App\ObjectManager;
use Vconnect\IntegrityChecker\Domain\Exception\LocalizedException;
use Vconnect\IntegrityChecker\Domain\GraphQlSchemaStitching\GraphQlReader;
use Vconnect\IntegrityChecker\Domain\Package;
use Vconnect\IntegrityChecker\Domain\PackagesRegistry;

/**
 * Provide information on the dependency between the modules according to the GraphQL schema.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class GraphQlSchemaDependencyProvider
{
    private array $parsedSchema = [];

    public function __construct(
        private readonly GraphQlReader $reader
    ) {
    }

    /**
     * @param Package $package
     * @return array
     * @throws Error
     * @throws SyntaxError
     * @throws \ReflectionException
     */
    public function getPackageDependencies(Package $package): array
    {
        $schema = $this->getGraphQlSchemaDeclaration();
        $packageName = $package->getPackageName();

        $dependencies = [];

        foreach ($schema as $type) {
            if (isset($type['package']) && $type['package'] === $packageName && isset($type['implements'])) {
                $interfaces = array_keys($type['implements']);
                foreach ($interfaces as $interface) {
                    $dependOnPackage = $schema[$interface]['package'];
                    if ($dependOnPackage !== $packageName) {
                        $dependencies[] = $dependOnPackage;
                    }
                }

            }
        }
        return array_unique($dependencies);
    }

    /**
     * Get parsed GraphQl schema
     *
     * @return array
     * @throws Error
     * @throws SyntaxError
     * @throws \ReflectionException
     */
    private function getGraphQlSchemaDeclaration(): array
    {
        if (!$this->parsedSchema) {
            $this->parsedSchema = $this->reader->read($this->collectGraphQlSchemaFiles());
        }

        return $this->parsedSchema;
    }

    private function collectGraphQlSchemaFiles(): array
    {
        $files = [];
        foreach (PackagesRegistry::getInstance()->getAllPackages() as $package) {
            $graphQlSchema = $package->getConfig()->getGraphQlSchema();
            if ($graphQlSchema !== null) {
                $files[$package->getPackageName()] = $graphQlSchema;
            }
        }

        return $files;
    }
}
