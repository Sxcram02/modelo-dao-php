<?php
    namespace Src\App;    
    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * Session
     * Clase que se encargará de la gestión de las sesiones de los usuarios y su autentificación.
     * @implements Singelton
     */
    class Session implements Singelton {

        use GestorStrings;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * La instancia creada de la sesión la cual será reutilizada varias veces para evitar instanciar mas de una clase e iniciar mas de una sesión.
         * @private
         * @static
         * @var ?object
         * @default null
         */
        private static ?object $instancia = null;
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Cadena de texto cuyo contenido será el id de la sesión.
         * @private
         * @var ?string
         * @default null
         */
        private ?string $idSession = null;
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Array cuyo contenido serán todos los parámetros que serán de cada sesión.
         * @protected
         * @var array
         * @default []
         */
        protected array $parametrosSesion = [];

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Comprueba que se tiene o no sesión
         * @private
         * @static
         * @var bool
         * @default false
         */
        private static bool $tieneSesion = false;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * __construct
         * Método mágico que setea el atributo idSession con el id d la sesión en caso de existir una sesión activa.
         * @return void
         */
        private function __construct(){
            $this -> idSession = session_id();
            $_SESSION['isLogged'] = true;
            self::$tieneSesion = true;
        }
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * __wakeup
         * Método mágico usado para cuando se realiza unserialize
         * @return void
         */
        public function __wakeup(): void {
            if(self::hasActiveSession()){
                self::$instancia = $this;
                self::$tieneSesion = true;
            }
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @static
         * auth
         * Método que comprueba si esta iniciada la sesión y exista un id de sesión, en caso de no estar inciada o de estar inciada retorna una llamada a la redirección.
         * @return bool
         */
        public static function hasSession(): bool {
            return self::$tieneSesion;
        }
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @static
         * hasActiveSession
         * Método que comprueba si el estado de la sesión esta activa entre estar desactivada o inactiva.
         * @return bool
         */
        public static function hasActiveSession(): bool{
            return session_status() === PHP_SESSION_ACTIVE;
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @static
         * isLogged
         * Método que setea la variable estática a true o false y que comprueba si el usuario inicio o no sesión con credenciales y retona el valor obtenido.
         * @return void
         */
        public static function isLogged(): bool {
            $tieneSesionActiva = self::hasActiveSession();
            $tuvoSesion = self::huboSesion();

            return $tieneSesionActiva || $tuvoSesion;
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @static
         * setLogged
         * Método que setea una variable que determina si un usuario inicio o no sesión
         * @param  bool $value - true si el usuario inicio correctamente o false por el contrario, false por defecto.
         * @return void
         */
        public static function setLogged(bool $value = true): void {
            if(self::$instancia !== null){
                self::$tieneSesion = $value;
            }
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @static
         * getInstance
         * Método que obtiene una única instancia de la clase Session.
         * @return ?object
         */
        public static function getInstance(): ?object {
            if(self::hasActiveSession() && self::$instancia === null){
                $sesion = new Session();
                self::$instancia = $sesion;
            }
            
            return self::$instancia;
        }

        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @static
         * huboSesion
         * Método que comprobara si esta la sesión activa o exist una cookie de sesión
         * @return bool
         */
        public static function huboSesion(): bool {
            return !self::hasActiveSession() && isset($_COOKIE[session_name()]);
        }

        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @static
         * destruirSesion
         * Método encargado de destruir la sesión, desetear los valores y eliminar el archivo temporal de la sesion
         * @return void
         */
        public static function destruirSesion(): void {
            if(!self::hasActiveSession()){
                session_start();
            }

            // Por si acaso
            foreach ($_SESSION as $clave => $valor) {
                $_SESSION[$clave] = null;
                unset($_SESSION[$clave]);
            }

            self::$instancia = null;
            self::$tieneSesion = false;

            $archivoTemporalSesion = session_save_path() . '\sess_'. session_id();
            if(file_exists($archivoTemporalSesion)){
                unlink($archivoTemporalSesion);
            }

            // Unsetear los datos de la sesión.
            session_unset();
            // Destruir la sesión.
            session_destroy();
            // Abortar sesión
            session_abort();
            
            header_remove('Set-Cookie');
        }

        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * setSesionVariables
         * Método que toma una cadena como nombre de un parámetro y el valor que tomará esta variable de sesión si esta logeado el cliente.
         * @param  string $nombreParametro 
         * @param  mixed $valor
         * @return void
         */
        public function setSesionVariables(string $nombreParametro,mixed $valor): void {
            if(self::$instancia !== null){
                $parametrosSesion = $this -> parametrosSesion;
                $parametrosSesion[$nombreParametro] = $valor;
                $this -> parametrosSesion = $parametrosSesion;
            }
        }
        
        /**
         * save
         * Método que setea todos los valores de los parámetros de sesión en el array assoc $_SESSION cierra la sesión y guarda los cambios realizados.
         * @author Sxcram02 ms2d0v4@gmail.com
         * @return void
         */
        public function save(): void {
            foreach($this -> parametrosSesion as $nombreParametro => $parametro){
                if(!is_array($parametro)){
                    $esObjeto =  self::existenCoincidencias("/([iaOs]{1}:[0-9]{1,3}:\"?.*\"?)+/",$parametro);
    
                    if(!$esObjeto){
                        $parametro = self::filtrarContenido($parametro);
                    }
                }

                $_SESSION[$nombreParametro] = $parametro;
            }
        }

        /**
         * get
         * Método que obtiene todos los valores de los parámetros de sesión o solo el pasado por parametro
         * @author Sxcram02 ms2d0v4@gmail.com
         * @param string $variable
         * @return mixed
         */
        public function get(string $variable = ""): mixed {
            if(self::$instancia !== null){
                $valores = [];
                $parametros = $this -> parametrosSesion;
                foreach($parametros as $nombre => $valor){
                    $esObjeto =  self::existenCoincidencias("/([iaOs]{1}:[0-9]{1,3}:\"?.*\"?)+/",$valor);
                    
                    $valores[$nombre] = $esObjeto ? unserialize($valor) : $valor;
                }

                if(!isset($valores[$variable])){
                    return null;
                }
                
                return (!empty($variable)) ? $valores[$variable] : $valores;
            }

            return null;
        }
    }
?>