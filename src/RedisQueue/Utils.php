<?php

namespace Sunra\RedisQueueBundle\RedisQueue;

class Utils
{
	static function secondsToHuman($s)
	{
	
	  $str = '';
	  	
	  $d = intval($s/86400);
	  $s -= $d*86400;
	
	  $h = intval($s/3600);
	  $s -= $h*3600;
	
	  $m = intval($s/60);
	  $s -= $m*60;
	
	  if ($d) $str = $d . 'd ';
	  if ($h) $str .= $h . 'h ';
	  if ($m) $str .= $m . 'm ';
	  if ($s) $str .= $s . 's';
	
	  return $str;
    }


    static function dateToHuman($time)
    {
        return date("j.n.Y H:i:s", $time);
    }


    static function timeStringForMessageId()
    {
        return date("j.n.Y_H:i:s", time());
    }


    static function guid()
    {
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"

        $uuid = /* chr(123).*/ // "{"
            substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12)
            /*.chr(125)*/;// "}"

        return $uuid;
    }

}