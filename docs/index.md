# SamsonFramework Router
## Route creation
### Route variables
Route pattern can have variable placeholder which is intend to be used as parameters for controller actions. All variable placeholder logic is very similar to awesome [FastRoute](https://github.com/nikic/FastRoute) package.
```php
new Route('/user/{id}', 'my_callback_function');
```
Variable placeholder should be surrounded by `{variable_name}` symbols and its identifier inside this template. All route variables should have unique identifiers.

Variable placeholder can also have [PHP PRCE](http://php.net/manual/ru/reference.pcre.pattern.syntax.php) filters:
```php
new Route('/user/{id:\d+}', 'my_callback_function');
```
In the example above we have added a `\d+` pattern for filtering variable, this means that route placeholder variable should have atleast one digit(0-9) symbol to match this filter.
So `/user/vitaly` would not match this pattern, but `/user/123` will.