CHANGELOG
=========

0.5.0
-----

* add Symfony 8 support
* ensure compatibility with Symfony 7.4

0.4.0
-----

* add Symfony 7 support
* the following classes are internal and can break BC at any time:
  * `RegisterExceptionHandlersPass`
  * `RichModelFormsExtension`
  * `RichModelFormsTypeExtension`
* [BC BREAK] add `mixed` return-type to the following methods:
  * `ObjectInstantiator::getArgumentData()`
  * `ObjectInstantiator::getData()`
  * `PropertyMapperInterface::readPropertyValue()`
  * `PropertyMapperInterface::writePropertyValue()`
* [BC BREAK] mark the following classes as final:
  * `ArgumentTypeMismatchExceptionHandler`
  * `ChainExceptionHandler`
  * `DataMapper`
  * `Error`
  * `ExceptionHandlerRegistry`
  * `FallbackExceptionHandler`
  * `FormDataInstantiator`
  * `FormExceptionHandler`
  * `GenericExceptionHandler`
  * `ValueObjectTransformer`
  * `ViewDataInstantiator`
* drop support for Symfony 5.4, 6.0, 6.1, 6.2 and 6.3
* drop support for PHP 7.4 and 8.0

0.3.0
-----

* allow `psr/container` 2.0
* drop support for Symfony 5.3
* drop support for PHP 7.2 and 7.3

0.2.0
-----

* add Symfony 6 support
* drop support for Symfony 5.0, 5.1, and 5.2
* drop support for PHP 7.1

0.1.0
-----

Initial release of the bundle under its new `qossmic/rich-model-forms-bundle` package name. This release is
feature-equivalent to the `0.8.0` release of the `sensiolabs-de/rich-model-forms-bundle` package apart from
all deprecated services and PHP classes being removed.
