<?php

namespace Src\App;

/**
 * Controller
 * @interface
 * Interfaz que establece las bases para cualquier tipo de controlador
 * @author Sxcram02 <ms2d0v4@gmail.com>
 */
interface Controller {
	/**
	 * index
	 * Método que pretende ser la vista inicial del controlador MVC
	 * @return void
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
	 */
	public static function index();

	/**
	 * create
	 * Método que permite la insercción si son correctos de los datos obtenidos en el objeto Request por el método http POST, insertados a través del Modelo de la base de datos.
	 * @param  Request $request
	 * @return void
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
	 */
	public static function create(Request $request);

	/**
	 * update
	 * Método que recibe un objeto Request con los datos a editar, además del modelo al que se asociarán los cambios y a través del Modelo si los datos son adecuados se hará la insercción en la base de datos.
	 * @param Request $request
	 * @return void
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
	 */
	public static function update(Request $request);
}
?>