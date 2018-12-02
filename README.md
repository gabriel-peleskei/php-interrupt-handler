# php-interrupt-handler


Requirement
----------
PHP >= 5.6

Usage
-----
```php
$handler = \GabrielPeleskei\InterruptHandler\Handler::getInstance();
$listener = $handler->register([SIGINT]);
while(true) {
    if ($lister->interrupt) {
        echo "SIGINT received...";
        break;
    }
}
```

For more examples, look into the /examples folder.