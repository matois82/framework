<?php
namespace Colibri\Application\Application;

use Colibri\Application\Application;
use Colibri\Cache\Cache;
use Colibri\Config\Config;
use Colibri\Http\Request;
use Colibri\Session\Session;

/**
 * Application API for most common functionality of app manipulation.
 */
class API
{
    /**
     * @var \Colibri\Application\Application
     */
    protected static $application = null;

    /**
     * API constructor.
     *
     * @param \Colibri\Application\Application $application
     */
    public function __construct(Application &$application)
    {
        self::$application = $application;
    }

    /**
     * @param string $division
     * @param string $module
     * @param string $method
     * @param array  $params
     *
     * @return string
     *
     * @throws \Colibri\Routing\Exception\NotFoundException
     */
    public static function getModuleView($division, $module, $method, ...$params)
    {
        return self::$application->getModuleView($division, $module, $method, $params);
    }

    /**
     * @param array $params
     *
     * @return string
     */
    private static function getCacheKeyForCall(array $params)
    {
        static $domainLevel = null;

        $params += $_GET;
        $keyStr = '';
        foreach ($params as $param) {
            $keyStr .= serialize($param);
        }

        $keyStr .= Request::domainPrefix(
            $domainLevel ?? $domainLevel = count(explode('.', Config::application('domain')))
        );

        return md5($keyStr);
    }

    /**
     * @param string $division
     * @param string $module
     * @param string $method
     * @param array  $params
     *
     * @return string
     *
     * @throws \Colibri\Routing\Exception\NotFoundException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public static function getModuleViewCached($division, $module, $method, ...$params)
    {
        if (Config::application('useCache') && ! DEBUG) {
            $key      = self::getCacheKeyForCall(func_get_args());
            $retValue = Cache::remember($key, function () use ($division, $module, $method, $params) {
                return self::getModuleView($division, $module, $method, ...$params);
            });
        } else {
            $retValue = self::getModuleView($division, $module, $method, ...$params);
        }

        return $retValue;
    }

    /**
     * @param string $type
     * @param array  $values
     */
    protected static function pass($type, array $values)
    {
        Session::flash($type, $values);
    }

    /**
     * @param string $message
     */
    public static function passNotice(string $message)
    {
        Session::flash('app_notice', $message);
    }

    /**
     * Устанавливает и передаёт ошибки следующему вызванному скрипту
     * (однократно - удаляется в следующем вызванном скрипте).
     *
     * @param array $errors повторный вызов перезаписывет
     */
    public static function passErrors(array $errors)
    {
        self::pass('app_errors', $errors);
    }

    /**
     * Передаёт переменные в следующий вызванный скрипт.
     * Повторный вызов перезаписывет полностью все переменные.
     *
     * @param array $vars передаваемые переменные в виде ассоциативного массива
     */
    public static function passVars(array $vars)
    {
        self::pass('app_vars', $vars);
    }

    /**
     * @param string           $type
     * @param string|null      $key
     * @param array|mixed|null $default
     *
     * @return mixed
     */
    protected static function passed($type, $key = null, $default = null)
    {
        return Session::get(
            $type . ($key !== null ? '.' . $key : ''),
            $key === null && $default === null ? [] : $default
        );
    }

    /**
     * @return string|null
     */
    public static function notice()
    {
        return Session::get('app_notice');
    }

    /**
     * Возвращает переданные (из предыдущего скрипта) ошибки.
     * При вызове без параметров вернёт пустой массив.
     *
     * @param string|null      $key
     * @param array|mixed|null $default
     *
     * @return mixed
     */
    public static function errors($key = null, $default = null)
    {
        return self::passed('app_errors', $key, $default);
    }

    /**
     * Возвращает переданные (из предыдущего скрипта) переменные.
     * При вызове без параметров вернёт пустой массив.
     *
     * @param string|null      $key
     * @param array|mixed|null $default
     *
     * @return mixed
     */
    public static function vars($key = null, $default = null)
    {
        return self::passed('app_vars', $key, $default);
    }
}
