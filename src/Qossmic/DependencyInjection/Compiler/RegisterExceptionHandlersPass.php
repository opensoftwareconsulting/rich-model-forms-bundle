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

namespace Qossmic\RichModelForms\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\TypedReference;

/**
 * @author Christian Flothmann <christian.flothmann@qossmic.com>
 *
 * @internal
 */
final class RegisterExceptionHandlersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('qossmic.rich_model_forms.exception_handler.registry')) {
            return;
        }

        $exceptionHandlerRegistry = $container->getDefinition('qossmic.rich_model_forms.exception_handler.registry');

        $exceptionHandlers = [];
        $strategies = [];

        foreach ($container->findTaggedServiceIds('qossmic.rich_model_forms.exception_handler') as $id => $tag) {
            /** @var class-string $class */
            $class = $container->getParameterBag()->resolveValue($container->getDefinition($id)->getClass());
            $exceptionHandlers[$id] = new TypedReference($id, $class);

            foreach ($tag as $attributes) {
                /* @phpstan-ignore-next-line */
                $strategies[$attributes['strategy']] = $id;
            }
        }

        $exceptionHandlersLocator = ServiceLocatorTagPass::register($container, $exceptionHandlers);
        $exceptionHandlerRegistry->setArgument('$container', $exceptionHandlersLocator);
        $exceptionHandlerRegistry->setArgument('$strategies', $strategies);
    }
}
