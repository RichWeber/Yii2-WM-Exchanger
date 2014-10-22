<?php
/**
 * Расширение Yii Framework 2 для работы с WebMoney Exchanger API.
 *
 * @copyright Copyright &copy; Roman Bahatyi, richweber.net, 2014
 * @package yii2-wm-exchanger
 * @version 1.0.0
 */

namespace richweber\wm\exchanger;

/**
 * Расширение для работы с API cервиса WebMoney Exchanger.
 *
 * Пример конфигурации:
 * ~~~
 * 'components' => [
 *       ...
 *       'exchanger' => [
 *           'class' => 'richweber\wm\exchanger\Exchanger',
 *       ],
 *       ...
 *   ],
 * ~~~
 *
 * @author Roman Bahatyi <rbagatyi@gmail.com>
 * @since 1.0
 */
class Exchanger
{
    public $wmid = '';

    /**
     * XML-интерфейс получения текущих доступных объемов обмена
     */
    public function getVolumeExchange()
    {
        //
    }

    /**
     * XML-интерфейс получения текущих заявок
     */
    public function getCurrentApplications()
    {
        //
    }

    /**
     * XML-интерфейс списка новых заявок
     * конкретного ВМ-идентификатора
     */
    public function getListApplications()
    {
        //
    }

    /**
     * XML-интерфейс списка встречных заявок
     * конкретного ВМ-идентификатора
     */
    public function getListCounterApplications()
    {
        //
    }

    /**
     * XML-интерфейс удаления новой заявки
     * конкретного ВМ-идентификатора
     */
    public function removeApplication()
    {
        //
    }

    /**
     * XML-интерфейс изменения курса новой заявки
     * конкретного ВМ-идентификатора
     */
    public function changeCourseApplication()
    {
        //
    }

    /**
     * XML-интерфейс объединенеия двух новых заявок
     * конкретного ВМ-идентификатора
     */
    public function unionTwoApplications()
    {
        //
    }

    /**
     * XML-интерфейс постановки новой заявки на обмен
     */
    public function submitNewExchange()
    {
        //
    }

    /**
     * XML-интерфейс скупки из своей новой заяки
     * чужой новой противоположной по направлению обмена
     */
    public function buyApplication()
    {
        //
    }

    /**
     * XML-интерфейс получения истории курсов обмена
     */
    public function getHistoryExchangeRates()
    {
        //
    }

    /**
     * XML-интерфейс списка встречных заявок
     * по конкретной НОВОЙ заявке ВМ-идентификатора
     */
    public function getListCounterOrders()
    {
        //
    }
}
