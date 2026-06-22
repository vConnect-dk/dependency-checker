<?php
declare(strict_types=1);

namespace Vconnect\IntegrityChecker\Tests\Unit\Analysis\TopologicalOrder;

use PHPUnit\Framework\TestCase;
use Vconnect\IntegrityChecker\Analysis\Service\TopologicalOrder\Graph;

class GraphTest extends TestCase
{
    public function testAddDependenciesCreatesNodesAndEdges(): void
    {
        $graph = new Graph();
        $graph->addDependencies('module-a', ['module-b', 'module-c']);

        $nodes = $graph->getAllNodes();

        $this->assertArrayHasKey('module-a', $nodes);
        $this->assertArrayHasKey('module-b', $nodes);
        $this->assertArrayHasKey('module-c', $nodes);

        $this->assertContains('module-b', $nodes['module-a']->getOutEdges());
        $this->assertContains('module-c', $nodes['module-a']->getOutEdges());

        // module-b should have an incoming edge from a
        $this->assertContains('module-a', $nodes['module-b']->getInEdges());
    }

    public function testGetNodeReturnsCorrectNode(): void
    {
        $graph = new Graph();
        $graph->addDependencies('standalone', []);

        $this->assertNotNull($graph->getNode('standalone'));
        $this->assertNull($graph->getNode('does-not-exist'));
    }
}
