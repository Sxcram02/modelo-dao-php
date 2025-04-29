<?php
	namespace Src\App;
	use DateTime;
	/**
	 * @author Sxcram02 ms2d0v4@gmail.com
	 * Files
	 * La clase files se encargará de gestionar todos los archivos multimedia introducidos, gestionando sus rutas, tamaño y formato.
	*/
	class Files {
		/**
		 * @author Sxcram02 ms2d0v4@gmail.com
		 * El tamaño maximo de los archivos multimedia en megabytes.
		 * @static
		 * @var int $megabytesMaximos
		 * @default 45
		 */
		public static int $megabytesMaximos = 45;

		/**
		 * @author Sxcram02 ms2d0v4@gmail.com
		 * Los tipos de archivos admitidos.
		 * @static
		 * @var array $tiposAdmitidos
		 * @default ["image/jpeg","image/png","image/gif","image/jpg","video/mp4","application/pdf"]
		 */
		public static array $tiposAdmitidos = [
			"image/jpeg",
			"image/png",
			"image/gif",
			"image/jpg",
			"video/mp4",
			"application/pdf"
		];

		/**
		 * @author Sxcram02 ms2d0v4@gmail.com
		 * El nombre del archivo.
		 * @private
		 * @var ?string $nombreArchivo
		 * @default null
		 */
		private ?string $nombreArchivo = null;
		
		/**
		 * @author Sxcram02 ms2d0v4@gmail.com
		 * La ubicacion del archivo.
		 * @private
		 * @var ?string $ubicacionArchivo
		 * @default null
		 */
		private ?string $ubicacionArchivo = null;

		
		/**
		 * @author Sxcram02 ms2d0v4@gmail.com
		 * @private
		 * __construct
		 * Método que recibe el nombre del archivo, su carpeta temporal y su tipo y lo mueve a la carpeta correspondiente.
		 * @param  string $nombreArchivo
		 * @param  string $carpetaTemporal
		 * @param  string $tipo
		 * @return void
		 */
		private function __construct(string $nombreArchivo,string $carpetaTemporal,string $tipo){

			$tipoArchivo = match($tipo){
				"pdf" => "pdf",
				"jpg" => "img",
				"png" => "img",
				"jpeg" => "img",
				"gif" => "videos",
				"mp4" => "videos"

			};
			
			$rutaDestino = "public/$tipoArchivo/$nombreArchivo";
			if(move_uploaded_file($carpetaTemporal,$rutaDestino)){
				$this -> nombreArchivo = $nombreArchivo;
				$this -> ubicacionArchivo = $rutaDestino;	
			}
		}
		
		
		/**
		 * @author Sxcram02 ms2d0v4@gmail.com
		 * @static
		 * filtrarImagen
		 * Método que recibe un array con los datos de una imagen y retorna un array con el resultado un objeto Files o un booleano y en caso de fallo un mensaje en base al error
		 * @param  array $imagen
		 * @return array
		 */
		public static function filtrarImagen(array $imagen): array{
			$resultado = [
				"resultado" => true,
				"mensaje" => null
			];

			// Los datos de la imagen debe ser un array asociativo.
			if(!esArrayAssociativo($imagen)){
				$resultado["resultado"] = false;
				$resultado["mensaje"] = "El formato de los datos de la imagen, son incorrectos";
				return $resultado;
			}

			// Tipo MIME valido.
			if(!self::esUnTipoValido($imagen["type"])){
				$resultado["resultado"] = false;
				$resultado["mensaje"] = "El archivo no tiene el formato permitido";
				return $resultado;
			}

			// Tamaño del archivo.
			$bytesImagen = $imagen["size"];
			$kiloBytesImagen = $bytesImagen / 1024;
			$megabytesImagen = $kiloBytesImagen / 1024;

			if($megabytesImagen >= self::$megabytesMaximos){
				$resultado["resultado"] = false;
				$resultado["mensaje"] = "El archivo no puede superar los 45MB";
				return $resultado;
			}

			// Errores en la subida del archivo.
			if($imagen["error"] != 0){
				$resultado["resultado"] = false;
				$resultado["mensaje"] = match($imagen["error"]){
					UPLOAD_ERR_INI_SIZE => "El archivo excede los limites del servidor",
					UPLOAD_ERR_FORM_SIZE => "El archivo excede el limite del formulario",
					UPLOAD_ERR_PARTIAL => "El archivo esta dañado o incompleto",
					UPLOAD_ERR_NO_FILE => "No se subio ningun archivo",
					default => "Error en el servidor"
				};

				return $resultado;
			}

			$tipoArchivo = match($imagen["type"]){
				"image/jpeg" => "jpg",
				"image/png" => "png",
				"image/gif" => "gif",
				"image/jpg" => "jpeg",
				"video/mp4" => "mp4",
				"application/pdf" => "pdf"
			};

			$fechaActual = new DateTime();
			$fechaActual = $fechaActual -> format('D-M-YY');
			$nuevoNombre = $imagen["name"] . $fechaActual;
			$nuevoNombre = hash('sha256',$nuevoNombre);

			// Subir archivo.
			$resultado["resultado"] = self::subirArchivo(
				nombreArchivo: $nuevoNombre,
				carpetaTemporal: $imagen["tmp_name"],
				tipo: $tipoArchivo
			);
			
			return $resultado;

		}
		
		/**
		 * @author Sxcram02 ms2d0v4@gmail.com
		 * @private
		 * @static
		 * esUnTipoValido
		 * Método que termina a partir de uno o varios tipo introducidos, si existen estos en los tipos admitidos por el objeto
		 * @param  string|array $tipos
		 * @return bool
		 */
		private static function esUnTipoValido(string|array $tipos): bool {
			if(!is_array($tipos)){
				return in_array($tipos,self::$tiposAdmitidos);
			}

			foreach($tipos as $tipo){
				if(!in_array($tipo,self::$tiposAdmitidos)){
					return false;
				}
			}

			return true;
		}
		
		/**
		 * @author Sxcram02 ms2d0v4@gmail.com
		 * @private
		 * @static
		 * subirArchivo
		 * Método encargado de intentar con un try/catch instanciar el objeto Files y retornarlo a partir del nombre del archivo, su ubicacion y su tipo
		 * @param  string $nombreArchivo
		 * @param  string $carpetaTemporal
		 * @param  string $tipo
		 * @return bool
		 */
		private static function subirArchivo(string $nombreArchivo,string $carpetaTemporal,string $tipo): bool|Files {
			try {
				$file = new Files($nombreArchivo,$carpetaTemporal,$tipo);
				return $file;
			} catch (\Error|\ErrorException|\Exception $error) {
				return false;
			}
		}
		
		/**
		 * @author Sxcram02 ms2d0v4@gmail.com
		 * getUbicacionArchivo
		 * Método encargado de obtener la nueva ubicación del archivo
		 * @return string
		 */
		public function getUbicacionArchivo(): string {
			return $this -> ubicacionArchivo;
		}

		/**
		 * @author Sxcram02 ms2d0v4@gmail.com
		 * getNombreArchivo
		 * Método encargado de retorna el nuevo nombre del archivo
		 * @return string
		 */
		public function getNombreArchivo(): string {
			return $this -> nombreArchivo;
		}
	}
?>