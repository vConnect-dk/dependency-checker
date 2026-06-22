<?php

declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Tests\Unit\Analysis\TopologicalOrder;

use PHPUnit\Framework\TestCase;
use Vconnect\IntegrityChecker\Analysis\Service\TopologicalOrder\Graph;
use Vconnect\IntegrityChecker\Analysis\Service\TopologicalOrder\Kahn;

class KahnTest extends TestCase
{
    public function testGetOrderedPackagesToRemoveProducesCorrectLayers(): void
    {
        $graph = new Graph();
        // B depends on A  → to remove safely: remove B before A
        // C depends on B
        // D has no relations
        $graph->addDependencies('A', []);
        $graph->addDependencies('B', ['A']);
        $graph->addDependencies('C', ['B']);
        $graph->addDependencies('D', []);

        $kahn = new Kahn($graph);
        $kahn->processGraph();

        $layers = $kahn->getOrderedPackagesToRemove();

        // Modules with 0 in-edges (nothing depends on them) come first
        $this->assertArrayHasKey(1, $layers);
        // First removable layer: things with 0 in-edges (C and D)
        $this->assertContains('C', $layers[1]);
        $this->assertContains('D', $layers[1]);

        $this->assertArrayHasKey(2, $layers);
        $this->assertContains('B', $layers[2]);

        $this->assertArrayHasKey(3, $layers);
        $this->assertContains('A', $layers[3]);
    }

    public function testExplainForRemovableModuleReturnsRoadmap(): void
    {
        $graph = new Graph();
        // dependent depends on base
        $graph->addDependencies('base', []);
        $graph->addDependencies('dependent', ['base']);

        $kahn = new Kahn($graph);
        $kahn->processGraph();

        // 'dependent' has 0 in-edges → can be removed immediately
        $result = $kahn->explain('dependent');

        $this->assertNotEmpty($result);
        $this->assertStringContainsString('can be removed', $result[0]['message']);
        $this->assertFalse($result[0]['problem']);

        $messages = array_column($result, 'message');
        $this->assertContains('"dependent": "*",', $messages);
        // Note: current nodeRemoveSequences follows in-edges of the target.
        // For a leaf like 'dependent' it mainly reports itself.
    }

    public function testExplainForNonExistentModule(): void
    {
        $graph = new Graph();
        $kahn = new Kahn($graph);
        $kahn->processGraph();

        $result = $kahn->explain('nonexistent/module');

        $this->assertCount(1, $result);
        $this->assertTrue($result[0]['problem']);
        $this->assertStringContainsString('can not be found', $result[0]['message']);
    }

    public function testExplainRespectsWhitelist(): void
    {
        $graph = new Graph();
        $graph->addDependencies('core', []);
        $graph->addDependencies('module', ['core']);

        $kahn = new Kahn($graph, whitelist: ['core' => 'core']);
        $kahn->processGraph();

        // 'module' can be removed (it is not whitelisted)
        $resultForModule = $kahn->explain('module');
        $this->assertNotEmpty($resultForModule);

        // Explaining the whitelisted core should report it is protected
        $resultForCore = $kahn->explain('core');
        $messages = array_column($resultForCore, 'message');

        $foundWhitelist = false;
        foreach ($messages as $msg) {
            if (stripos($msg, 'whitelisted') !== false) {
                $foundWhitelist = true;
            }
        }
        $this->assertTrue($foundWhitelist, 'Whitelist reason should appear when explaining a whitelisted module');
    }

    public function testModulesWithNoDependenciesAppearInFirstLayer(): void
    {
        $graph = new Graph();
        $graph->addDependencies('standalone', []);

        $kahn = new Kahn($graph);
        $kahn->processGraph();

        $layers = $kahn->getOrderedPackagesToRemove();

        $this->assertArrayHasKey(1, $layers);
        $this->assertContains('standalone', $layers[1]);
    }
}
