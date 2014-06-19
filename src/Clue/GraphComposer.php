<?php

namespace Clue;

use Fhaculty\Graph\GraphViz;
use Fhaculty\Graph\Graph;

class GraphComposer
{
    private $layoutVertex = array(
        'fillcolor' => '#eeeeee',
        'style' => 'filled, rounded',
        'shape' => 'box',
        'fontcolor' => '#314B5F'
    );
    
    private $layoutVertexRoot = array(
        'style' => 'filled, rounded, bold'
    );
    
    private $layoutEdge = array(
        'fontcolor' => '#767676',
        'fontsize' => 10,
        'color' => '#1A2833'
    );
    
    private $layoutEdgeDev = array(
        'style' => 'dashed'
    );
    
    private $dependencyGraph;
    
    private $format = 'svg';
    
    /**
     * 
     * @param string $dir
     */
    public function __construct($dir)
    {
        $analyzer = new \JMS\Composer\DependencyAnalyzer();
        $this->dependencyGraph = $analyzer->analyze($dir);
    }
    
    /**
     * 
     * @param boolean $showDevPackage
     * @return \Fhaculty\Graph\Graph
     */
    public function createGraph($showDevPackage = true)
    {
        $graph = new Graph();
        
        foreach ($this->dependencyGraph->getPackages() as $package) {
            $name = $package->getName();
            $start = $graph->createVertex($name, true);

            $label = $name;
            if ($package->getVersion() !== null) {
                $label .= ': ' . $package->getVersion();
            }

            $start->setLayout(array('label' => $label) + $this->layoutVertex);

            $hasOnlyDevDependencies = true;
            foreach ($package->getOutEdges() as $requires) {
                if (!$showDevPackage && $requires->isDevDependency()) {
                    continue;
                }

                $hasOnlyDevDependencies = false;

                $targetName = $requires->getDestPackage()->getName();
                $target = $graph->createVertex($targetName, true);
                
                $label = $requires->getVersionConstraint();
                
                $edge = $start->createEdgeTo($target)->setLayout(array('label' => $label) + $this->layoutEdge);
                
                if ($requires->isDevDependency()) {
                    $edge->setLayout($this->layoutEdgeDev);
                }
            }

            if (!$showDevPackage && $hasOnlyDevDependencies) {
                $start->destroy();
            }
        }

        $graph->getVertex($this->dependencyGraph->getRootPackage()->getName())->setLayout($this->layoutVertexRoot);
        
        return $graph;
    }

    /**
     * @param boolean $showDevPackage
     */
    public function displayGraph($showDevPackage = true)
    {
        $graph = $this->createGraph($showDevPackage);
        
        $graphviz = new GraphViz($graph);
        $graphviz->setFormat($this->format);
        $graphviz->display();
    }

    /**
     * @param boolean $showDevPackage
     */
    public function getImagePath($showDevPackage = true)
    {
        $graph = $this->createGraph($showDevPackage);
        
        $graphviz = new GraphViz($graph);
        $graphviz->setFormat($this->format);
        
        return $graphviz->createImageFile();
    }
    
    public function setFormat($format)
    {
        $this->format = $format;
        return $this;
    }
}
