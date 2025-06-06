<?php
	namespace Src\App;
	/**
	 * Filtro
	 * Objeto usado para la validación de datos
	 * @author Sxcram02 ms2d0v4@gmail.com
	 * @use GestorStrings
	 * @final
	 */
	final class Filtro {	
		use GestorStrings;
		/**
		 * Array con los errores encontrados en la svalidaciones
		 * @var array
		 * @default []
		 * @author Sxcram02 ms2d0v4@gmail.com
		 * @private
		 * @static
		 */
		private static array $errores = [];

		/**
		 * __construct
		 * Método contructor del objeto
		 * @return void
		 * @author Sxcram02 ms2d0v4@gmail.com
		 * @private
		 */
		private function __construct(){}
		
		/**
		 * validarRegex
		 * Método para validar un valor con una regex y longitud adecuada, retorna true o false
		 * @param string $regex 
		 * @param mixed $valor
		 * @return bool
		 * @author Sxcram02 ms2d0v4@gmail.com
		 * @private
		 */
		private function validarRegex(string $regex,mixed $valor): bool {
			$esValido = true;
			preg_match($regex, $valor, $coincidencias);

			$esValido = count($coincidencias) <= 0 ? false : $esValido;
			return $esValido;
		}
		
		/**
		 * validate
		 * Método que recibe un array assoc que tiene como clave el valor que debe ser validado y como valor las validaciones a usar.
		 * @param  array $validaciones 
		 * @return bool
		 * @static
		 * @author Sxcram02 ms2d0v4@gmail.com
		 */
		public static function validate(array $validaciones): bool {
			$filtro = new Filtro();
			$pruebasValidaciones = [];
			
			// El formato de las validaciones es una cadena notnull|hasValue|dni|...
			foreach ($validaciones as $valor => $validacion) {
				if (is_string($validacion)) {
					$pruebasValidaciones[$valor] = self::separarString(cadena: $validacion,pattern: "/(?=.*[a-z\s]+)\|/");
				}
			}
			
			$resultados = [];
			// Una o varias pruebas
			foreach ($pruebasValidaciones as $valor => $pruebas) {
				if(is_string($pruebas)){
					$funcion = preg_replace("/\s+/","",$pruebas);
					if(!method_exists($filtro,$funcion)){
						return false;
					}
					$resultados[] = $filtro -> $funcion($valor);
				}else{
					foreach ($pruebas as $funcion) {
						$funcion = preg_replace("/^\s+|\s+$/","",$funcion);
						if(!method_exists($filtro,$funcion)){
							return false;
						}
						$resultados[] = $filtro -> $funcion($valor);
					}
				}
			}
			
			$numResultados = count($resultados);
			$aciertos = 0;
			$fallados = 0;
			// Uno o varios fallos
			foreach($resultados as $resultado){
				if($resultado === true){
					$aciertos++;
				}
			}

			return $aciertos == $numResultados;
		}
		
		/**
		 * maxlenght
		 * Método que determina si la longitud de la cadena es correcta y/o adecuada, recibiendo dos parámetros el primero la cadena y el segundo la longitud máxima que debe tener.
		 * @param ?string $valor - La cadena que va a ser analizada.
		 * @param int|string $longitud - La longitud máxima de la cadena.
		 * @default 255
		 * @return bool
		 * @author Sxcram02 ms2d0v4@gmail.com
		 */
		public function maxlenght(?string $valor, int|string $longitud = 255): bool {
			$esValido = strlen($valor) <= $longitud;
			$errores = self::$errores;

			if(!$esValido){
				if(count($errores) > 0){
					foreach($errores as $indice => $error){
						if($error['codigo_error'] == "707"){
							$error['mensaje_error'] = "$valor supera la longitud de $longitud caractéres";
							$errores[$indice] = $error;
						}
					}
				}else{
					$errores[] = [
						"codigo_error" => "707",
						"mensaje_error" => "$valor supera la longitud de $longitud  caractéres"
					];
				}
				self::$errores = $errores;
			}

			return $esValido;
		}
		
		/**
		 * nombre
		 * Método que recibe una cadena que debe cumplir dicha expresión regular asociada a un nombre propio de una persona, retorna false en caso de superar los 90 caractéres o en caso de no cumplir con la regex.
		 * @param  string $nombre - cadena que será analizada por la expresión rgular.
		 * @return bool
		 * @author Sxcram02 ms2d0v4@gmail.com
		 */
		public function nombrepropio(?string $nombre): bool {		
			$errores = self::$errores;
			$regexNombre = "/[áéñíóúüa-z0-9\s]+/i";
			$longitudMaxima = 90;

			if(!$this -> maxlenght($nombre, $longitudMaxima)){
				return false;
			}

			if(!$this -> validarRegex($regexNombre, $nombre)){
				$errores[] = [
					"codigo_error" => "708",
					"mensaje_error" => "La cadena \"$nombre\" no puede tener caractéres especiales a excepcion de la ñ, tíldes y diéresis"
				];

				self::$errores = $errores;
				return false;
			}

			return true;
		}

		/**
		 * number
		 * Método que recibe una cadena que debe cumplir dicha expresión regular asociada a un número entero, retorna false en caso de superar los 90 caractéres o en caso de no cumplir con la regex.
		 * @param string|int|null $numero - cadena que será analizada por la expresión rgular.
		 * @return bool
		 * @author Sxcram02 ms2d0v4@gmail.com
		 */
		public function number(string|int|null $numero): bool {		
			$errores = self::$errores;
			$regexNumero = "/^[0-9]+|[0-9]+\.[0-9]+$/";
			$longitudMaxima = 90;

			if(!$this -> maxlenght($numero, $longitudMaxima)){
				return false;
			}


			if(!$this -> validarRegex($regexNumero, $numero)){
				$errores[] = [
					"codigo_error" => "709",
					"mensaje_error" => "La cadena \"$numero\" no cumple con las características de un número"
				];

				self::$errores = $errores;
				return false;
			}

			return true;
		}
		
		/**
		 * apellidos
		 * Método que recibe una cadena que debe cumplir dicha expresión regular asociada a un apellido o apellidos de una persona, retorna false en caso de superar los 100 caractéres o en caso de no cumplir con la regex.
		 * @param string $apellido - cadena con uno o dos apellidos.
		 * @return bool
		 * @author Sxcram02 ms2d0v4@gmail.com
		 */
		public function apellidos(string $apellido): bool {
			$regexApellido = "/[a-z\s]+/i";
			$longitudMaxima = 100;
			$errores = self::$errores;

			if(!$this -> maxlenght($apellido, $longitudMaxima)){
				return false;
			}


			if(!$this -> validarRegex($regexApellido, $apellido)){
				$errores[] = [
					"codigo_error" => "710",
					"mensaje_error" => "La cadena \"$apellido\" no cumple con las características de un apellido"
				];

				self::$errores = $errores;
				return false;
			}

			return true;
		}
		
		/**
		 * email
		 * Método que recibe una cadena que debe cumplir dicha expresión regular asociada a un correo electrónico de una persona, retorna false en caso de superar los 150 caractéres o en caso de no cumplir con la regex.
		 * @param string $email - cadena que deberá corresponder con un correo electrónico.
		 * @return bool
		 * @author Sxcram02 ms2d0v4@gmail.com
		 */
		public function email(string $email):bool {
			$regexEmail = "/^[0-9a-z-_\.]+\@[a-z]{5,}\.[a-z]{2,3}$/";
			$longitudMaxima = 250;
			$errores = self::$errores;

			if(!$this -> maxlenght($email, $longitudMaxima)){
				return false;
			}


			if(!$this -> validarRegex($regexEmail, $email)){
				$errores[] = [
					"codigo_error" => "711",
					"mensaje_error" => "La cadena \"$email\" no cumple con las carcterísticas de un correo electrónico"
				];

				self::$errores = $errores;
				return false;
			}

			return true;
		}
		
		/**
		 * telefono
		 * Método que recibe una cadena que debe cumplir dicha expresión regular asociada a un número de teléfono de una persona o del trabajo, retorna false en caso de superar los 13 caractéres o en caso de no cumplir con la regex.
		 * @param string $telefono - número de teléfono con o sin prefijo.
		 * @return bool
		 * @author Sxcram02 ms2d0v4@gmail.com
		 */
		public function telefono(string $telefono): bool {
			$regexTelefono = "/^(\+[0-9]{1,3})?[0-9]{9}$/";
			$longitudMaxima = 13;
			$errores = self::$errores;

			if(!$this -> maxlenght($telefono, $longitudMaxima)){
				return false;
			}


			if(!$this -> validarRegex($regexTelefono, $telefono)){
				$errores[] = [
					"codigo_error" => "712",
					"mensaje_error" => "La cadena \"$telefono\" no cumple con las características de un número de teléfono móvil"
				];

				self::$errores = $errores;
				return false;
			}

			return true;
		}
		
		/**
		 * text
		 * Método que recibe una cadena que debe cumplir dicha expresión regular asociada a un texto, retorna false en caso de superar los 300 caractéres o en caso de no cumplir con la regex.
		 * @param string $texto - cualquier cadena de texto que será comprobada.
		 * @return bool
		 * @author Sxcram02 ms2d0v4@gmail.com
		 */
		public function text(string $texto): bool {
			$errores = self::$errores;
			$regexTexto = "/[\wáéíóúöü\.\,\:\;]+/i";
			$longitudMaxima = 300;
			if(!$this -> maxlenght($texto, $longitudMaxima)){
				return false;
			}

			if(!$this -> validarRegex($regexTexto, $texto)){
				$errores[] = [
					"codigo_error" => "713",
					"mensaje_error" => "El texto contiene caractéres inválidos"
				];

				self::$errores = $errores;
				return false;
			}

			return true;
		}

		/**
		 * notnull
		 * Método que determina si un valor es nulo o esta vacío retorna false en caso de ser cierto o true si no es nulo ni esta vacío el dato pasado por parámetro.
		 * @param mixed $valor - cualquier cadena, número o booleano que será comprobado.
		 * @return bool
		 * @author Sxcram02 ms2d0v4@gmail.com
		 */
		public function notnull(mixed $valor): bool {
			$errores = self::$errores;
			$tieneValor = isset($valor);

			if(!$tieneValor){
				$errores[] = [
					"codigo_error" => "714",
					"mensaje_error" => "El dato introducido es nulo"
				];

				self::$errores = $errores;
				return $tieneValor;
			}

			return $tieneValor;
		}

		/**
		 * hasValue
		 * Método encargado de comprobar que un valor no este vacío
		 * @param mixed $valor - cualquier cadena, número flotante o entero o un booleano que será comprobado.
		 * @return bool
		 * @author Sxcram02 ms2d0v4@gmail.com
		 */
		public function hasValue(mixed $valor): bool {
			$errores = self::$errores;
			$estaVacio = empty($valor);

			if($estaVacio){
				$errores[] = [
					"codigo_error" => "715",
					"mensaje_error" => "El dato esta vacío"
				];

				self::$errores = $errores;
				return false;
			}

			return true;
		}

		/**
		 * dni
		 * Método que recibe una cadena que debe cumplir dicha expresión regular asociada a un documento nacional de identificación, retorna false en caso de superar los 9 caractéres o en caso de no cumplir con la regex.
		 * @param string $dni - la cadena de texto con formato 00000000X
		 * @return bool
		 * @author Sxcram02 ms2d0v4@gmail.com
		 */
		public function dni(string $dni): bool {
			$regexDni = "/^[0-9]{8}[A-Z]$|^[A-Z][0-9]{7}[A-Z]$/";
			$longitudMaxima = 9;
			$errores = self::$errores;

			if(!$this -> maxlenght($dni, $longitudMaxima)){
				
				return false;
			}


			if(!$this -> validarRegex($regexDni, $dni)){
				$errores[] = [
					"codigo_error" => "716",
					"mensaje_error" => "La cadena no cumple con la regex de un DNI o NIE"
				];

				self::$errores = $errores;
				return false;
			}

			return true;
		}

		/**
		 * year
		 * Método que recibe una cadena que debe cumplir dicha expresión regular asociada a un número de año, retorna false en caso de superar los 4 caractéres o en caso de no cumplir con la regex.
		 * @param string $year - la cadena de texto con un año entre 19XX - 20XX
		 * @return bool
		 * @author Sxcram02 ms2d0v4@gmail.com
		 */
		public function year(string $year): bool {
			$regexYear = "/^19[0-9]{2}|20[0-3][0-9]$/";
			$longitudMaxima = 4;
			$errores = self::$errores;

			if(!$this -> maxlenght($year, $longitudMaxima)){
				return false;
			}


			if(!$this -> validarRegex($regexYear, $year)){
				$errores[] = [
					"codigo_error" => "717",
					"mensaje_error" => "La cadena no cumple con la regex de un año entre 1900 y 2039"
				];

				self::$errores = $errores;
				return false;
			}

			return true;
		}

		/**
		 * fecha
		 * Método que recibe una cadena que debe cumplir dicha expresión regular asociada a una fecha, retorna false en caso de superar los 10 caractéres o en caso de no cumplir con la regex.
		 * @param string $year - la cadena de texto con un año entre 19XX - 20XX
		 * @return bool
		 * @author Sxcram02 ms2d0v4@gmail.com
		 */
		public function fecha(string $fecha): bool {
			$regexFecha = "/^19[0-9]{2}|20[0-3][0-9]\-[0][1-9]|[1][0-2]\-[0-2][0-9]|[3][0-1]$/";

			$longitudMaxima = 10;
			$errores = self::$errores;

			if(!$this -> maxlenght($fecha, $longitudMaxima)){
				return false;
			}


			if(!$this -> validarRegex($regexFecha, $fecha)){
				$errores[] = [
					"codigo_error" => "718",
					"mensaje_error" => "La cadena no cumple con la regex de una fecha entre 1900 y 2039 con formato YYYY-MM-DD"
				];

				self::$errores = $errores;
				return false;
			}

			return true;
		}

		/**
		 * password
		 * Método que recibe una cadena que debe cumplir dicha expresión regular asociada a un número de año, retorna false en caso de superar los 4 caractéres o en caso de no cumplir con la regex.
		 * @param string $year - la cadena de texto con un año entre 19XX - 20XX
		 * @return bool
		 * @author Sxcram02 ms2d0v4@gmail.com
		 */
		public function password(string $pass): bool {
			$regexPass = "/(?=[áéíúóña-z]+[0-9]+\W+).{8,}/i";
			$errores = self::$errores;

			if(!$this -> maxlenght($pass)){
				return false;
			}

			if(!$this -> validarRegex($regexPass, $pass)){
				$errores[] = [
					"codigo_error" => "719",
					"mensaje_error" => "La contraseña $pass debe contener al menos 8 caractéres con una mayúsucla, minúscula, un número y un caractér especial"
				];

				self::$errores = $errores;
				return false;
			}

			return true;
		}

		/**
		 * url
		 * Método que recibe una cadena que debe cumplir dicha expresión regular asociada a un número de año, retorna false en caso de superar los 4 caractéres o en caso de no cumplir con la regex.
		 * @param string $year - la cadena de texto con un año entre 19XX - 20XX
		 * @return bool
		 * @author Sxcram02 ms2d0v4@gmail.com
		 */
		public function url(string $url): bool {
			$regexUrl = "#^https:\/\/#i";
			$errores = self::$errores;

			if(!$this -> maxlenght($url)){
				return false;
			}

			if(!$this -> validarRegex($regexUrl, $url)){
				$errores[] = [
					"codigo_error" => "720",
					"mensaje_error" => "La url introducida no es válida"
				];

				self::$errores = $errores;
				return false;
			}

			return true;
		}

		/**
		 * getErrors
		 * Método que por defecto retorna todos los errores de las validaciones realizadas recientemente o un error en concreto
		 * @param  ?int $indiceError
		 * @default null
		 * @return array
		 * @static
		 * @author Sxcram02 ms2d0v4@gmail.com
		 */
		public static function getErrors(?int $indiceError = null):array {
			$errores = self::$errores;
			if(isset($indiceError)){
				return $indiceError < count($errores) ? $errores[$indiceError] : $errores[0];
			}

			return $errores;
		}
	}
?>
