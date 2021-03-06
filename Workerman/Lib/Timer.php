<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Workerman\Lib;

use Workerman\Events\EventInterface;
use Exception;

/**
 * Timer.
 *
 * example:
 * Workerman\Lib\Timer::add($time_interval, callback, array($arg1, $arg2..));
 */
class Timer
{
    /**
     * Tasks that based on ALARM signal.
     * [
     *   run_time => [[$func, $args, $persistent, time_interval],[$func, $args, $persistent, time_interval],..]],
     *   run_time => [[$func, $args, $persistent, time_interval],[$func, $args, $persistent, time_interval],..]],
     *   ..
     * ]
     *
     * @var array
     */
    protected static $_tasks = array();

    /**
     * event
     *
     * @var \Workerman\Events\EventInterface
     */
    protected static $_event = null;

    /**
     * Init.
     *
     * @param \Workerman\Events\EventInterface $event
     * @return void
     */
    public static function init($event = null)
    {
        if ($event) {
            self::$_event = $event;
        } else {
            pcntl_signal(SIGALRM, array('\Workerman\Lib\Timer', 'signalHandle'), false);
        }
    }

    /**
     * ALARM signal handler.
     *
     * @return void
     */
    public static function signalHandle()
    {
        if (!self::$_event) {
            pcntl_alarm(1);
            self::tick();
        }
    }

    /**
     * Add a timer.
     *
     * @param int      $time_interval
     * @param callback $func
     * @param mixed    $args
     * @param bool     $persistent
     * @return bool
     */
    public static function add($time_interval, $func, $args = array(), $persistent = true)
    {
        if ($time_interval <= 0) {
            echo new Exception("bad time_interval");
            return false;
        }

        if (self::$_event) {
            return self::$_event->add($time_interval,
                $persistent ? EventInterface::EV_TIMER : EventInterface::EV_TIMER_ONCE, $func, $args);
        }

        if (!is_callable($func)) {
            echo new Exception("not callable");
            return false;
        }

        if (empty(self::$_tasks)) {
            pcntl_alarm(1);
        }

        $time_now = time();
        $run_time = $time_now + $time_interval;
        if (!isset(self::$_tasks[$run_time])) {
            self::$_tasks[$run_time] = array();
        }
        self::$_tasks[$run_time][] = array($func, (array)$args, $persistent, $time_interval);
        return true;
    }


    /**
     * Tick.
     *
     * @return void
     */
    public static function tick()
    {
        if (empty(self::$_tasks)) {
            pcntl_alarm(0);
            return;
        }

        $time_now = time();
        foreach (self::$_tasks as $run_time => $task_data) {
            if ($time_now >= $run_time) {
                foreach ($task_data as $index => $one_task) {
                    $task_func     = $one_task[0];
                    $task_args     = $one_task[1];
                    $persistent    = $one_task[2];
                    $time_interval = $one_task[3];
                    try {
                        call_user_func_array($task_func, $task_args);
                    } catch (\Exception $e) {
                        echo $e;
                    }
                    if ($persistent) {
                        self::add($time_interval, $task_func, $task_args);
                    }
                }
                unset(self::$_tasks[$run_time]);
            }
        }
    }

    /**
     * Remove a timer.
     *
     * @param mixed $timer_id
     * @return bool
     */
    public static function del($timer_id)
    {
        if (self::$_event) {
            return self::$_event->del($timer_id, EventInterface::EV_TIMER);
        }

        return false;
    }

    /**
     * Remove all timers.
     *
     * @return void
     */
    public static function delAll()
    {
        self::$_tasks = array();
        pcntl_alarm(0);
        if (self::$_event) {
            self::$_event->clearAllTimer();
        }
    }
    
    /**
     * get current time.
     *
     * @format  Y-m-d H:i:s   2016-08-03  10:10:10
     * @return string
     */
    
    public static function curStrTime()
    {
    	return date("Y-m-d H:i:s");
    }
    /**
     * get current time 10:00:00   取当前时间戳或取当天指定点的时间戳 format = "10:00:00"
     * @return int  timestamp
     */
    public static function timeStamp($time = null)
    {
    	if($time)
    		return strtotime($time);
    	else
    		return time();
    }
    
    public static function assginTimeStamp($time = "10:00:00")
    {
    	$strtime = date("Y-m-d");
    	return strtotime($strtime." ".$time);
    }
    
    //php获取今日开始时间戳和结束时间戳(23:59:59)
    public static function beginTodayStamp(){
    	return mktime(0,0,0,date('m'),date('d'),date('Y'));
    }
    
    public static function endTodayStamp() {
    	return  mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
    }
    
    public static function beginYesterdayStamp(){
    	return  mktime(0,0,0,date('m'),date('d')-1,date('Y'));
    }
    public static function endYesterdayStamp(){
    	return mktime(0,0,0,date('m'),date('d'),date('Y'))-1;
    }
    public static function beginLastweekStamp(){
    	return mktime(0,0,0,date('m'),date('d')-date('w')+1-7,date('Y'));
    }
    public static function endLastweekStamp(){
    	return mktime(23,59,59,date('m'),date('d')-date('w')+7-7,date('Y'));
    }
    
    public static function beginThisMonthStamp(){
    	return mktime(0,0,0,date('m'),1,date('Y'));
    }
    public static function endThisMonthStamp(){
    	return mktime(23,59,59,date('m'),date('t'),date('Y'));
    }
}
