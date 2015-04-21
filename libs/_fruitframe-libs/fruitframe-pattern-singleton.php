<?php
/**
 * DESCRIPTION:
 * 		Класс для реализации паттерна Singleton.
 * SYNOPSIS:
 * 		1) YourClass extends Fruitframe_Pattern_Singleton
 * 		2) $obYourClass = YourClass::init();
 * 		Вот и всё. Подобный вызов всегда будет возвращать один и тот же экзземпляр класса.
 */
namespace Fruitframe;

abstract class Pattern_Singleton
{
	/**
	 * Обязательный для реализации закрытый извне конструктор класса. Не должен принимать параметры.
	 */
	protected function __construct(){}

	/**
	 * Статическая функция инициализации получения экземпляра класса
	 * @return $this Объект класса, наследующего Fruitframe_Pattern_Singleton
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