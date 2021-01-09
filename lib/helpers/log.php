<?php
namespace Starlabs\Tools\Helpers;

class Log
{
    protected $data = '';

    public function collect($data)
    {
        ini_set("memory_limit", "4096M");
        if(
           is_object($data) ||
           is_array($data)
        ) {
            $this->data .= var_export($data, 1).'<br><br>';
        }else
            $this->data .= $data.'<br><br>';

        $bt = debug_backtrace();
        $caller = array_shift($bt);
        $this->data .= "==========================================================<br>";
        $this->data .= 'Файл: '.$caller['file']."<br>";
        $this->data .= 'Строка вызова: '.$caller['line']."<br>";
        $this->data .= "==========================================================<br><br>";

        return $this;
    }

    public function send($event = 'Запись в журнал',$idEntity = null)
    {

        $bt = debug_backtrace();
        $caller = array_shift($bt);
        $this->data .= "<br><br>==========================================================<br>";
        $this->data .= 'Файл: '.$caller['file']."<br>";
        $this->data .= 'Строка вызова: '.$caller['line'];
        \CEventLog::Log('DEBUG',$event,'starlabs.tools',$idEntity,$this->data);
    }

    public static function AddEvent($data,$event='Запись в журнал',$idEntity = null)
    {
        ini_set("memory_limit", "4096M");
        if(
           is_object($data) ||
           is_array($data)
        ) {
            $data = var_export($data, 1);
        }

        $bt = debug_backtrace();
        $caller = array_shift($bt);
        $data .= "<br><br>==========================================================<br>";
        $data .= 'Файл: '.$caller['file']."<br>";
        $data .= 'Строка вызова: '.$caller['line'];
        \CEventLog::Log('DEBUG',$event,'starlabs.tools',$idEntity,$data);
    }
}