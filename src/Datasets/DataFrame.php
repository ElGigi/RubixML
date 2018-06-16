<?php

namespace Rubix\ML\Datasets;

use Rubix\ML\Transformers\Transformer;
use InvalidArgumentException;
use IteratorAggregate;
use RuntimeException;
use ArrayIterator;
use ArrayAccess;
use Countable;

class DataFrame implements ArrayAccess, IteratorAggregate, Countable
{
    const CATEGORICAL = 1;
    const CONTINUOUS = 2;

    /**
     * The feature vectors of the dataset. i.e the data table.
     *
     * @var array
     */
    protected $samples = [
        //
    ];

    /**
     * @param  array  $samples
     * @throws \InvalidArgumentException
     * @return void
     */
    public function __construct(array $samples)
    {
        foreach ($samples as &$sample) {
            $sample = array_values((array) $sample);

            if (count($sample) !== count(current($samples))) {
                throw new InvalidArgumentException('The number of feature'
                    . ' columns must be equal for all samples.');
            }

            foreach ($sample as &$feature) {
                if (!is_string($feature) and !is_numeric($feature)) {
                    throw new InvalidArgumentException('Feature must be a'
                        . ' string, or numeric type, '
                        . gettype($feature) . ' found.');
                }
            }
        }

        $this->samples = array_values($samples);
    }

    /**
     * @return array
     */
    public function samples() : array
    {
        return $this->samples;
    }

    /**
     * Return the sample at the given row index.
     *
     * @param  mixed  $index
     * @return array
     */
    public function row($index) : array
    {
        return $this->offsetGet($index);
    }

    /**
     * Return the number of rows in the datasets.
     *
     * @return int
     */
    public function numRows() : int
    {
        return count($this->samples);
    }

    /**
     * Return the feature column at the given index.
     *
     * @param  mixed  $index
     * @return array
     */
    public function column($index) : array
    {
        return array_column($this->samples, $index);
    }

    /**
     * Return an array of autodetected column types.
     *
     * @return array
     */
    public function columnTypes() : array
    {
        return array_map(function ($feature) {
            return is_string($feature) ? self::CATEGORICAL : self::CONTINUOUS;
        }, $this->samples[0] ?? []);
    }

    /**
     * Return the number of feature columns in the datasets.
     *
     * @return int
     */
    public function numColumns() : int
    {
        return count($this->samples[0] ?? []);
    }

    /**
     * Have a transformer transform the dataset.
     *
     * @param  \Rubix\ML\Transformers\Transformer  $transformer
     * @return void
     */
    public function transform(Transformer $transformer) : void
    {
        $transformer->transform($this->samples);
    }

    /**
     * Returns an array of feature columns.
     *
     * @return array
     */
    public function rotate() : array
    {
        return array_map(null, ...$this->samples);
    }

    /**
     * @return int
     */
    public function count() : int
    {
        return $this->numRows();
    }

    /**
     * Is the dataset empty?
     *
     * @return bool
     */
    public function isEmpty() : bool
    {
        return $this->numRows() === 0;
    }

    /**
     * @param  mixed  $index
     * @param  array  $values
     * @throws \RuntimeException
     * @return void
     */
    public function offsetSet($index, $values) : void
    {
        throw new RuntimeException('Datasets cannot be mutated directly.');
    }

    /**
     * Does a given column exist in the dataset.
     *
     * @param  mixed  $index
     * @return bool
     */
    public function offsetExists($index) : bool
    {
        return isset($this->samples[$index]);
    }

    /**
     * @param  mixed  $index
     * @throws \RuntimeException
     * @return void
     */
    public function offsetUnset($index) : void
    {
        throw new RuntimeException('Datasets cannot be mutated directly.');
    }

    /**
     * Return a column from the dataframe given by index.
     *
     * @param  mixed  $index
     * @throws \InvalidArgumentException
     * @return array
     */
    public function offsetGet($index) : array
    {
        if (!isset($this->samples[$index])) {
            throw new InvalidArgumentException('Sample not found at the given'
                . ' index ' . (string) $index . '.');
        }

        return $this->samples[$index];
    }

    /**
     * Get an iterator for the samples in the dataset.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->samples);
    }
}