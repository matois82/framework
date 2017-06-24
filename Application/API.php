<?php
namespace Colibri\Application;

use Colibri\Cache\Memcache;
use Colibri\Config\Config;

/**
 * Application API for most common functionality of app manipulation
 */
class API
{
    /**
     * @var Engine
     */
    protected static $moduleSystem = null;
    /**
     * @var array assoc-array of errors (key/field => error-message);
     */
    public static $errors = null;
    /**
     * @var array assoc-array of vars (name => value);
     */
    public static $vars = null;

    /**
     * API constructor.
     *
     * @param \Colibri\Application\Engine $mSystem
     */
    public function __construct(Engine &$mSystem)
    {
        self::$moduleSystem = $mSystem;

        static::init();
    }

    /**
     * @return void
     */
    protected static function init()
    {
        static::initFromSession();
    }

    /**
     * @return void
     */
    protected static function initFromSession()
    {
        if (isset($_SESSION['api_errors'])) {
            self::$errors = unserialize($_SESSION['api_errors']);
            unset($_SESSION['api_errors']);
        }
        if (isset($_SESSION['api_vars'])) {
            self::$vars = unserialize($_SESSION['api_vars']);
            unset($_SESSION['api_vars']);
        }
    }

    /**
     * @param string $division
     * @param string $module
     * @param string $method
     * @param ...
     *
     * @return string
     * @throws \Colibri\Application\Exception\NotFoundException
     * @throws \LogicException
     */
    public static function callModuleMethod($division, $module, $method/* , ... */)
    {
        $params = array_slice(func_get_args(), 3);

        return self::$moduleSystem->callModuleMethod($division, $module, $method, $params);
    }

    /**
     * @param string $division
     * @param string $module
     * @param string $method
     * @param ...
     *
     * @return string
     * @throws \Colibri\Application\Exception\NotFoundException
     * @throws \LogicException
     */
    public static function getModuleView($division, $module, $method/* , ... */)
    {
        $params = array_slice(func_get_args(), 3);

        return self::$moduleSystem->getModuleView($division, $module, $method, $params);
    }

    /**
     * @param array $params
     *
     * @return string
     */
    private static function getCacheKeyForCall(array $params)
    {
        $params += $_GET;
        $keyStr = '';
        foreach ($params as $param)
            $keyStr .= serialize($param);

        $keyStr .= self::$moduleSystem->domainPrefix;

        return md5($keyStr);
    }

    /**
     * @param string $division
     * @param string $module
     * @param string $method
     * @param ...
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function callModuleMethodCached($division, $module, $method/* , ... */)
    {
        $params = func_get_args();

        if (Config::application('useCache') && ! DEBUG) {
            $key      = self::getCacheKeyForCall($params);
            $retValue = Memcache::remember($key, function () use ($params) {
                return call_user_func_array([self, 'callModuleMethod'], $params);
            });
        } else { // TODO [alek13]: cache 2 file
            $retValue = call_user_func_array([self, 'callModuleMethod'], $params);
            //$retValue=self::callModuleMethod($division,$module,$method);
        }

        return $retValue;
    }

    /**
     * @param string $division
     * @param string $module
     * @param string $method
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public static function getModuleViewCached($division, $module, $method/* , ... */)
    {
        $params = func_get_args();

        if (Config::application('useCache') && ! DEBUG) {
            $key      = self::getCacheKeyForCall($params);
            $retValue = Memcache::remember($key, function () use ($params) {
                return call_user_func_array(['self', 'getModuleView'], $params);
            });
        } else { // [TODO]: cache 2 file
            //call_user_func_array(array(self,'getModuleView'),$params);
            $retValue = call_user_func_array(['self', 'getModuleView'], $params);
        }

        return $retValue;
    }

    /**
     * @param string $varName
     *
     * @return mixed
     */
    public static function getTemplateVar($varName)
    {
        return self::$moduleSystem->responser->getTemplate()->vars[$varName];
    }

    /**
     * @param string $sessionKey
     * @param array  $values
     */
    protected static function pass($sessionKey, array $values)
    {
        if ($values === null) {
            unset($_SESSION[$sessionKey]);

            return;
        }

        $existingErrors        = isset($_SESSION[$sessionKey])
            ? unserialize($_SESSION[$sessionKey])
            : [];
        $_SESSION[$sessionKey] = serialize(array_merge($existingErrors, $values));
    }

    /**
     * передаёт устанавливает и передаёт ошибки следующему вызванному скрипту-странице (однократно - удаляется в
     * след.вызв-ом скрипте само).
     *
     * @param array $errors повторный вызов добавляет или перезаписывет с существующие ключами. вызов со значением null
     *                      стирает ошибки и отменяет передачу в след. контроллер
     */
    public static function passErrors(array $errors = null)
    {
        self::pass('api_errors', $errors);
    }

    /**
     * передаёт переменные в следующий вызванный скрипт
     *
     * @param array $vars передаваемые переменные в виде ассоциативного массива. повторный вызов затирает предыдущее, а
     *                    со значением null отменяет передачу
     */
    public static function passVars(array $vars = null)
    {
        self::pass('api_vars', $vars);
    }

    /**
     * @param string $session_id
     */
    public static function catchSession($session_id)
    {
        // @todo move this into Session
        session_write_close();
        session_id($session_id);
        session_start();
        self::initFromSession();
    }
}
