<?php
/**
 * DESCRIPTION:
 * 		Singleton Pattern realisation
 * SYNOPSIS:
 * 		1) YourClass extends Fruitframe_Pattern_Singleton
 * 		2) $obYourClass = YourClass::init();
 * 		That's all folks!
 */
namespace Fruitframe;

abstract class Pattern_Singleton
{
	/**
	 * Required constructor.
	 */
	protected function __construct(){}

	/**
	 * Object initialisation instead of using new ... construction
	 * @return $this Object of class which extends Fruitframe_Pattern_Singleton
	 */
	static public function init()
	{
		static $instance;
		if (!is_object($instance)){
			$className = get_called_class();
			$instance = new $className();
		}
		return $instance;
	}
}