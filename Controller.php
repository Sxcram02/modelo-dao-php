<?php
	namespace Src\App;
	/**
	 * @author Sxcram02 ms2d0v4@gmail.com
	 * Controller
	 * @interface
	 * Interfaz que establece las bases para cualquier tipo de controlador
	 */
	interface Controller {		
		/**
		 * @author Sxcram02 ms2d0v4@gmail.com
		 * @static
		 * index
		 * Método que pretende ser la vista inicial del controlador MVC
		 * @return void
		 */
		public static function index();		

		/**
		 * @author Sxcram02 ms2d0v4@gmail.com
		 * @static
		 * create
		 * Método que permite la insercción si son correctos de los datos obtenidos en el objeto Request por el método http POST, insertados a través del Modelo de la base de datos.
		 * @param  Request $request
		 * @return void
		 */
		public static function create(Request $request);

		/**
		 * @author Sxcram02 ms2d0v4@gmail.com
		 * @static
		 * update
		 * Método que recibe un objeto Request con los datos a editar, además del modelo al que se asociarán los cambios y a través del Modelo si los datos son adecuados se hará la insercción en la base de datos.
		 * @param Request $request
		 * @return void
		 */
		public static function update(Request $request);
	}

?>
