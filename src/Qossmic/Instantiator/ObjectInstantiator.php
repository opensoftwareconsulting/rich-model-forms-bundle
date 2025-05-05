<?php

/*
 * This file is part of the RichModelFormsBundle package.
 *
 * (c) Christian Flothmann <christian.flothmann@qossmic.com>
 * (c) Christopher Hertel <mail@christopher-hertel.de>
 * (c) QOSSMIC GmbH <info@qossmic.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace Qossmic\RichModelForms\Instantiator;

use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @author Christian Flothmann <christian.flothmann@qossmic.com>
 */
abstract class ObjectInstantiator
{
    /** @var class-string|(\Closure(): object)|((callable(): object)&array{0: class-string|object, 1: string}) */
    private mixed $factory;

    /**
     * @param class-string|(\Closure(): object)|((callable(): object)&array{0: class-string|object, 1: string}) $factory
     */
    public function __construct(mixed $factory)
    {
        $this->factory = $factory;
    }

    public function instantiateObject(): ?object
    {
        if (\is_string($this->factory) && class_exists($this->factory)) {
            $factoryMethod = (new \ReflectionClass($this->factory))->getConstructor();

            if (null === $factoryMethod) {
                throw new TransformationFailedException(\sprintf('The class "%s" used as a factory does not have a constructor.', $this->factory));
            }

            $factoryMethodAsString = $this->factory.'::__construct';
            if (!$factoryMethod->isPublic()) {
                throw new TransformationFailedException(\sprintf('The factory method %s() is not public.', $factoryMethodAsString));
            }
        } elseif (\is_array($this->factory)) {
            /** @var class-string $class */
            $class = \is_object($this->factory[0]) ? \get_class($this->factory[0]) : $this->factory[0];
            $factoryMethod = (new \ReflectionMethod($class, $this->factory[1]));
            $factoryMethodAsString = $class.'::'.$this->factory[1];
            if (!$factoryMethod->isPublic()) {
                throw new TransformationFailedException(\sprintf('The factory method %s() is not public.', $factoryMethodAsString));
            }
        } elseif ($this->factory instanceof \Closure) {
            $factoryMethod = new \ReflectionFunction($this->factory);
        } else {
            /* @phpstan-ignore-next-line */
            return $this->getData();
        }

        $arguments = [];

        if ($this->isCompoundForm()) {
            foreach ($factoryMethod->getParameters() as $parameter) {
                $arguments[] = $this->getArgumentData($parameter->getName());
            }
        } else {
            $arguments[] = $this->getData();
        }

        if (\is_string($this->factory)) {
            return new $this->factory(...$arguments);
        }

        /* @phpstan-ignore-next-line */
        return ($this->factory)(...$arguments);
    }

    abstract protected function isCompoundForm(): bool;

    abstract protected function getData(): mixed;

    abstract protected function getArgumentData(string $argument): mixed;
}
