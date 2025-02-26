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

namespace OpenSC\RichModelForms\DataTransformer;

use OpenSC\RichModelForms\ExceptionHandling\ExceptionHandlerRegistry;
use OpenSC\RichModelForms\ExceptionHandling\ExceptionToErrorMapperTrait;
use OpenSC\RichModelForms\Instantiator\ViewDataInstantiator;
use Symfony\Component\Form\ButtonBuilder;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @author Christian Flothmann <christian.flothmann@qossmic.com>
 */
final class ValueObjectTransformer implements DataTransformerInterface
{
    use ExceptionToErrorMapperTrait;

    private PropertyAccessorInterface $propertyAccessor;
    private FormBuilderInterface $form;

    public function __construct(ExceptionHandlerRegistry $exceptionHandlerRegistry, PropertyAccessorInterface $propertyAccessor, FormBuilderInterface $form)
    {
        $this->exceptionHandlerRegistry = $exceptionHandlerRegistry;
        $this->propertyAccessor = $propertyAccessor;
        $this->form = $form;
    }

    /**
     * @param object|null $value
     *
     * @return array<string,bool|int|string|null>|bool|int|string|null
     */
    public function transform(mixed $value): array|bool|int|string|null
    {
        if (null === $value) {
            return null;
        }

        if ($this->form->getCompound()) {
            $viewData = [];

            /** @var string $name */
            foreach ($this->form as $name => $child) {
                if ($child instanceof ButtonBuilder) {
                    continue;
                }

                if (!$child->getOption('mapped')) {
                    continue;
                }

                $viewData[$name] = $this->getPropertyValue($child, $value);
            }

            return $viewData;
        }

        return $this->getPropertyValue($this->form, $value);
    }

    public function reverseTransform(mixed $value): ?object
    {
        try {
            /* @phpstan-ignore-next-line */
            return (new ViewDataInstantiator($this->form, $value))->instantiateObject();
        } catch (\Throwable $e) {
            $error = $this->mapExceptionToError($this->form, $value, $e);

            if (null !== $error) {
                throw new TransformationFailedException(strtr($error->getMessageTemplate(), $error->getParameters()), 0, $e);
            }

            throw $e;
        }
    }

    private function getPropertyValue(FormBuilderInterface $form, object $object): bool|int|string|null
    {
        if (null !== $form->getPropertyPath()) {
            /* @phpstan-ignore-next-line */
            return $this->propertyAccessor->getValue($object, $form->getPropertyPath());
        }

        $readPropertyPath = $form->getFormConfig()->getOption('read_property_path') ?? $form->getName();

        if ($readPropertyPath instanceof \Closure) {
            return $readPropertyPath($object);
        }

        /* @phpstan-ignore-next-line */
        return $this->propertyAccessor->getValue($object, $readPropertyPath);
    }
}
