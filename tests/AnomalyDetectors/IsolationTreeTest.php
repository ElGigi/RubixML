<?php

namespace Rubix\Tests\AnomalyDetectors;

use Rubix\ML\Estimator;
use Rubix\ML\Persistable;
use Rubix\ML\Probabilistic;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\AnomalyDetectors\IsolationTree;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class IsolationTreeTest extends TestCase
{
    protected $estimator;

    protected $training;

    protected $testing;

    public function setUp()
    {
        $this->training = Labeled::load(dirname(__DIR__) . '/iris.dataset');

        $this->testing = new Labeled([
            [6.9, 3.2, 5.7, 2.3], [6.4, 3.1, 5.5, 1.8], [5.5, 2.4, 3.8, 1.1],
            [6.8, 3.2, 5.9, 2.3], [5.7, 3.8, 1.7, 0.3], [5.4, 3.9, 1.7, 0.4],
        ], [1, 1, 0, 1, 0, 0]);

        $this->estimator = new IsolationTree(10, 1, 0.50);
    }

    public function test_build_detector()
    {
        $this->assertInstanceOf(IsolationTree::class, $this->estimator);
        $this->assertInstanceOf(Probabilistic::class, $this->estimator);
        $this->assertInstanceOf(Persistable::class, $this->estimator);
        $this->assertInstanceOf(Estimator::class, $this->estimator);
    }

    public function test_estimator_type()
    {
        $this->assertEquals(Estimator::DETECTOR, $this->estimator->type());
    }

    public function test_make_prediction()
    {
        $this->estimator->train($this->training);

        $predictions = $this->estimator->predict($this->testing);

        $this->assertContains($predictions[0], [1, 0]);
        $this->assertContains($predictions[1], [1, 0]);
        $this->assertContains($predictions[2], [1, 0]);
        $this->assertContains($predictions[3], [1, 0]);
        $this->assertContains($predictions[4], [1, 0]);
        $this->assertContains($predictions[5], [1, 0]);
    }

    public function test_predict_untrained()
    {
        $this->expectException(RuntimeException::class);

        $this->estimator->predict($this->testing);
    }
}
