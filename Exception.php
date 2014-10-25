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
 * Exception represents a generic exception for all purposes.
 *
 * @author Roman Bahatyi <rbagatyi@gmail.com>
 * @since 1.0
 */
class Exception extends \Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Exception';
    }
}
