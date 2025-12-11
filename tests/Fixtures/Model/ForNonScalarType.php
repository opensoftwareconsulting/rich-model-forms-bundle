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

namespace Qossmic\RichModelForms\Tests\Fixtures\Model;

class ForNonScalarType
{
    private function __construct(
        private \DateTimeImmutable $dateFrom,
    ) {
    }

    public static function create(\DateTimeImmutable $dateFrom): self
    {
        return new self($dateFrom);
    }

    public function getDateFrom(): \DateTimeImmutable
    {
        return $this->dateFrom;
    }
}
