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

namespace Qossmic\RichModelForms\DataMapper;

use Qossmic\RichModelForms\ExceptionHandling\FormExceptionHandler;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @author Christian Flothmann <christian.flothmann@qossmic.com>
 */
final class DataMapper implements DataMapperInterface
{
    private DataMapperInterface $dataMapper;
    private PropertyAccessorInterface $propertyAccessor;
    private FormExceptionHandler $formExceptionHandler;

    public function __construct(DataMapperInterface $dataMapper, PropertyAccessorInterface $propertyAccessor, FormExceptionHandler $formExceptionHandler)
    {
        $this->dataMapper = $dataMapper;
        $this->propertyAccessor = $propertyAccessor;
        $this->formExceptionHandler = $formExceptionHandler;
    }

    public function mapDataToForms(mixed $data, \Traversable $forms): void
    {
        $isDataEmpty = null === $data || [] === $data;

        if (!$isDataEmpty && !\is_array($data) && !\is_object($data)) {
            throw new UnexpectedTypeException($data, 'object, array or null');
        }

        $formsToBeMapped = [];

        foreach ($forms as $form) {
            $readPropertyPath = null;
            $propertyMapper = null;

            if (!$this->isImmutable($form)) {
                $readPropertyPath = $form->getConfig()->getOption('read_property_path');
                $propertyMapper = $form->getConfig()->getOption('property_mapper');
            }

            if (!$isDataEmpty && $readPropertyPath instanceof \Closure && $form->getConfig()->getMapped()) {
                $form->setData($readPropertyPath($data));
            } elseif (!$isDataEmpty && null !== $readPropertyPath && $form->getConfig()->getMapped()) {
                /* @phpstan-ignore-next-line */
                $form->setData($this->propertyAccessor->getValue($data, $readPropertyPath));
            } elseif (!$isDataEmpty && null !== $propertyMapper) {
                /* @phpstan-ignore-next-line */
                $form->setData($propertyMapper->readPropertyValue($data));
            } elseif (null !== $readPropertyPath) {
                $form->setData($form->getConfig()->getData());
            } else {
                $formsToBeMapped[] = $form;
            }
        }

        $this->dataMapper->mapDataToForms($data, new \ArrayIterator($formsToBeMapped));
    }

    public function mapFormsToData(\Traversable $forms, mixed &$data): void
    {
        if (null === $data) {
            return;
        }

        if (!\is_array($data) && !\is_object($data)) {
            throw new UnexpectedTypeException($data, 'object, array or null');
        }

        $writePropertyPaths = [];

        foreach ($forms as $form) {
            $forwardToWrappedDataMapper = false;
            $config = $form->getConfig();
            $readPropertyPath = null;
            $propertyMapper = null;

            if (!$this->isImmutable($form)) {
                $readPropertyPath = $form->getConfig()->getOption('read_property_path');
                $propertyMapper = $form->getConfig()->getOption('property_mapper');
            }

            $writePropertyPath = $config->getOption('write_property_path');

            if ($readPropertyPath instanceof \Closure) {
                $previousValue = $readPropertyPath($data);
            } elseif (null !== $readPropertyPath) {
                /* @phpstan-ignore-next-line */
                $previousValue = $this->propertyAccessor->getValue($data, $readPropertyPath);
            } elseif (null !== $propertyMapper) {
                /* @phpstan-ignore-next-line */
                $previousValue = $propertyMapper->readPropertyValue($data);
            } else {
                $previousValue = null;
            }

            if (null === $writePropertyPath && null === $propertyMapper) {
                $forwardToWrappedDataMapper = true;
            } elseif (!$config->getMapped() || !$form->isSubmitted() || !$form->isSynchronized() || $form->isDisabled()) {
                // write-back is disabled if the form is not synchronized (transformation failed),
                // if the form was not submitted and if the form is disabled (modification not allowed)
                $forwardToWrappedDataMapper = true;
            } elseif (\is_object($data) && $config->getByReference() && $form->getData() === $previousValue && !$writePropertyPath instanceof \Closure) {
                $forwardToWrappedDataMapper = true;
            }

            try {
                if ($forwardToWrappedDataMapper) {
                    $this->dataMapper->mapFormsToData(new \ArrayIterator([$form]), $data);
                } elseif ($writePropertyPath instanceof \Closure) {
                    $writePropertyPath($data, $form->getData());
                } elseif ($propertyMapper instanceof PropertyMapperInterface) {
                    $propertyMapper->writePropertyValue($data, $form->getData());
                } elseif (\is_string($writePropertyPath)) {
                    $writePropertyPaths[$writePropertyPath][] = $form;
                }
            } catch (\Throwable $e) {
                $this->formExceptionHandler->handleException($form, $data, $e);
            }
        }

        /** @var string $writePropertyPath */
        foreach ($writePropertyPaths as $writePropertyPath => $forms) {
            try {
                if (1 === \count($forms)) {
                    $this->propertyAccessor->setValue($data, $writePropertyPath, reset($forms)->getData());
                } elseif (!\is_object($data)) {
                    throw new LogicException(\sprintf('Mapping multiple forms to a single method requires the form data to be an object but is "%s".', \gettype($data)));
                } else {
                    $formData = [];

                    foreach ($forms as $form) {
                        $formData[$form->getName()] = $form->getData();
                    }

                    $method = new \ReflectionMethod($data::class, $writePropertyPath);
                    $arguments = [];

                    foreach ($method->getParameters() as $parameter) {
                        $arguments[] = $formData[$parameter->getName()];
                    }

                    $method->invokeArgs($data, $arguments);
                }
            } catch (\Throwable $e) {
                foreach ($forms as $form) {
                    $this->formExceptionHandler->handleException($form, $data, $e);
                }
            }
        }
    }

    private function isImmutable(FormInterface $form): bool
    {
        do {
            if ($form->getConfig()->getOption('immutable')) {
                return true;
            }
        } while ($form = $form->getParent());

        return false;
    }
}
