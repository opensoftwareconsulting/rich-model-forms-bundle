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

use Symfony\Component\Form\FormInterface;

/**
 * @author Christian Flothmann <christian.flothmann@qossmic.com>
 */
final class FormDataInstantiator extends ObjectInstantiator
{
    private FormInterface $form;
    /** @var array<string,string> */
    private array $formNameForArgument;

    /**
     * @param class-string|\Closure|((callable(): object)&array{0: class-string|object, 1: string}) $factory
     */
    public function __construct(string|callable $factory, FormInterface $form)
    {
        parent::__construct($factory);

        $this->form = $form;
        $this->formNameForArgument = [];

        foreach ($form as $child) {
            /* @phpstan-ignore-next-line */
            $this->formNameForArgument[$child->getConfig()->getOption('factory_argument') ?? $child->getName()] = $child->getName();
        }
    }

    protected function isCompoundForm(): bool
    {
        return $this->form->getConfig()->getCompound();
    }

    protected function getData(): mixed
    {
        if ($this->isCompoundForm()) {
            $data = [];

            foreach ($this->form as $childForm) {
                $data[$childForm->getConfig()->getName()] = $childForm->getData();
            }

            return $data;
        }

        return $this->form->getData();
    }

    protected function getArgumentData(string $argument): mixed
    {
        return $this->form->get($this->formNameForArgument[$argument])->getData();
    }
}
