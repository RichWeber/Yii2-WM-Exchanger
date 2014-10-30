<?php
/**
 * Расширение Yii Framework 2 для работы с WebMoney Exchanger API.
 *
 * @copyright Copyright &copy; Roman Bahatyi, richweber.net, 2014
 * @package yii2-wm-exchanger
 * @version 1.0.0
 */

namespace richweber\wm\exchanger;

use SimpleXMLElement;

/**
 * Расширение для работы с API cервиса WebMoney Exchanger.
 *
 * Пример конфигурации:
 * ~~~
 * 'components' => [
 *       ...
 *       'exchanger' => [
 *           'class' => 'richweber\wm\exchanger\Exchanger',
 *           'wmid' => 121212121212,
 *           'keyFile' => '/path/to/key/file.kwm',
 *           'keyPassword' => 'password',
 *       ],
 *       ...
 *   ],
 * ~~~
 *
 * @author Roman Bahatyi <rbagatyi@gmail.com>
 * @since 1.0
 */
class Exchanger extends Signer
{
    /**
     * WMID пользователя
     * @var integer
     */
    public $wmid;

    /**
     * Путь к файлу ключей
     * @var string
     */
    public $keyFile;

    /**
     * Пароль от файла ключей
     * @var string
     */
    public $keyPassword;

    /**
     * URL интерфейса
     * @var string
     */
    private $url;

    /**
     * Ответ на запрос
     * @var object
     */
    private $result;

    /**
     * Объект XML-запроса
     * @var object
     */
    private $xml;

    /**
     * Метод запроса
     * @var boolean
     */
    private $isPostRequest;

    /**
     * Строка XML-запроса
     * @var string
     */
    private $xmlStringResponse;

    /**
     * Строка XML-ответа
     * @var string
     */
    private $xmlStringRequest;

    /**
     * XML-интерфейс получения текущих доступных объемов обмена.
     * Интерфейс актуализирует информацию раз в 3 минуты.
     */
    public function getVolumeExchange()
    {
        $this->url = 'https://wm.exchanger.ru/asp/XMLbestRates.asp';
        $this->isPostRequest = false;

        $this->run();
        return $this->result;
    }

    /**
     * XML-интерфейс получения текущих заявок.
     * Интерфейс актуализирует информацию раз в 1 минуту.
     *
     * @param integer $exchType Направление обмена
     * Числовое значение (от 1 до 32)
     * @throws ApiException
     * @return object Объект ответа
     */
    public function getCurrentApplications($exchType)
    {
        $this->url = 'https://wm.exchanger.ru/asp/XMLWMList.asp';
        $this->isPostRequest = false;

        if (is_numeric($exchType) && $exchType > 0 && $exchType < 33) {
            $this->url .= '?exchtype=' . $exchType;

            $this->run();
            return $this->result;
        } else {
            throw new ApiException('Invalid exchType');
        }
    }

    /**
     * XML-интерфейс получения истории курсов обмена
     * для автоматического получения информации о среднем курсе
     * обмена в указанном направлении на любую дату и время
     * с точностью до 1 часа.
     *
     * Интерфейс актуализирует информацию несколько раз в час.
     *
     * @param integer $exchType   числовое значение направления обмена
     * (числа в диапазоне 1-40)
     * @param integer $groupType  минимальный интервал времени, внутри которого
     * необходимо получить среднее значение курса, имеет четыре занчения:
     * - 1- месячный интервал
     * - 2- недельный интервал
     * - 3 -дневной интервал
     * - 4-часовой интервал
     * @param integer $yearStats  год, за который необходимо получить историю
     * по значениям среднего курса
     * @param integer $monthStats месяц, за который необходимо получить историю
     * по значениям среднего курса.
     * Параметр обязателен при значении grouptype =4, при всех остальных
     * значениях параметра grouptype - необязателен.
     * @param integer $dayStats   день, за который необходимо получить историю
     * по значениям среднего курса.
     * @param integer $hourStats  час за который необходимо получить историю
     * по значениям среднего курса
     * @param integer $weekStats  неделя (по порядку в году), за которую
     * необходимо получить историю по значениям среднего курса
     * @return object Объект ответа
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
        $this->isPostRequest = false;

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
     * XML-интерфейс списка новых заявок конкретного
     *
     * @param integer $type           тип запроса:
     * - 0 - вернуть только неоплаченные заявки
     * - 1 - вернуть оплаченные заявки, но еще не погашенные
     * (по которым еще идет обмен)
     * - 2 - вернуть только уже погашенные заявки
     * - 3 - вернуть все заявки независимо от сосотояния
     * @param integer $queryId        номер (id) новой заявки идентификатора wmid,
     * информацию по которой необходимо вернуть, если параметр не указан, то
     * возвращаются последние 20 новых заявок данного идентификатора по типу запроса
     * @param integer $capitallerWmid ВМ-идентификатор капиталлера
     * @return object Объект ответа
     */
    public function getListApplications($type, $queryId = 0, $capitallerWmid = false)
    {
        $this->url = 'https://wm.exchanger.ru/asp/XMLWMList2.asp';
        $this->buildObject();

        $sign = $this->sign($this->wmid . $type . $queryId);

        $this->xml->addChild('wmid', $this->wmid);
        $this->xml->addChild('signstr', $sign);
        $this->xml->addChild('type', $type);
        $this->xml->addChild('queryid', $queryId);
        if ($capitallerWmid) $this->xml->addChild('capitallerwmid', $capitallerWmid);

        $this->run();
        return $this->result;
    }

    /**
     * XML-интерфейс списка встречных заявок
     * конкретного ВМ-идентификатора
     *
     * @param integer  $type           тип запроса, зарезервировано для будуших применений
     * @param integer  $queryId        номер (id) встречной заявки идентификатора wmid,
     * информацию по которой необходимо вернуть, если в параметре передать "-1", то будут
     * возвращены последние 20 встречных заявок данного идентификатора
     * @param integer  $capitallerWmid ВМ-идентификатор капиталлера
     * @return object  Объект ответа
     */
    public function getListCounterApplications($type, $queryId = -1, $capitallerWmid = false)
    {
        $this->url = 'https://wm.exchanger.ru/asp/XMLWMList3.asp';
        $this->buildObject();

        $sign = $this->sign($this->wmid . $type . $queryId);

        $this->xml->addChild('wmid', $this->wmid);
        $this->xml->addChild('signstr', $sign);
        $this->xml->addChild('type', $type);
        $this->xml->addChild('queryid', $queryId);
        if ($capitallerWmid) $this->xml->addChild('capitallerwmid', $capitallerWmid);

        $this->run();
        return $this->result;
    }

    /**
     * XML-интерфейс удаления новой заявки
     * конкретного ВМ-идентификатора
     *
     * @param integer  $operId         номер, выставленной идентификатором wmid,
     * новой заявки, которую необходимо удалить и вернуть
     * остаток средств на кошелек с которого она была выставлена
     * @param integer  $capitallerWmid ВМ-идентификатор капиталлера
     * @return object  Объект ответа
     */
    public function removeApplication($operId, $capitallerWmid = false)
    {
        $this->url = 'https://wm.exchanger.ru/asp/XMLTransDel.asp';
        $this->buildObject();

        $sign = $this->sign($this->wmid . $operid);

        $this->xml->addChild('wmid', $this->wmid);
        $this->xml->addChild('signstr', $sign);
        $this->xml->addChild('operid', $operId);
        if ($capitallerWmid) $this->xml->addChild('capitallerwmid', $capitallerWmid);

        $this->run();
        return $this->result;
    }

    /**
     * XML-интерфейс изменения курса новой заявки
     * конкретного ВМ-идентификатора
     *
     * @param integer  $operId         номер, выставленной идентификатором wmid,
     * новой заявки, курс обмена которой необходимо изменить
     * @param float    $cursAmount     новое числовое значение курса обмена завки operid,
     * как прямое или обратное отношение суммы выставленой на обмен к сумме которую
     * нужно получить в результате обмена
     * @param integer  $cursType       тип курса обмена в тэге cursamount, "0" -
     * прямой курс (отношение суммы выставленной на обмен, к сумме которую
     * необходимо получить), обратный курс (отношение суммы которую необходимо
     * получить к сумме выставленной на обмен)
     * @param integer  $capitallerWmid ВМ-идентификатор капиталлера
     * @return object  Объект ответа
     */
    public function changeCourseApplication(
        $operId,
        $cursAmount,
        $cursType = 0,
        $capitallerwmid = false
    )
    {
        $this->url = 'https://wm.exchanger.ru/asp/XMLTransIzm.asp';
        $this->buildObject();

        $sign = $this->sign($this->wmid . $operId . $cursType . $cursAmount);

        $this->xml->addChild('wmid', $this->wmid);
        $this->xml->addChild('signstr', $sign);
        $this->xml->addChild('operid', $operId);
        $this->xml->addChild('curstype', $cursType);
        $this->xml->addChild('cursamount', $cursAmount);
        if ($capitallerWmid) $this->xml->addChild('capitallerwmid', $capitallerWmid);

        $this->run();
        return $this->result;
    }

    /**
     * XML-интерфейс объединенеия двух новых заявок
     * конкретного ВМ-идентификатора
     *
     * @param integer  $operId         номер, выставленной идентификатором wmid,
     * новой заявки, к которой необходимо присоединить заявку unionoperid
     * @param integer  $unionOperId    номер, выставленной идентификатором wmid,
     * новой заявки, которую необходимо присоединить к заявке operid, при этом
     * обе суммы к обмену будут объеденены и курс получившейся
     * заявки operid останется прежним
     * @param integer  $capitallerWmid ВМ-идентификатор капиталлера
     * @return object  Объект ответа
     */
    public function unionTwoApplications($operId, $unionOperId, $capitallerWmid = false)
    {
        $this->url = 'https://wm.exchanger.ru/asp/XMLTransUnion.asp';
        $this->buildObject();

        $sign = $this->sign($thism->wmid . $operId . $unionOperId);

        $this->xml->addChild('wmid', $this->wmid);
        $this->xml->addChild('signstr', $sign);
        $this->xml->addChild('operid', $operId);
        $this->xml->addChild('unionoperid', $unionOperId);
        if ($capitallerWmid) $this->xml->addChild('capitallerwmid', $capitallerWmid);

        $this->run();
        return $this->result;
    }

    /**
     * XML-интерфейс постановки новой заявки на обмен
     *
     * @param string  $inPurse        номер кошелька ВМ-идентификатора wmid,
     * с которого необходимо взять сумму к обмену для постановки заявки.
     * на данный кошелек должно быть установлено доверие на выполнение переводов
     * от имени идентификатора сервиса системы
     * WMT - WM#128984249415 - секции wm.exchanger
     * @param string  $outPurse       номер кошелька ВМ-идентификатора wmid,
     * на который будут поступать средства по мере обмена
     * @param float   $inAmount       сумма, которая будет автоматически переведена
     * с кошелька inPurse на кошелек сервиса секции wm.exchanger и выставлена к обмену
     * @param float   $outAmount      сумма, которую необходимо перевести на
     * кошелек outPurse по завершению обмена
     * @param integer $capitallerWmid ВМ-идентификатор капиталлера
     * @return object Объект ответа
     */
    public function submitNewExchange(
        $inPurse,
        $outPurse,
        $inAmount,
        $outAmount,
        $capitallerWmid = false
    )
    {
        $this->url = 'https://wm.exchanger.ru/asp/XMLTrustPay.asp';
        $this->buildObject();

        $sign = $this->sign($this->wmid . $inPurse . $outPurse . $inAmount . $outAmount);

        $this->xml->addChild('wmid', $this->wmid);
        $this->xml->addChild('signstr', $sign);
        $this->xml->addChild('inpurse', $inPurse);
        $this->xml->addChild('outpurse', $outPurse);
        $this->xml->addChild('inamount', $inAmount);
        $this->xml->addChild('outamount', $outAmount);
        if ($capitallerWmid) $this->xml->addChild('capitallerwmid', $capitallerWmid);

        $this->run();
        return $this->result;
    }

    /**
     * XML-интерфейс скупки из своей новой заяки
     * чужой новой противоположной по направлению обмена
     *
     * @param integer $isxTrid        номер, выставленной идентификатором wmid,
     * новой заявки, c которой будет производится покупка чужой заявки номер desttrid
     * @param integer $destTrid       номер чужой заявки, которую необходимо купить
     * @param integer $destStamp      число равное сумме часа, минуты и секунды из даты заявки,
     * которую необходимо купить (querydate в интерфейсе 2), в случае если заявка,
     * которую необходимо купить - изменялась и у нее будет другое время
     * (другая сумма часа минуты и секунды), траназкция не пройдет.
     * Для совместимости в данном параметре можно ничего не передавать
     * или передавать число 1001, в этом случае проверка на измененность
     * заявки производиться не будет.
     * @param integer $capitallerWmid ВМ-идентификатор капиталлера
     * @return object Объект ответа
     */
    public function buyApplication($isxTrid, $destTrid, $destStamp, $capitallerWmid = false)
    {
        $this->url = 'https://wm.exchanger.ru/asp/XMLQrFromTrIns.asp';
        $this->buildObject();

        $sign = $this->sign($this->wmid . $isxTrid . $destTrid);

        $this->xml->addChild('wmid', $this->wmid);
        $this->xml->addChild('signstr', $sign);
        $this->xml->addChild('isxtrid', $isxTrid);
        $this->xml->addChild('desttrid', $destTrid);
        $this->xml->addChild('deststamp', $destStamp);
        if ($capitallerWmid) $this->xml->addChild('capitallerwmid', $capitallerWmid);

        $this->run();
        return $this->result;
    }

    /**
     * XML-интерфейс списка встречных заявок
     * по конкретной НОВОЙ заявке ВМ-идентификатора
     *
     * @param integer $queryId        номер (id) новой заявки идентификатора wmid,
     * информацию по которой необходимо вернуть
     * @param integer $capitallerWmid ВМ-идентификатор капиталлера
     * @return object Объект ответа
     */
    public function getListCounterOrders($queryId, $capitallerWmid = false)
    {
        $this->url = 'https://wm.exchanger.ru/asp/XMLWMList3Det.asp';
        $this->buildObject();

        $sign = $this->sign($this->wmid . $queryid);

        $this->xml->addChild('wmid', $this->wmid);
        $this->xml->addChild('signstr', $sign);
        $this->xml->addChild('queryid', $queryId);
        if ($capitallerWmid) $this->xml->addChild('capitallerwmid', $capitallerWmid);

        $this->run();
        return $this->result;
    }

    /**
     * Выполняем запрос и получаем ответ
     */
    private function run()
    {
        if ($this->isPostRequest) $this->formatXML();
        $this->request();
        $this->getObject();
    }

    /**
     * Проверяем конфигурацию модуля аутентификации WMSigner
     * и создаем пустой объект XML-запроса
     *
     * @throws ApiException
     */
    public function buildObject()
    {
        $this->isPostRequest = true;

        $this->xml = new SimpleXMLElement('<wm.exchanger.request></wm.exchanger.request>');

        if (empty($this->wmid)) {
            throw new ApiException('WMID not provided.');
        }

        if (!file_exists($this->keyFile)) {
            throw new ApiException('Key file not found: ' . $this->keyFile);
        }

        $key = file_get_contents($this->keyFile);

        $keyData = unpack('vreserved/vsignFlag/a16hash/Vlength/a*buffer', $key);
        $keyData['buffer'] = $this->encryptKey($keyData['buffer'], $this->wmid, $this->keyPassword);

        if (!$this->verifyHash($keyData)) {
            throw new ApiException('Hash check failed. Key file seems corrupted.');
        }

        $this->initSignVariables($keyData['buffer']);
    }

    /**
     * Получаем объект с строки ответа
     */
    private function getObject()
    {
        $this->result = simplexml_load_string($this->xmlStringResponse);
    }

    /**
     * Получаем объект класса DOMElement
     * из объекта класса SimpleXMLElement
     */
    private function formatXML()
    {
        $dom = dom_import_simplexml($this->xml)->ownerDocument;
        $dom->formatOutput = true;

        $this->xmlStringRequest = $dom->saveXML();
    }

    /**
     * Выполняем запрос к сервису WebMoney Exchanger
     */
    protected function request()
    {
        $handler = curl_init($this->url);

        if ($this->isPostRequest) {
            curl_setopt($handler, CURLOPT_POST, true);
            curl_setopt($handler, CURLOPT_POSTFIELDS, $this->xmlStringRequest);
        }

        curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($handler, CURLOPT_SSLVERSION, 3);

        ob_start();
        if (!curl_exec($handler)) {
            throw new ApiException('Error while performing request (' . curl_error($handler) . ')');
        }
        $this->xmlStringResponse = ob_get_contents();
        ob_end_clean();
        curl_close($handler);

        if (trim($this->xmlStringResponse) == '') {
            throw new ApiException('No response was received from the server');
        }
    }
}
