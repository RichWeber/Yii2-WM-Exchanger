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
    /**
     * [$wmid description]
     * @var string
     */
    public $wmid = '';

    /**
     * [$url description]
     * @var [type]
     */
    private $url;

    /**
     * [$result description]
     * @var [type]
     */
    private $result;

    /**
     * [$xmlString description]
     * @var [type]
     */
    private $xmlString;

    /**
     * XML-интерфейс получения текущих доступных объемов обмена
     */
    public function getVolumeExchange()
    {
        $this->url = 'https://wm.exchanger.ru/asp/XMLbestRates.asp';
        $this->run();
        return $this->result;
    }

    /**
     * XML-интерфейс получения текущих заявок
     * @param integer $exchType Направление обмена
     */
    public function getCurrentApplications($exchType)
    {
        $this->url = 'https://wm.exchanger.ru/asp/XMLWMList.asp';

        if (is_numeric($exchType) && $exchType > 0 && $exchType < 33) {
            $this->url .= '?exchtype=' . $exchType;

            $this->run();
            return $this->result;
        } else {
            //
        }
    }

    /**
     * XML-интерфейс получения истории курсов обмена
     * @param [type] $exchType   [description]
     * @param [type] $groupType  [description]
     * @param [type] $yearStats  [description]
     * @param [type] $monthStats [description]
     * @param [type] $dayStats   [description]
     * @param [type] $hourStats  [description]
     * @param [type] $weekStats  [description]
     */
    public function getHistoryExchangeRates(
        $exchType,
        $groupType,
        $yearStats,
        $monthStats,
        $dayStats,
        $hourStats,
        $weekStats = false
    )
    {
        $this->url = 'https://wm.exchanger.ru/asp/XMLQuerysStats.asp';
        // ?exchtype=1&grouptype=4&yearstats=2011&monthstats=11&daystats=11&hourstats=11

        $this->url .= '?exchtype=' . $exchType;
        $this->url .= '&grouptype=' . $groupType;
        $this->url .= '&yearstats=' . $yearStats;
        $this->url .= '&monthstats=' . $monthStats;
        if ($weekStats) $this->url .= '&weekstats=' . $weekStats;
        $this->url .= '&daystats=' . $dayStats;
        $this->url .= '&hourstats=' . $hourStats;

        $this->run();
        return $this->result;
    }

    /**
     * XML-интерфейс списка новых заявок
     * конкретного ВМ-идентификатора
     */
    public function getListApplications()
    {
        $this->url = 'https://wm.exchanger.ru/asp/XMLWMList2.asp';
    }

    /**
     * XML-интерфейс списка встречных заявок
     * конкретного ВМ-идентификатора
     */
    public function getListCounterApplications()
    {
        $this->url = 'https://wm.exchanger.ru/asp/XMLWMList3.asp';
    }

    /**
     * XML-интерфейс удаления новой заявки
     * конкретного ВМ-идентификатора
     */
    public function removeApplication()
    {
        $this->url = 'https://wm.exchanger.ru/asp/XMLTransDel.asp';
    }

    /**
     * XML-интерфейс изменения курса новой заявки
     * конкретного ВМ-идентификатора
     */
    public function changeCourseApplication()
    {
        $this->url = 'https://wm.exchanger.ru/asp/XMLTransIzm.asp';
    }

    /**
     * XML-интерфейс объединенеия двух новых заявок
     * конкретного ВМ-идентификатора
     */
    public function unionTwoApplications()
    {
        $this->url = 'https://wm.exchanger.ru/asp/XMLTransUnion.asp';
    }

    /**
     * XML-интерфейс постановки новой заявки на обмен
     */
    public function submitNewExchange()
    {
        $this->url = 'https://wm.exchanger.ru/asp/XMLTrustPay.asp';
    }

    /**
     * XML-интерфейс скупки из своей новой заяки
     * чужой новой противоположной по направлению обмена
     */
    public function buyApplication()
    {
        $this->url = 'https://wm.exchanger.ru/asp/XMLQrFromTrIns.asp';
    }

    /**
     * XML-интерфейс списка встречных заявок
     * по конкретной НОВОЙ заявке ВМ-идентификатора
     */
    public function getListCounterOrders()
    {
        $this->url = 'https://wm.exchanger.ru/asp/XMLWMList3Det.asp';
    }

    /**
     * [run description]
     * @return [type] [description]
     */
    private function run()
    {
        $this->request();
        $this->getObject();
    }

    private function getObject()
    {
        $this->result = simplexml_load_string($this->xmlString);
    }

    /**
     * [request description]
     * @return [type] [description]
     */
    protected function request()
    {
        $handler = curl_init($this->url);

        // curl_setopt($handler, CURLOPT_POST, true);
        // curl_setopt($handler, CURLOPT_POSTFIELDS, $request->getData());

        curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($handler, CURLOPT_SSLVERSION, 3);

        ob_start();
        if (!curl_exec($handler)) {
            throw new RequesterException('Error while performing request (' . curl_error($handler) . ')');
        }
        $content = ob_get_contents();
        ob_end_clean();
        curl_close($handler);

        if (trim($content) == '') {
            throw new RequesterException('No response was received from the server');
        }

        $this->xmlString = $content;
    }
}
