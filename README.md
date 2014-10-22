# Расширение для работы с API cервиса WebMoney Exchanger

### Установка расширения

Желательно устанавливать расширение с помощью [composer](http://getcomposer.org/download/).

Выполните команду

```
php composer.phar require richweber/yii2-wm-exchanger "*"
```

или добавьте

```
"richweber/yii2-wm-exchanger": "*"
```

в раздел `require` вашего `composer.json` файла.

### Конфигурация приложения

Пример конфигурации:

```php
'components' => [
    ...
    'exchanger' => [
        'class' => 'richweber\wm\exchanger\Exchanger',
    ],
    ...
],
```

### License

**yii2-wm-exchanger** is released under the BSD 3-Clause License. See the bundled `LICENSE.md` for details.
