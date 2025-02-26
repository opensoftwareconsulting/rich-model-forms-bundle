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

namespace OpenSC\RichModelForms\ExceptionHandling;

use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;

/**
 * @author Christian Flothmann <christian.flothmann@qossmic.com>
 */
final class ArgumentTypeMismatchExceptionHandler implements ExceptionHandlerInterface
{
    public function getError(FormConfigInterface $formConfig, mixed $data, \Throwable $e): ?Error
    {
        if ($e instanceof \TypeError) {
            if (str_starts_with($e->getMessage(), 'Argument ') || str_contains($e->getMessage(), 'Argument #')) {
                // code for extracting the expected type borrowed from the error handling in the Symfony PropertyAccess component
                if (false !== $pos = strpos($e->getMessage(), 'must be of type ')) {
                    $pos += 16;
                } elseif (false !== $pos = strpos($e->getMessage(), 'must be an instance of ')) {
                    $pos += 23;
                } else {
                    $pos = strpos($e->getMessage(), 'must implement interface ') + 25;
                }

                return new Error($e, 'This value should be of type {{ type }}.', [
                    '{{ type }}' => substr($e->getMessage(), $pos, strpos($e->getMessage(), ',', $pos) - $pos),
                ]);
            }

            if (str_starts_with($e->getMessage(), 'Cannot assign ') && false !== $pos = strpos($e->getMessage(), ' of type ')) {
                return new Error($e, 'This value should be of type {{ type }}.', [
                    '{{ type }}' => substr($e->getMessage(), $pos + 9),
                ]);
            }

            // we are not interested in type errors that are not related to argument type nor property type (PHP 7.4+) mismatches
            return null;
        }

        // type errors that are triggered when the property accessor performs the write-call are wrapped in an
        // InvalidArgumentException by the PropertyAccess component
        if ($e instanceof InvalidArgumentException) {
            return new Error($e, 'This value should be of type {{ type }}.', [
                '{{ type }}' => substr($e->getMessage(), 27, strpos($e->getMessage(), '",') - 27),
            ]);
        }

        return null;
    }
}
