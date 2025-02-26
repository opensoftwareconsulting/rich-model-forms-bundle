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

namespace OpenSC\RichModelForms\Tests\Fixtures\Form;

use OpenSC\RichModelForms\DataMapper\PropertyMapperInterface;

/**
 * @author Christian Flothmann <christian.flothmann@qossmic.com>
 */
class CancellationDateMapper implements PropertyMapperInterface
{
    public function readPropertyValue(mixed $data): mixed
    {
        return $data->cancelledFrom();
    }

    public function writePropertyValue(mixed $data, mixed $value): void
    {
        $data->cancelFrom($value);
    }
}
