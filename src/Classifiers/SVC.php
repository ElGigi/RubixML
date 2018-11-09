<?php

namespace Rubix\ML\Classifiers;

use Rubix\ML\Learner;
use Rubix\ML\Persistable;
use Rubix\ML\Kernels\SVM\RBF;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Dataset;
use Rubix\ML\Datasets\DataFrame;
use Rubix\ML\Kernels\SVM\Kernel;
use InvalidArgumentException;
use RuntimeException;
use svmmodel;
use svm;

/**
 * SVC
 * 
 * The Support Vector Machine Classifier is a maximum margin classifier that
 * can efficiently perform non-linear classification by implicitly mapping
 * feature vectors into high dimensional feature space.
 * 
 * References:
 * [1] C. Chang et al. (2011). LIBSVM: A library for support vector machines.
 *
 * @category    Machine Learning
 * @package     Rubix/ML
 * @author      Andrew DalPino
 */
class SVC implements Learner, Persistable
{
    /**
     * The support vector machine instance.
     * 
     * @var \svm
     */
    protected $svm;

    /**
     * The trained model instance.
     * 
     * @var \svmmodel|null
     */
    protected $model;

    /**
     * The mappings from integer to class label.
     * 
     * @var string[]
     */
    protected $classes =[
        //
    ];

    /**
     * @param  float  $c
     * @param  \Rubix\ML\Kernels\SVM\Kernel  $kernel
     * @param  bool  $shrinking
     * @param  float  $tolerance
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @return void
     */
    public function __construct(float $c = 1.0, ?Kernel $kernel = null, bool $shrinking = true,
                                float $tolerance = 1e-3)
    {
        if (!extension_loaded('svm')) {
            throw new RuntimeException('SVM extension is not loaded, check'
                . ' php.ini file.');
        }

        if ($c < 0.) {
            throw new InvalidArgumentException('C cannot be less than 0,'
                . " $c given.");
        }

        if (is_null($kernel)) {
            $kernel = new RBF();
        }

        if ($tolerance < 0.) {
            throw new InvalidArgumentException('Tolerance cannot be less than 0,'
                . " $tolerance given.");
        }

        $options = [
            svm::OPT_TYPE => 0,
            svm::OPT_C => $c,
            svm::OPT_SHRINKING => $shrinking,
            svm::OPT_EPS => $tolerance,
        ];

        $options = array_replace($options, $kernel->options());

        $this->svm = new svm();

        $this->svm->setOptions($options);
    }

    /**
     * Return the integer encoded type of estimator this is.
     *
     * @return int
     */
    public function type() : int
    {
        return self::CLASSIFIER;
    }

    /**
     * Train the learner with a dataset.
     *
     * @param  \Rubix\ML\Datasets\Dataset  $dataset
     * @throws \InvalidArgumentException
     * @return void
     */
    public function train(Dataset $dataset) : void
    {
        if (!$dataset instanceof Labeled) {
            throw new InvalidArgumentException('This estimator requires a'
                . ' labeled training set.');
        }

        if (in_array(DataFrame::CATEGORICAL, $dataset->types())) {
            throw new InvalidArgumentException('This estimator only works with'
            . ' continuous features.');
        }

        $this->classes = $dataset->possibleOutcomes();

        $mapping = array_flip($this->classes);

        $labels = $dataset->labels();

        $data = [];

        foreach ($dataset->samples() as $i => $sample) {
            $data[] = array_merge([$mapping[$labels[$i]]], $sample);
        }

        $this->model = $this->svm->train($data);
    }

    /**
     * Make predictions from a dataset.
     *
     * @param  \Rubix\ML\Datasets\Dataset  $dataset
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return array
     */
    public function predict(Dataset $dataset) : array
    {
        if (in_array(DataFrame::CATEGORICAL, $dataset->types())) {
            throw new InvalidArgumentException('This estimator only works with'
            . ' continuous features.');
        }

        if (is_null($this->model)) {
            throw new RuntimeException('Estimator has not been trained.');
        }

        $predictions = [];

        foreach ($dataset as $sample) {
            $predictions[] = $this->classes[$this->model->predict($sample)];
        }

        return $predictions;
    }
}