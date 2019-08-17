# Borsch - PSR-7 and PSR-17 implementation

A simple and lightweight, yet efficient, implementation of [PSR-7 HTTP Message](https://www.php-fig.org/psr/psr-7/) and [PSR-17 HTTP Factories](https://www.php-fig.org/psr/psr-17/).


## Installation

Via [Composer](https://getcomposer.org/) :
```
$ composer require borsch/http-message
```

## Usage

The PSR-7 classes do not have any additional method, other than the one listed in the specification.  
For instance, there is no method `Response::withJson()` as in [Slim Framework](http://slimframework.com/).  
There is however, an `Helper` class that you can use to parse $_FILES when creating your ServerRequest isntance.

### Instantiation

This package uses the PSR-17 constructor signatures as the PSR-7 classes constructors, allowing a continuity and natural logic between the two packages.

```php
require_once __DIR__.'/vendor/autoload.php';

use Borsch\Http\Request;
use Borsch\Http\Factory

$request = new Request(
    'GET',
    'https://github.com/debuss/borch-http-message'
);
// OR
$request = Factory::getInstance()->createRequest(
    'GET',
    'https://github.com/debuss/borch-http-message'
);
```

Play a bit with both PSR-7 classes and the PSR-17 Factory to see how to use them ;) .

## License

```
MIT License

Copyright (c) 2018 Alexandre DEBUSSCHERE

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```
