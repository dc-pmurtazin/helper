<?php

namespace AppBundle\Internal;

class Log
{
	public static $level2color =
		[
			'INFO' => 'rgba(46, 184, 0, 0.2)',
			'WARNING' => 'rgba(255, 204, 51, 0.2)',
			'ERROR' => 'rgba(245, 0, 61, 0.2)',
		];

	public $date = null;
	public $id = null;
	public $level = null;
	public $message = null;

	public function getColor()
	{
		return 
			isset(self::$level2color[$this->level])
				? self::$level2color[$this->level]
				: 'rgba(0, 0, 0, 0.1)'
			;
	}
}