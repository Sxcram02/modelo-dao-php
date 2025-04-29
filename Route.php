<?php

namespace Src\App;

use Src\App\Request;
use Src\App\Session;
use Src\App\Logs;

/**
 * @author Sxcram02 ms2d0v4@gmail.com
 * Route
 * Objeto que controla la gestión de rutas dek servidor, tiene control sobre el método GET, POST, PUT y DELETE que junto al mod_rewrite comprueba si la ruta solicitada existe en el servidor, además también se encarga de gestionar las sesiones del servidor.
 * @todo Implementar control sobre el método OPTIONS
 * @todo Implementación de un token como seguridad.
 * @implements Singelton
 * @use GestorRutas
 */
class Route implements Singelton {

    use GestorRutas;
    
    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * Comprueba si la cabecera <head></head> ya fue incluida o no en el documento.
     * @static
     * @var bool
     * @default false
     */
    public static bool $estaSeteadaLaCabecera = false;

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * Comprueba si la cabecera <head></head> ya fue incluida o no en el documento.
     * @static
     * @var bool
     * @default false
     */
    public static bool $encontroRuta = false;

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * Almacenamiento de la instancia creada de Route la cual será reutilizada varias veces para evitar instanciar mas de una clase.
     * @private
     * @static
     * @var ?object
     * @default null
     */
    private static ?object $instancia = null;

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * Representa la sesión del usuario.
     * @private
     * @static
     * @var ?Session
     * @default null
     */
    private ?Session $sesion = null;

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * Intancia del objeto Logs que se encargará de registrar cada request
     * @private
     * @var Logs
     */
    private Logs $log;

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * Un array con todas las rutas normales.
     * @private
     * @var array
     * @default []
     */
    private array $rutasPermitidas = [];

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * Un array con todas las rutas a las que el usuario no tiene permiso de acceder sin sesión.
     * @private
     * @var array
     * @default []
     */
    private array $rutasConSesion = [];

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * Un array con todas las rutas a las que el usuario recibira una respuesta en json
     * @private
     * @var array
     * @default []
     */
    private array $rutasApi = [];

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * Un cadena con la ruta registrada por el servidor.
     * @private
     * @var ?string
     * @default null
     */
    private ?string $rutaRegistrada = null;

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * @private
     * __construct
     * Método mágico contructor del objeto Request.
     * @return void
     */
    private function __construct() {}

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * __isset
     * Método mágico que comprueba si un atributo existe y no esta vacío.
     * @param  string $atributo - un atributo de la clase.
     * @return bool
     */
    public function __isset(string $atributo): bool {
        return isset($this->$atributo) && !empty($this->$atributo);
    }

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * @static
     * getInstance
     * Método que crea una única instancia del objeto Route y la reutiliza constantemente y en caso de que previamente ya haya existido un enroutador lo selecciona.
     * @return ?object
     */
    public static function getInstance(): ?object {
        if (self::$instancia === null) {

            if(Session::huboSesion()){
                session_start();
                $isLogged = (isset($_SESSION['isLogged']) && !empty($_SESSION)) ? $_SESSION['isLogged'] : null;
                $routerAntiguo = (isset($_SESSION['_router']) && !empty($_SESSION)) ? $_SESSION['_router'] : null;

                if (isset($routerAntiguo)) {
                    $routerAntiguo = unserialize($_SESSION['_router']);
                    $routerAntiguo = !$routerAntiguo ? null : $routerAntiguo;
                }

                // Cierra la sesión sin revertir los datos seteados.
                session_write_close();
            }

            $router = $routerAntiguo ?? new Route();

            if(!isset($router -> log)){
                $router -> log = Logs::getInstance();
            }

            self::$instancia = $router;            
            if ($router -> sesion === null && Session::isLogged()) {
                self::$instancia -> sesion = Session::getInstance();
            }

        }

        return self::$instancia;
    }

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * @static
     * redirect
     * Método que recibe una cadena con la ruta a la que se redireccionará al usuario.
     * @param  string $ruta - la ruta a la que se desea redireccionar como "/registrar".
     * @return never
     */
    public static function redirect(string $ruta): never {
        header("Location: $ruta");
        die;
    }

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * get
     * Método que recibe una ruta gestionada por el método GET y un controlador
     * @param callable $controller
     * @param ?string $rutaEnv - nombre de la ruta que será gestionada.
     * @default null
     * @return Route
     */
    public function get(callable $controller,?string $rutaEnv = null): Route {
        $rutaEnv ??= $this -> rutaRegistrada;
        $rutaReq = $_SERVER['REQUEST_URI'];
        $isInitialRoute = $rutaEnv == "/" || $rutaReq == "/";

        if (!self::esMetodoValido("GET")) {
            return $this;
        }

        $tieneParametros = $isInitialRoute ? false : self::tieneParametros($rutaEnv);

        if (self::esSolicitadaRutaValida($rutaEnv)) {
            self::$encontroRuta = true;

            if (!self::$estaSeteadaLaCabecera && !$this -> esRutaApi($rutaEnv)) {
                require_once RUTA_COMPONENTES . 'head.php';
                self::$estaSeteadaLaCabecera = true;
            }

            $this -> log -> logRequest();
            $tieneParametros ? $controller(Request::getInstance()) : $controller();
        }

        return $this;
    }

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * head
     * Método que recibe una ruta gestionada por el método HEAD y un controlador
     * @param callable $controller
     * @param ?string $rutaEnv - nombre de la ruta que será gestionada.
     * @default null
     * @return Route
     */
    public function head(callable $controller,?string $rutaEnv = null): Route {
        $rutaEnv ??= $this -> rutaRegistrada;
        $rutaReq = $_SERVER['REQUEST_URI'];
        $isInitialRoute = $rutaEnv == "/" || $rutaReq == "/";

        if (!self::esMetodoValido("HEAD")) {
            return $this;
        }

        $tieneParametros = $isInitialRoute ? false : self::tieneParametros($rutaEnv);

        if (self::esSolicitadaRutaValida($rutaEnv)) {
            self::$encontroRuta = true;
            $this -> log -> logRequest();

            if ($this -> esRutaApi($rutaEnv)) {
                $_POST['_method'] = "HEAD";
                $tieneParametros ? $controller(Request::getInstance()) : $controller();
            }
        }

        return $this;
    }

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * post
     * Método que recibe una ruta gestionada por el método POST y un controlador
     * @param  callable $controller
     * @param  ?string $rutaEnv - nombre de la ruta que será gestionada.
     * @default null
     * @return Route;
     */
    public function post(callable $controller,?string $rutaEnv = null): Route {
        if (!self::esMetodoValido("POST")) {
            return $this;
        }

        $rutaEnv ??= $this -> rutaRegistrada;
        $rutaReq = $_SERVER["REQUEST_URI"];
        $isInitialRoute = $rutaEnv == "/" || $rutaReq == "/";


        if (self::esSolicitadaRutaValida($rutaEnv) && !$isInitialRoute) {
            self::$encontroRuta = true;
            $this -> log -> logRequest();
            $controller(Request::getInstance());
            die();
        }

        return $this;
    }


    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * put
     * Método que recibe una ruta gestionada por el método PUT y un controlador
     * @param  callable $controller
     * @param  ?string $rutaEnv - nombre de la ruta que será gestionada.
     * @default null
     * @return Route
     */
    public function put(callable $controller,?string $rutaEnv=null): Route {
        if (!self::esMetodoValido("PUT")) {
            return $this;
        }
        
        $rutaEnv ??= $this -> rutaRegistrada;
        $rutaReq = $_SERVER["REQUEST_URI"];
        $isInitialRoute = $rutaEnv == "/" || $rutaReq == "/";

        if (self::esSolicitadaRutaValida($rutaEnv) && !$isInitialRoute) {
            self::$encontroRuta = true;
            $this -> log -> logRequest();
            $controller(Request::getInstance());
            die();
        }

        return $this;
    }

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * delete
     * Método que recibe una ruta gestionada por el método DELETE y un controlador
     * @param  callable $controller
     * @param  ?string $rutaEnv - nombre de la ruta que será gestionada.
     * @default null
     * @return Route
     */
    public function delete(callable $controller,?string $rutaEnv=null): Route {
        if (!self::esMetodoValido("DELETE")) {
            return $this;
        }
        
        $rutaEnv ??= $this -> rutaRegistrada;
        $rutaReq = $_SERVER["REQUEST_URI"];
        $isInitialRoute = $rutaEnv == "/" || $rutaReq == "/";

        if (self::esSolicitadaRutaValida($rutaEnv) && !$isInitialRoute) {
            self::$encontroRuta = true;
            $_POST['_method'] = 'DELETE';
            $this -> log -> logRequest();
            $controller(Request::getInstance());
            die();
        }

        return $this;
    }

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * auth
     * Método encargado de setear las rutas a las que será necesaria tener una sesión.
     * @param  ?string $rutaNueva - la ruta que deberá ser autentificada
     * @default null
     * @param  ?string $role - un rol como puede ser aspirante o admin
     * @default null
     * @return Route
     */
    public function auth(?string $rutaNueva = null,null|string|array $role = null):Route {
        if(isset($rutaNueva) && !empty($rutaNueva)){
            $rutaRegistrada = $rutaNueva;
            $this -> rutaRegistrada = $rutaRegistrada;
        }else{
            $rutaRegistrada = $this -> rutaRegistrada;
        }

        $rutasConSesion  = $this -> rutasConSesion;
        $tieneRol = isset($role) && !empty($role);

        if($tieneRol && is_array($role)){
            $role = join('|',$role);
        }

        if(isset($rutaRegistrada) && !empty($rutaRegistrada) &&
            !in_array($rutaRegistrada,$rutasConSesion)
        ){
            $tieneRol ? $rutasConSesion[$role] = $rutaRegistrada : $rutasConSesion[] = $rutaRegistrada;

            $this -> rutasConSesion = $rutasConSesion;
        }

        return $this;
    }

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * setLogged
     * Método que setea la variable del usuario si inicio o no sesión con credenciales
     * @return void
     */
    public function setLogged(): void {
        if ($this -> sesion === null) {
            if(!Session::hasActiveSession()){
                session_start();
            }

            $this -> sesion = Session::getInstance();
        }
    }

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * logout
     * Método que en caso de tener la sesión iniciada, la cierra
     * @return void
     */
    public function logout(): void {
        if($this -> sesion !== null){
            $this-> sesion::destruirSesion();
            $this -> sesion = null;
            redirect('/');
        }else{
            redirect('/error/no tienes sesion alguna');
        }
    }

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * setSesionVariables
     * Setea las variables en el array $_SESSION cuyo nombre es el primer parametro y el valor el segundo.
     * @param  string $nombreParametro 
     * @param  mixed $valor
     * @return void
     */
    public function setSesionVariables(string $nombreParametro, mixed $valor): void {
        if ($this -> sesion !== null) {
            $sesion = $this -> sesion;
            $sesion->setSesionVariables($nombreParametro, $valor);
            // Almaceno en la sesion el objeto enrutador (persistencia de la sesión)
            $sesion->setSesionVariables('_router', serialize($this));
        }
    }

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * save
     * Guarda todas las variables seteadas en el método setSesionVariables
     * @return void
     */
    public function save(): void {
        if ($this -> sesion !== null) {
            $sesion = $this -> sesion;
            $sesion->save();
        }
    }

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * api
     * Método encargado de registrar una nueva ruta para la api, es decir, recibira una cabecera distinta
     * @param  string $rutaNueva
     * @default ""
     * @return Route
     */
    public function api(string $rutaNueva = ""): Route {
        if(isset($rutaNueva) && !empty($rutaNueva)){
            $rutaRegistrada = $rutaNueva;
            $this -> rutaRegistrada = $rutaRegistrada;
        }else{
            $rutaRegistrada = $this -> rutaRegistrada;
        }

        $rutasDeApi  = $this -> rutasApi;
        if(isset($rutaRegistrada) && !empty($rutaRegistrada) && !$this -> esRutaApi($rutaRegistrada)){
            $rutasDeApi[] = $rutaRegistrada;
            $this -> rutasApi = $rutasDeApi;
        }

        return $this;
    }

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * getLog
     * Método que retornara el objeto Loh
     * @return Logs
     */
    public function log(): Logs{
        return $this -> log;
    }

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * @private
     * tienePermisoParaAcceder
     * Comprueba que la sesión de un usuario este seteada y activada.
     * @return bool
     */
    private function tienePermisoParaAcceder(): bool {
        $sesion = $this -> sesion;

        if(!isset($sesion) || !$sesion instanceof Session){
            return false;
        }

        if(!$sesion::hasSession() || !$sesion::hasActiveSession()){
            return false;
        }
        return true;
    }
    
    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * @private
     * registrarRuta
     * Método encargado de setear las rutas permitidas por medio de alguna de las funciones get(), post o put() en la variable estática comprobando que esta exista en el array o no.
     * @param  mixed $rutaNueva - puede ser una ruta como "/usuario/{_user}" o "/publicacion/ver"
     * @return void
     */
    private function registrarRuta(string $rutaNueva): void {
        $rutasExistentes = $this -> rutasPermitidas;
        if (empty($rutasExistentes)) {
            $rutasExistentes[] = $rutaNueva;
            $this -> rutaRegistrada = $rutaNueva;
        } else {
            $existeLaRuta = false;

            foreach ($rutasExistentes as $ruta) {

                if(self::sonMismaRuta($rutaNueva,$ruta,true)){
                    $existeLaRuta = true;
                }
            }

            if (!$existeLaRuta) {
                $rutasExistentes[] = $rutaNueva;
                $this -> rutaRegistrada = $rutaNueva;
            }
        }

        $this -> rutasPermitidas = $rutasExistentes;
    }

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * @private
     * esRutaExistente
     * Método el cual unifica accede a la variable privada rutasPermitidas y comprueba que la ruta recibida por parametro sea existente en el array.
     * @param  string $rutaReq - ruta solicitada por el usuario.
     * @return bool
     */
    private function esRutaExistente(string $rutaReq,bool $estaRegistrada = false): bool {
        $rutasPermitidas = $this -> rutasPermitidas;
        $esEstrcito = !$estaRegistrada ? true : false;

        foreach ($rutasPermitidas as $ruta) {
            $sonLaMisma = self::sonMismaRuta( $rutaReq,$ruta, $esEstrcito);

            if ($sonLaMisma) {
                return true;
            }
        }

        return false;
    }

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * @private
     * esRutaConSesion
     * Método que comprueba que la ruta pasada por parámetro coincide con alguna ruta que deba incluir sesion.
     * @param  string $rutaEnv - generalmente la ruta de entorno del servidor
     * @return bool
     */
    private function esRutaConSesion(string $rutaEnv): bool {
        $rutasConSesion = $this -> rutasConSesion;
        if(empty($rutasConSesion)){
            return false;
        }

        foreach ($rutasConSesion as $ruta) {
            if (self::sonMismaRuta( $rutaEnv,$ruta,true)) {
                return true;
            }
        }

        return false;
    }

    /**´
     * @author Sxcram02 ms2d0v4@gmail.com
     * @private
     * esRutaConRol
     * Método que comprueba que la ruta pasada por parámetro coincide con alguna ruta que deba incluir sesion.
     * @param  string $rutaEnv - generalmente la ruta de entorno del servidor
     * @return bool
     */
    private function esRutaConRol(string $rutaEnv): bool {
        $rutasConSesion = $this -> rutasConSesion;
        if(empty($rutasConSesion)){
            return false;
        }

        foreach ($rutasConSesion as $rol => $ruta) {
            if (self::sonMismaRuta( $rutaEnv,$ruta,true)) {
                return gettype($rol) != "integer" ? true : false;
            }
        }

        return false;
    }

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * @private
     * esRolValido
     * Método que comprueba que la ruta pasada por parámetro coincide con alguna ruta que deba incluir un rol.
     * @param  string $rutaEnv - generalmente la ruta de entorno del servidor
     * @return bool
     */
    private function esRolValido(string $rol,string $rutaEnv): bool {
        $rutasConSesion = $this -> rutasConSesion;
        if(empty($rutasConSesion)){
            return false;
        }

        $rolNecesario = "";

        foreach ($rutasConSesion as $rolImpuesto => $ruta) {
            if (self::sonMismaRuta( $rutaEnv,$ruta,true)) {
                $rolNecesario = $rolImpuesto;
                $rolNecesario = separarString($rolNecesario,'/\|/');
            }
        }

        if(is_string($rolNecesario)){
            return $rolNecesario === $rol;
        }

        if(is_array($rolNecesario)){
            return in_array($rol,$rolNecesario);
        }

        return false;

    }

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * @private
     * esRutaApi
     * Método que comprueba que la ruta pasada por parámetro coincide con alguna ruta que sea gestionada por una api.
     * @param  string $rutaEnv - generalmente la ruta de entorno del servidor
     * @return bool
     */
    private function esRutaApi(string $rutaEnv): bool {
        $rutasApi = $this -> rutasApi;
        if(empty($rutasApi)){
            return false;
        }

        foreach ($rutasApi as $ruta) {
            if (self::sonMismaRuta( $rutaEnv,$ruta,true)) {
                return true;
            }
        }

        return false;
    }
    
    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * @private
     * esSolicitadaRutaValida
     * Método encargado de registrar, filtrar por dirctorios /home,/usuario y por sesiones (solicitada o no)  por acceso (permitido o no) retorna un true si la REQUES_URI cumple con todas estas condiciones
     * @param  mixed $rutaEnv - ruta que será registrada para el servidor
     * @return bool
     */
    private function esSolicitadaRutaValida($rutaEnv = null): bool {
        $sonLaMismaRuta = false;
        $tieneSesion = false;
        $tieneAccesoPermitido = true;

        $rutaEnv ??= $this -> rutaRegistrada;
        $rutaReq = $_SERVER["REQUEST_URI"];

        $esRutaDeArchivo = existenCoincidencias("/\.[pdf|jpeg|png|php|html|js|jpg|gif|txt|xhtml|yml|xml]$/",$rutaReq);

        if ($esRutaDeArchivo) {
            return false;
        }

        $isInitialRoute = $rutaEnv == "/" || $rutaReq == "/";

        if ($rutaEnv != "/" && !$this->esRutaExistente($rutaEnv)) {
            $this->registrarRuta($rutaEnv);
        }

        if(!$isInitialRoute && $this -> esRutaConSesion($rutaEnv)){
            $tieneSesion = true;
            $tieneRol = $this -> esRutaConRol($rutaEnv);
            $tieneAccesoPermitido = false;
        }

        $sonLaMismaRuta = $isInitialRoute ? $rutaEnv == $rutaReq : self::sonMismaRuta($rutaReq,$rutaEnv);
        

        if($tieneSesion){
            $tieneAccesoPermitido = $this -> tienePermisoParaAcceder();
        }

        if($tieneAccesoPermitido && $tieneRol){
            $rol = $this -> sesion -> get('_typeRol');
            $tieneAccesoPermitido = $this -> esRolValido($rol,$rutaEnv);
        }

        if($sonLaMismaRuta && !$tieneAccesoPermitido){
            header('HTTP/1.1 404 Not found');
            header('Location: /');
            die;
        }

        if($tieneAccesoPermitido){
            $tieneAccesoPermitido = $isInitialRoute ? true : $this->esRutaExistente($rutaReq,true);
        }

        if($sonLaMismaRuta && !$tieneAccesoPermitido){
            header('HTTP/1.1 404 Not found');
            header('Location: /');
            die;
        }

        return $sonLaMismaRuta && $tieneAccesoPermitido;
    }
    
    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * @private
     * @static
     * esMetodoValido
     * Método encargado de a partir de expresiones regulares, HTTP_METHOD_ALLOWED put|delete|get|post !php solo soporta POST & GET si solicita una medida mas
     * @param  string $metodo - cadena con el metodo
     * @return bool
     */
    private static function esMetodoValido(string $metodo): bool {
        $metodoActual = $_SERVER["REQUEST_METHOD"];

        if(!existenCoincidencias("/put|post|get|head|delete/i",$metodoActual)){
            return false;
        }

        if(existenCoincidencias("/post|put/i",$metodoActual)){
            $datosCabecera = file_get_contents('php://input');
            $datosPorCabecera = json_decode($datosCabecera != false ? $datosCabecera : "{}", true);

            if(is_array($datosPorCabecera) && count($datosPorCabecera) > 0){
                foreach($datosPorCabecera as $clave => $dato){
                    $_POST[$clave] = $dato;
                }
            }

            file_put_contents('php://input',"");
            $metodoActual = $_POST['_method'] ?? "";

            if(!Filtro::validate([$metodoActual => "notnull|hasValue"])){
                return false;
            }

            if(!existenCoincidencias("/put|delete|post/i",$metodoActual)){
                return false;
            }
        }

        if(!existenCoincidencias("/$metodo/i",$metodoActual)){
            return false;
        }

        return true;
    }

    
    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * cabeceraRespuesta
     * Método encargado de mandar una cabecera concreta y un mensaje en formato json si procede
     * @param  ?int $codigo
     * @default null
     * @param  mixed $mensaje
     * @return never
     */
    public static function cabeceraRespuesta(?int $codigo = null,mixed $mensaje): never {
        $mensajeCodigo = match($codigo){
            200 => "Ok",
            302 => "Forbidden",
            400 => "Bad request",
            406 => "Not Acceptable",
            409 => "Conflict",
            500 => "Internal Server Error",
            default => "Not found"
        };

        $mensajeError = "HTTP/1.1 $codigo $mensajeCodigo";
        $cuerpo = match(gettype($mensaje)){
            "string" => json_encode(['mensaje' => $mensaje],JSON_PRETTY_PRINT),
            "array" => json_encode($mensaje,JSON_PRETTY_PRINT),
            default => null
        };

        if(isset($codigo) && is_int($codigo)){
            header($mensajeError);
        }

        if(isset($cuerpo)){
            header("Content-Type: application/json");
            echo $cuerpo;
        }
        die;
    }
}

?>