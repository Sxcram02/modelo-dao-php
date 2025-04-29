<?php
    namespace Src\App;
    use Exception;

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * Request
     * Objeto que se encargará de guardar todos los parametros request desencriptarlos y encriptarlos si se da el caso, y setear los valores a null para evitar su acceso en los arrays $_REQUEST,... y crear una única instancia con los datos para despues destruirla.
     * @implements Singelton
     * @use Openssl
     */
    class Request implements Singelton {
        use Openssl;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Almacenamineto de la única instancia del objeto Request.
         * @private
         * @static
         * @var ?Request
         * @default null
         */
        private static ?Request $instancia = null;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * __construct
         * Método mágico que recibe el array $_REQUEST, $_POST o $_GET y crea los atributos de forma dinámica.
         * @param array $request 
         * @return void
         */
        private function __construct(array $request){
            foreach ($request as $clave => $valor) {
                $this -> $clave = $valor;
            }
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @static
         * getInstance
         * Método que setea cada valor de $_REQUEST los filtra y un caso de no existir instancia usa esos parametros para instanciar el objeto Request.
         * @return ?object
         */
        public static function getInstance(): ?object {
            $requestCopy = [];

            if(isset($_POST['_method'])){
                $requestCopy['_method'] = $_POST['_method'];
            }
            
            if(in_array($_SERVER["REQUEST_METHOD"],["POST",'PUT'])){
                foreach ($_POST as $clave => $valor) {
                    if(!in_array($clave,['clave-publica']) && !is_array($valor)){
                        $requestCopy[$clave] = self::estaCodificado($valor) ? self::decrypt($valor) : $valor;
                    }
        
                    $_POST[$clave] = null;
                }
        
                if(!empty($_FILES)){
                    foreach ($_FILES as $clave => $valor) {
                        $requestCopy[$clave] = $valor;
                    }
                }
            }

            if($_SERVER["REQUEST_METHOD"] == "GET" || existenCoincidencias("/put|delete|head|post/i",$requestCopy['_method'])){
                foreach ($_GET as $clave => $valor) {
                    if(!in_array($clave,['clave-publica']) && !is_array($valor)){
                        $valor = self::estaCodificado($valor) ? self::decrypt($valor) : $valor;
                        $requestCopy[$clave] =  $valor;
                    }

                    $_GET[$clave] = null;
                }
            }

            if(self::$instancia !== null){
                self::$instancia -> __destroy();
            }

            $requestObject = new Request($requestCopy);
            self::$instancia = $requestObject;

            return self::$instancia;
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * __get
         * Método mágico usado para obtener los atributos de clase.
         * @param string $atributo
         * @return mixed
         */
        public function __get(string $atributo): mixed {
            return $this->$atributo;
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * __set
         * Método mágico usado para setear los atributos de la clase con una propiedsd dada.
         * @param string $atributo 
         * @param mixed $propiedad 
         * @return void
         */
        public function __set(string $atributo, mixed $propiedad): void {
            $this->$atributo = $propiedad;
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * __destroy
         * Método usado para destruir la instancia del objeto.
         * @return void
         */
        public function __destroy(): void {
            $atributos = get_object_vars($this);
            foreach ($atributos as $atributo) {
                $this -> $atributo = null;
            }
        }
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * array
         * Método que retorna un único array assoc con el nombre de la variable y su valor
         * @return array
         */
        public function array():array {
            $atributos = get_object_vars($this);
            $array = [];
            foreach ($atributos as $atributo => $valor) {
                if(array_search($atributo,['_method']) === false){
                    $array[$atributo] = $valor;
                }
            }

            return $array;
        }
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @static
         * method
         * Método que retorna un input con el método HTTP especificado si esta permitido en el formulario.
         * @param string $nombreMetodo - nombre del método HTTP usado en un formulario generalmente.
         * @return mixed
         */
        public static function method(string $nombreMetodo):mixed {
            if(!existenCoincidencias("/put|post|get|delete/i",$nombreMetodo)){
                return false;
            } 
            
            return "<input type='hidden' name='_method' value='$nombreMetodo' />";
        }
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @static
         * encrypt
         * Método encargado de encriptar y codificar los datos pasados por paramteros
         * @param  string|array $datos
         * @return void
         */
        public static function encrypt(string|array $datos) {
            if(!isset(self::$clavePublica)){
                self::getPrivateKey();
            }
            return $datos;

            $datos = self::encript($datos);
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @static
         * decrypt
         * Método encargado de descodificar y desencriptar los datos pasados por paramteros
         * @param  mixed $datos
         * @return void
         */
        public static function decrypt(mixed $datos): mixed{
            if(!isset(self::$clavePublica)){
                self::getPrivateKey();
            }

            return $datos;
            $datos = self::decript($datos);
        }
    }
?>
