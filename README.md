Rich Model Forms Bundle
=======================

[![StandWithUkraine](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/badges/StandWithUkraine.svg)](https://github.com/vshymanskyy/StandWithUkraine/blob/main/docs/README.md)

[![SWUbanner](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner2-direct.svg)](https://github.com/vshymanskyy/StandWithUkraine/blob/main/docs/README.md)

## A message to Russian 🇷🇺 people

If you currently live in Russia, please read [this message](./ToRussianPeople.md).

The Rich Model Forms Bundle enhances the [Symfony Form component](https://symfony.com/doc/current/forms.html) with
useful options that ease the work with rich domain models.

Installation
------------

Use Composer to install the bundle:

```bash
$ composer require opensc/rich-model-forms-bundle
```

When using Symfony Flex, the bundle will be enabled automatically. Otherwise, you need to make sure that the bundle is
registered in your application kernel.

Usage
-----

The bundle currently supports the following use cases:

* [Differing Property Paths For Reading And Writing](docs/mapping.md)

* [Support for constructors with arguments and for value objects](docs/factory_value_object.md)

* [Enhanced exception handling](docs/exception_handling.md)

Resources
---------

* Video - [SymfonyCon Lisbon 2018: Symfony Form Rich Domain Models - Video](https://symfonycasts.com/screencast/symfonycon2018/symfony-forms-rich-domain-models)
* Slide deck - [SymfonyCon Lisbon 2018: Symfony Form Rich Domain Models - Slides](https://speakerdeck.com/el_stoffel/using-symfony-forms-with-rich-domain-models)
