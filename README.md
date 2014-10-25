# Расширение для работы с API cервиса WebMoney Exchanger

### Установка расширения

Желательно устанавливать расширение с помощью [composer](http://getcomposer.org/download/).

Выполните команду

```
php composer.phar require richweber/yii2-wm-exchanger "*"
```

или добавьте

```
"richweber/yii2-wm-exchanger": "dev-master"
```

в раздел `require` вашего `composer.json` файла.

### Конфигурация приложения

Пример конфигурации:

```php
'components' => [
    ...
    'exchanger' => [
        'class' => 'richweber\wm\exchanger\Exchanger',
        'wmid' => 121212121212,
        'keyFile' => '/path/to/key/file.kwm',
        'keyPassword' => 'password',
    ],
    ...
],
```

**Получение текущих заявок:**

```php
$exchType = 1;

Yii::$app->exchanger->getCurrentApplications($exchType);
```

### Документация

- [XML-интерфейсы](http://wm.exchanger.ru/asp/rules_xml.asp)
- [Правила работы](http://wm.exchanger.ru/asp/rules_wm.asp)
- [Обучающие ролики](http://wm.exchanger.ru/asp/flashmovies.asp)

### License

**yii2-wm-exchanger** is released under the BSD 3-Clause License. See the bundled `LICENSE.md` for details.
