<?php
namespace Colibri\Application;

use Colibri\Cache\Cache;
use Colibri\Config\Config;
use Colibri\Database\AbstractDb\Driver\Connection;
use Colibri\Pattern\Helper;

// TODO [alek13]: bring out into separate package:
//  - based on event system
//  - pass Response
//  - use templates
//
class StupidDebugger extends Helper
{
    public static function output($time, bool $showProfilerInfoOnDebug = false, bool $showAppDevToolsOnDebug = false)
    {
        if ( ! Config::application('debug')) {
            return;
        }
        if ($showProfilerInfoOnDebug) {
            echo '<pre style="background-color:#333;color:lime;padding:4px;border:solid 1px lime">';
            echo '<div align=center>';
            echo 'memory usage: <b>' . memory_get_peak_usage() . '</b>';
            $time = microtime(true) - $time;
            echo '<div style=/*font-size:' . (10 + round($time * 10)) . 'px>Время генерации страницы: <b>' . $time . '</b></div>';
            echo 'количество запросов: <b>' . Connection::$queriesCount . '</b><br>';
            echo 'количество запросов к Cache-у: <b>' . Cache::getQueriesCount() . '</b><br>';
            echo '</div>';
            echo Connection::$strQueries;
            echo '</pre>';
        }
        if ($showAppDevToolsOnDebug) {
            echo '<div style="position:absolute;top:0;right:0;border:solid 1px #678;margin:4px;padding:4px 6px;background-color:#def;opacity:0.6;z-index:10000">';
            echo '<a href=/devtools/sess_destroy>session destroy</a> | ';
            echo '<a href=/devtools/unset_session>unset session</a> | ';
            echo '<a href=/devtools/show_session>show session</a>';
            echo '</div>';
        }
    }
}
