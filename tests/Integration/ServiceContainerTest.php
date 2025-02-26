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

namespace OpenSC\RichModelForms\Tests\Integration;

use OpenSC\RichModelForms\ExceptionHandling\ArgumentTypeMismatchExceptionHandler;
use OpenSC\RichModelForms\ExceptionHandling\FallbackExceptionHandler;
use OpenSC\RichModelForms\Tests\Fixtures\DependencyInjection\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ServiceContainerTest extends KernelTestCase
{
    public function testServicesCanBeBuilt(): void
    {
        $container = $this->bootKernel()->getContainer();

        foreach ($container->getParameter('opensc.rich_model_forms.test_service_aliases') as $id => $type) {
            $this->assertInstanceOf($type, $container->get($id));
        }
    }

    public function testArgumentTypeMismatchExceptionHandlingStrategyIsRegistered(): void
    {
        $container = $this->bootKernel()->getContainer();
        $exceptionHandlerRegistry = $container->get('test.opensc.rich_model_forms.exception_handler.registry');

        $this->assertInstanceOf(ArgumentTypeMismatchExceptionHandler::class, $exceptionHandlerRegistry->get('type_error'));
    }

    public function testFallbackExceptionHandlingStrategyIsRegistered(): void
    {
        $container = $this->bootKernel()->getContainer();
        $exceptionHandlerRegistry = $container->get('test.opensc.rich_model_forms.exception_handler.registry');

        $this->assertInstanceOf(FallbackExceptionHandler::class, $exceptionHandlerRegistry->get('fallback'));
    }

    public function testExceptionIsThrownIfExceptionHandlingStrategyIsNotKnown(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $container = $this->bootKernel()->getContainer();
        $exceptionHandlerRegistry = $container->get('test.opensc.rich_model_forms.exception_handler.registry');

        $exceptionHandlerRegistry->get('unknown');
    }

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }
}
