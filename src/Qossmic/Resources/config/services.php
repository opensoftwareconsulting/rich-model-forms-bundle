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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Qossmic\RichModelForms\ExceptionHandling\ArgumentTypeMismatchExceptionHandler;
use Qossmic\RichModelForms\ExceptionHandling\ExceptionHandlerRegistry;
use Qossmic\RichModelForms\ExceptionHandling\FallbackExceptionHandler;
use Qossmic\RichModelForms\ExceptionHandling\FormExceptionHandler;
use Qossmic\RichModelForms\Extension\RichModelFormsTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('qossmic.rich_model_forms.type_extension', RichModelFormsTypeExtension::class)
            ->args([
                service('property_accessor'),
                service('qossmic.rich_model_forms.exception_handler.registry'),
                service('qossmic.rich_model_forms.form_exception_handler'),
            ])
            ->tag('form.type_extension', ['extended_type' => FormType::class])

        ->set('qossmic.rich_model_forms.form_exception_handler', FormExceptionHandler::class)
            ->args([
                service('qossmic.rich_model_forms.exception_handler.registry'),
                service('translator')->ignoreOnInvalid(),
                '%validator.translation_domain%',
            ])

        ->set('qossmic.rich_model_forms.exception_handler.registry', ExceptionHandlerRegistry::class)
            ->args([
                abstract_arg('service locator'),
                abstract_arg('exception handling strategies'),
            ])

        ->set('qossmic.rich_model_forms.exception_handler.strategy.argument_type_mismatch', ArgumentTypeMismatchExceptionHandler::class)
            ->tag('qossmic.rich_model_forms.exception_handler', ['strategy' => 'type_error'])

        ->set('qossmic.rich_model_forms.exception_handler.strategy.fallback', FallbackExceptionHandler::class)
           ->tag('qossmic.rich_model_forms.exception_handler', ['strategy' => 'fallback'])
    ;
};
