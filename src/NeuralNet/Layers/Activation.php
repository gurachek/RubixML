<?php

namespace Rubix\ML\NeuralNet\Layers;

use Rubix\Tensor\Matrix;
use Rubix\ML\NeuralNet\Parameter;
use Rubix\ML\NeuralNet\Optimizers\Optimizer;
use Rubix\ML\NeuralNet\ActivationFunctions\ActivationFunction;
use InvalidArgumentException;
use RuntimeException;

/**
 * Activation
 *
 * Activation layers apply a nonlinear activation function to their inputs.
 *
 * @category    Machine Learning
 * @package     Rubix/ML
 * @author      Andrew DalPino
 */
class Activation implements Hidden, Nonparametric
{
    /**
     * The function computes the activation of the layer.
     *
     * @var \Rubix\ML\NeuralNet\ActivationFunctions\ActivationFunction
     */
    protected $activationFunction;

    /**
     * The width of the layer.
     *
     * @var int|null
     */
    protected $width;

    /**
     * The memoized input matrix.
     *
     * @var \Rubix\Tensor\Matrix|null
     */
    protected $input;

    /**
     * The memoized activation matrix.
     *
     * @var \Rubix\Tensor\Matrix|null
     */
    protected $computed;

    /**
     * @param  \Rubix\ML\NeuralNet\ActivationFunctions\ActivationFunction  $activationFunction
     * @return void
     */
    public function __construct(ActivationFunction $activationFunction)
    {
        $this->activationFunction = $activationFunction;
    }

    /**
     * @return int|null
     */
    public function width() : ?int
    {
        return $this->width;
    }

    /**
     * Initialize the layer.
     *
     * @param  int  $fanIn
     * @return int
     */
    public function init(int $fanIn) : int
    {
        $fanOut = $fanIn;

        $this->width = $fanOut;

        return $fanOut;
    }

    /**
     * Compute the input sum and activation of each neuron in the layer and
     * return an activation matrix.
     *
     * @param  \Rubix\Tensor\Matrix  $input
     * @return \Rubix\Tensor\Matrix
     */
    public function forward(Matrix $input) : Matrix
    {
        $this->input = $input;

        $this->computed = $this->activationFunction->compute($input);

        return $this->computed;
    }

    /**
     * Compute the inferential activations of each neuron in the layer.
     *
     * @param  \Rubix\Tensor\Matrix  $input
     * @return \Rubix\Tensor\Matrix
     */
    public function infer(Matrix $input) : Matrix
    {
        return $this->activationFunction->compute($input);
    }

    /**
     * Calculate the gradients and update the parameters of the layer.
     *
     * @param  callable  $prevGradient
     * @param  \Rubix\ML\NeuralNet\Optimizers\Optimizer  $optimizer
     * @throws \RuntimeException
     * @return callable
     */
    public function back(callable $prevGradient, Optimizer $optimizer) : callable
    {
        if (is_null($this->input) or is_null($this->computed)) {
            throw new RuntimeException('Must perform forward pass before'
                . ' backpropagating.');
        }

        $input = $this->input;
        $computed = $this->computed;

        unset($this->input, $this->computed);

        return function () use ($input, $computed, $prevGradient) {
            return $this->activationFunction
                ->differentiate($input, $computed)
                ->multiply($prevGradient());
        };
    }
}
