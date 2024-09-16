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

namespace Qossmic\RichModelForms\Extension;

use Qossmic\RichModelForms\DataMapper\DataMapper;
use Qossmic\RichModelForms\DataMapper\PropertyMapperInterface;
use Qossmic\RichModelForms\DataTransformer\ValueObjectTransformer;
use Qossmic\RichModelForms\ExceptionHandling\ExceptionHandlerRegistry;
use Qossmic\RichModelForms\ExceptionHandling\FormExceptionHandler;
use Qossmic\RichModelForms\Instantiator\FormDataInstantiator;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * @author Christian Flothmann <christian.flothmann@qossmic.com>
 *
 * @internal
 */
final class RichModelFormsTypeExtension extends AbstractTypeExtension
{
    private PropertyAccessorInterface $propertyAccessor;
    private ExceptionHandlerRegistry $exceptionHandlerRegistry;
    private FormExceptionHandler $formExceptionHandler;

    public function __construct(PropertyAccessorInterface $propertyAccessor, ExceptionHandlerRegistry $exceptionHandlerRegistry, FormExceptionHandler $formExceptionHandler)
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->exceptionHandlerRegistry = $exceptionHandlerRegistry;
        $this->formExceptionHandler = $formExceptionHandler;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (null !== $options['factory'] && ($options['immutable'] || !$builder->getCompound())) {
            $builder->addViewTransformer(new ValueObjectTransformer($this->exceptionHandlerRegistry, $this->propertyAccessor, $builder));
        }

        if (null === $dataMapper = $builder->getDataMapper()) {
            return;
        }

        $builder->setDataMapper(new DataMapper($dataMapper, $this->propertyAccessor, $this->formExceptionHandler));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('read_property_path', null);
        $resolver->setAllowedTypes('read_property_path', ['string', 'null', \Closure::class]);

        $resolver->setDefault('write_property_path', null);
        $resolver->setAllowedTypes('write_property_path', ['string', 'null', \Closure::class]);

        $resolver->setDefault('property_mapper', null);
        $resolver->setAllowedTypes('property_mapper', [PropertyMapperInterface::class, 'null']);

        $resolver->setDefault('expected_exception', null);
        $resolver->setAllowedTypes('expected_exception', ['string', 'string[]', 'null']);
        $resolver->setNormalizer('expected_exception', function (Options $options, $value) {
            if (null !== $value) {
                @trigger_error('The "expected_exception" option is deprecated since RichModelFormsBundle 0.2 and will be removed in 03. Use the "handle_exception" option instead.', \E_USER_DEPRECATED);

                $value = (array) $value;
            }

            return $value;
        });
        $resolver->setDefault('handle_exception', null);
        $resolver->setAllowedTypes('handle_exception', ['string', 'string[]', 'null']);
        $resolver->setNormalizer('handle_exception', function (Options $options, $value) {
            if (null !== $value && null !== $options['expected_exception']) {
                throw new InvalidConfigurationException('The "expected_exception" and "handle_exception" options cannot be used at the same time.');
            }

            if (null === $value && null !== $options['expected_exception']) {
                return $options['expected_exception'];
            }

            if (null !== $value) {
                $value = (array) $value;
            }

            return $value;
        });

        $resolver->setDefault('exception_handling_strategy', null);
        $resolver->setNormalizer('exception_handling_strategy', function (Options $options, $value) {
            if (null !== $value && null !== $options['expected_exception']) {
                throw new InvalidConfigurationException('The "expected_exception" and "exception_handling_strategy" options cannot be used at the same time.');
            }

            if (null !== $value && null !== $options['handle_exception']) {
                throw new InvalidConfigurationException('The "handle_exception" and "exception_handling_strategy" options cannot be used at the same time.');
            }

            if (null !== $options['handle_exception']) {
                return null;
            }

            if (null === $value) {
                $value = ['type_error', 'fallback'];
            }

            $value = (array) $value;

            foreach ($value as $strategy) {
                if (!$this->exceptionHandlerRegistry->has($strategy)) {
                    throw new InvalidConfigurationException(\sprintf('The "%s" error handling strategy is not registered.', $strategy));
                }
            }

            return $value;
        });

        $resolver->setDefault('factory', null);
        $resolver->setAllowedTypes('factory', ['string', 'array', 'null', \Closure::class]);
        $resolver->setNormalizer('factory', function (Options $options, $value) {
            if (\is_string($value) && !class_exists($value)) {
                throw new InvalidConfigurationException(\sprintf('The configured value for the "factory" option is not a valid class name ("%s" given).', $value));
            }

            if (\is_array($value) && !\is_callable($value)) {
                throw new InvalidConfigurationException('An array used for the "factory" option must be a valid callable.');
            }

            return $value;
        });
        $resolver->setDefault('factory_argument', null);
        $resolver->setAllowedTypes('factory_argument', ['null', 'string']);

        $resolver->setDefault('immutable', false);
        $resolver->setAllowedTypes('immutable', 'bool');
        $resolver->setNormalizer('immutable', function (Options $options, $value) {
            if ($value && null === $options['factory']) {
                throw new InvalidConfigurationException('Immutable objects require a configured factory.');
            }

            return $value;
        });

        $resolver->setNormalizer('data_class', function (Options $options, $value) {
            if (null !== $value && $options['immutable']) {
                throw new InvalidConfigurationException('The "data_class" option cannot be used on immutable forms.');
            }

            if (!$options['immutable'] && null !== $options['factory'] && \is_string($options['factory'])) {
                return $options['factory'];
            }

            return $value;
        });

        $resolver->setNormalizer('empty_data', function (Options $options, $value) {
            if (null !== $options['factory']) {
                return function (FormInterface $form) use ($options) {
                    if ($options['immutable']) {
                        // the view data of value objects is represented by an array, a dedicated view transformer
                        // will create the object representation during reverse transformation
                        return [];
                    }

                    try {
                        /* @phpstan-ignore-next-line */
                        return (new FormDataInstantiator($options['factory'], $form))->instantiateObject();
                    } catch (\Throwable $e) {
                        $this->formExceptionHandler->handleException($form, $form->getData(), $e);
                    }
                };
            }

            return $value;
        });
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }
}
