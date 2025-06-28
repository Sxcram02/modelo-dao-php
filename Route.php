<?php

namespace Src\App;

use Exception;
use Src\App\Request;
use Src\App\Session;
use Src\App\Logs;

/**
 * Route
 * Objeto que controla la gestión de rutas dek servidor, tiene control sobre el método GET, POST, PUT y DELETE que junto al mod_rewrite comprueba si la ruta solicitada existe en el servidor, además también se encarga de gestionar las sesiones del servidor.
 * @author Sxcram02 <ms2d0v4@gmail.com>
 * @implements Singelton
 * @use GestorRutas
 * @use Openssl
 * @todo Implementación de un token como seguridad.
 * @todo Implementar control sobre el método OPTIONS
 */
class Route implements Singelton {
    use GestorRutas;
    use Openssl;

    /**
     * Comprueba si la cabecera <head></head> ya fue incluida o no en el documento.
     * @var bool
     * @default false
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public static bool $estaSeteadaLaCabecera = false;

    /**
     * Comprueba si la cabecera <head></head> ya fue incluida o no en el documento.
     * @var bool
     * @default false
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public static bool $encontroRuta = false;

    /**
     * Almacenamiento de la instancia creada de Route la cual será reutilizada varias veces para evitar instanciar mas de una clase.
     * @private
     * @var ?object
     * @default null
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private static ?object $instancia = null;

    /**
     * Representa la sesión del usuario.
     * @var ?Session
     * @default null
     * @private
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private ?Session $sesion = null;

    /**
     * Intancia del objeto Logs que se encargará de registrar cada request
     * @var Logs
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private Logs $log;

    /**
     * Cadena con el controlador a usar si es necesario
     * @var string
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private string $controller;

    /**
     * Un array con todas las rutas normales.
     * @var array
     * @default []
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private array $rutasPermitidas = [];

    /**
     * Un cadena con la ruta registrada por el servidor.
     * @var ?string
     * @default null
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private ?string $rutaRegistrada = null;

    /**
     * __construct
     * Método mágico contructor del objeto Request.
     * @return void
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private function __construct() {}

    /**
     * __isset
     * Método mágico que comprueba si un atributo existe y no esta vacío.
     * @param  string $atributo - un atributo de la clase.
     * @return bool
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function __isset(string $atributo): bool {
        return isset($this->$atributo) && !empty($this->$atributo);
    }

    /**
     * getInstance
     * Método que crea una única instancia del objeto Route y la reutiliza constantemente y en caso de que previamente ya haya existido un enroutador lo selecciona.
     * @return ?object
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public static function getInstance(): ?object {
        if (self::$instancia === null) {

            if (Session::huboSesion()) {
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

            if (!isset($router->log)) {
                $router->log = Logs::getInstance();
            }

            self::$instancia = $router;
            if ($router->sesion === null && Session::isLogged()) {
                self::$instancia->sesion = Session::getInstance();
            }
        }

        return self::$instancia;
    }

    /**
     * redirect
     * Método que recibe una cadena con la ruta a la que se redireccionará al usuario.
     * @param  string $ruta - la ruta a la que se desea redireccionar como "/registrar".
     * @return never
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public static function redirect(string $ruta): never {
        header("Location: $ruta");
        die;
    }

    /**
     * auth
     * Método encargado de setear las rutas a las que será necesaria tener una sesión.
     * @param  ?string $rutaNueva - la ruta que deberá ser autentificada
     * @default null
     * @param  mixed $role - un rol como puede ser aspirante o admin
     * @default null
     * @return Route
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function auth(?string $rutaNueva = null, mixed $role = null): Route {
        $tieneRol = isset($role) && !empty($role);
        $ruta = $this -> getRutaRegistrada($rutaNueva);
        $rutas = $this -> rutasPermitidas;

        if ($rutas[$ruta]['tieneSesion'] === false) {
            $rutas[$ruta]['tieneSesion'] = true;

            if($tieneRol){
                $rutas[$ruta]['tieneRol'] = true;
                $rutas[$ruta]['rol'] = $role;
            }
            $this->rutasPermitidas = $rutas;
        }

        $this->rutaRegistrada = $ruta;
        return $this;
    }

    /**
     * api
     * Método encargado de registrar una nueva ruta para la api, es decir, recibira una cabecera distinta
     * @param  string $rutaNueva
     * @default ""
     * @return Route
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function api(string $rutaNueva = ""): Route {
        $ruta = $this -> getRutaRegistrada($rutaNueva);
        $rutas  = $this->rutasPermitidas;

        if ($rutas[$ruta]['tieneHeaderHtml'] === true) {
            $rutas[$ruta]['tieneHeaderHtml'] = false;
            $this->rutasPermitidas = $rutas;
        }

        $this->rutaRegistrada = $ruta;
        return $this;
    }

    /**
     * encrypted
     * Método encargado de registrar una nueva ruta para encriptar, es decir, debe contener una cadena con el patron de la codificación de base 64
     * @param  string $rutaNueva
     * @default ""
     * @return Route
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function encrypted(string $rutaNueva = ""): Route {
        $ruta = $this -> getRutaRegistrada($rutaNueva);
        $rutas = $this->rutasPermitidas;

        if ($rutas[$ruta]['tieneEncriptacion'] === false) {
            $rutas[$ruta]['tieneEncriptacion'] = true;
            $this->rutasPermitidas = $rutas;
        }
        
        $this->rutaRegistrada = $ruta;
        return $this;
    }
    
    /**
     * controller
     * Método encargado de asociar a cada ruta un controlador para su uso y descarte
     * @param  string $controller - el nombre de la clase Src\Controllers\UsuarioController
     * @param  callable $callable - función que agrupará rutas con dicho controlador
     * @return void
     * @todo controlar las rutas que tienen controlador asignado y las que no
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function controller(string $controller, callable $callable) :void {
        $this -> controller = $controller;
        $callable();
    }

    /**
     * get
     * Método que recibe una ruta gestionada por el método GET y un controlador
     * @param string|callable $controller
     * @param ?string $rutaEnv - nombre de la ruta que será gestionada.
     * @default null
     * @return Route
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function get(string|callable $controller, ?string $rutaEnv = null): Route {
        $rutaReq = $_SERVER['REQUEST_URI'];
        $isInitialRoute = $rutaEnv == "/" || $rutaReq == "/";

        if (!$this->esMetodoValido("GET")) {
            return $this;
        }

        $rutas = $this->rutasPermitidas;
        $rutaEnv = $this->getRutaRegistrada($rutaEnv);
        $tieneParametros = $isInitialRoute ? false : self::tieneParametros($rutaEnv);

        if ($this -> esSolicitadaRutaValida($rutaEnv)) {
            self::$encontroRuta = true;

            if (!self::$estaSeteadaLaCabecera && $rutas[$rutaEnv]['tieneHeaderHtml'] === true) {
                require_once RUTA_COMPONENTES . 'head.php';
                self::$estaSeteadaLaCabecera = true;
            }

            $this->log->logRequest();
            $metodo = $controller;
            if (is_string($controller) && !empty($controller)) {
                $controller = $this->controller;

                if (!isset($controller) || empty($controller)) {
                    return $this;
                }

                if (!method_exists($controller, $metodo)) {
                    return $this;
                }

                $tieneParametros ? $controller::$metodo(Request::getInstance()) : $controller::$metodo();
            }else{
                $tieneParametros ? $controller(Request::getInstance()) : $controller();
            }
        }

        return $this;
    }

    /**
     * head
     * Método que recibe una ruta gestionada por el método HEAD y un controlador
     * @param callable $controller
     * @param ?string $rutaEnv - nombre de la ruta que será gestionada.
     * @default null
     * @return Route
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function head(callable $controller, ?string $rutaEnv = null): Route {
        $rutaReq = $_SERVER['REQUEST_URI'];
        $isInitialRoute = $rutaEnv == "/" || $rutaReq == "/";
        
        if (!$this->esMetodoValido("HEAD")) {
            return $this;
        }

        $rutaEnv = $this -> getRutaRegistrada($rutaEnv);
        $tieneParametros = $isInitialRoute ? false : self::tieneParametros($rutaEnv);
        $rutas = $this -> rutasPermitidas;

        if ($this -> esSolicitadaRutaValida($rutaEnv)) {
            self::$encontroRuta = true;
            $this->log->logRequest();

            if (!$rutas[$rutaEnv]['tieneHeaderHtml']) {
                $_POST['_method'] = "HEAD";
                $tieneParametros ? $controller(Request::getInstance()) : $controller();
            }
        }

        return $this;
    }

    /**
     * post
     * Método que recibe una ruta gestionada por el método POST y un controlador
     * @param  callable $controller
     * @param  ?string $rutaEnv - nombre de la ruta que será gestionada.
     * @default null
     * @return Route;
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function post(string|callable $controller, ?string $rutaEnv = null): Route {
        if (!$this->esMetodoValido("POST")) {
            return $this;
        }

        $rutaEnv = $this -> getRutaRegistrada($rutaEnv);
        $rutaReq = $_SERVER["REQUEST_URI"];
        $isInitialRoute = $rutaEnv == "/" || $rutaReq == "/";

        if ($this -> esSolicitadaRutaValida($rutaEnv) && !$isInitialRoute) {
            self::$encontroRuta = true;
            $this->log->logRequest();

            if (is_string($controller) && !empty($controller)) {
                $metodo = $controller;
                $controller = $this->controller;

                if (!isset($controller) || empty($controller)) {
                    return $this;
                }

                if (!method_exists($controller, $metodo)) {
                    return $this;
                }

                $controller::$metodo(Request::getInstance());
            }else{
                $controller(Request::getInstance());
            }
            die();
        }

        return $this;
    }

    /**
     * put
     * Método que recibe una ruta gestionada por el método PUT y un controlador
     * @param  callable $controller
     * @param  ?string $rutaEnv - nombre de la ruta que será gestionada.
     * @default null
     * @return Route
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function put(callable $controller, ?string $rutaEnv = null): Route {
        if (!$this->esMetodoValido("PUT")) {
            return $this;
        }

        $rutaEnv = $this -> getRutaRegistrada($rutaEnv);
        $rutaReq = $_SERVER["REQUEST_URI"];
        $isInitialRoute = $rutaEnv == "/" || $rutaReq == "/";

        if ($this -> esSolicitadaRutaValida($rutaEnv) && !$isInitialRoute) {
            self::$encontroRuta = true;
            $this->log->logRequest();
            $controller(Request::getInstance());
            die();
        }

        return $this;
    }

    /**
     * delete
     * Método que recibe una ruta gestionada por el método DELETE y un controlador
     * @param  callable $controller
     * @param  ?string $rutaEnv - nombre de la ruta que será gestionada.
     * @default null
     * @return Route
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function delete(callable $controller, ?string $rutaEnv = null): Route {
        if (!$this->esMetodoValido("DELETE")) {
            return $this;
        }

        $rutaEnv = $this -> getRutaRegistrada($rutaEnv);
        $rutaReq = $_SERVER["REQUEST_URI"];
        $isInitialRoute = $rutaEnv == "/" || $rutaReq == "/";

        if ($this -> esSolicitadaRutaValida($rutaEnv) && !$isInitialRoute) {
            self::$encontroRuta = true;
            $_POST['_method'] = 'DELETE';
            $this->log->logRequest();
            $controller(Request::getInstance());
            die();
        }

        return $this;
    }

    /**
     * setLogged
     * Método que setea la variable del usuario si inicio o no sesión con credenciales
     * @return void
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function setLogged(): void {
        if ($this->sesion === null) {
            if (!Session::hasActiveSession()) {
                session_start();
            }

            $this->sesion = Session::getInstance();
        }
    }

    /**
     * logout
     * Método que en caso de tener la sesión iniciada, la cierra
     * @return void
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function logout(): void {
        if ($this->sesion !== null) {
            $this->sesion::destruirSesion();
            $this->sesion = null;
            redirect('/');
        } else {
            redirect('/error/no tienes sesion alguna');
        }
    }

    /**
     * setSesionVariables
     * Setea las variables en el array $_SESSION cuyo nombre es el primer parametro y el valor el segundo.
     * @param  string $nombreParametro 
     * @param  mixed $valor
     * @return void
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function setSesionVariables(string $nombreParametro, mixed $valor): void {
        if ($this->sesion !== null) {
            $sesion = $this->sesion;
            $sesion->setSesionVariables($nombreParametro, $valor);
            // Almaceno en la sesion el objeto enrutador (persistencia de la sesión)
            $sesion->setSesionVariables('_router', serialize($this));
        }
    }

    /**
     * save
     * Guarda todas las variables seteadas en el método setSesionVariables
     * @return void
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function save(): void {
        if ($this->sesion !== null) {
            $sesion = $this->sesion;
            $sesion->save();
        }
    }

    /**
     * getLog
     * Método que retornara el objeto Loh
     * @return Logs
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function log(): Logs {
        return $this->log;
    }

    /**
     * tienePermisoParaAcceder
     * Comprueba que la sesión de un usuario este seteada y activada.
     * @return bool
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private function tienePermisoParaAcceder(): bool {
        $sesion = $this->sesion;

        if (!isset($sesion) || !$sesion instanceof Session) {
            return false;
        }

        if (!$sesion::hasSession() || !$sesion::hasActiveSession()) {
            return false;
        }
        return true;
    }

    /**
     * registrarRuta
     * Método encargado de añadir una ruta pasada por parámetro al objeto Route para que se pueda acceder a ella.
     * @param string $rutaNueva - cadena con la ruta que deseas registrar
     * @return void
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private function registrarRuta(string $rutaNueva): void {
        $rutasExistentes = $this->rutasPermitidas;
        $tieneControlador = isset($this->controller) && !empty($this -> controller);
        if (empty($rutasExistentes)) {
            $rutasExistentes[$rutaNueva] = [
                'tieneSesion' => false,
                'tieneRol' => false,
                'tieneHeaderHtml' => true,
                'tieneEncriptacion' => false,
                'tieneControllador' => $tieneControlador,
                'rol' => "",
                'controller' =>  $tieneControlador ? $this -> controller : ""
            ];

            $this->rutaRegistrada = $rutaNueva;
        } else {
            $existeLaRuta = false;

            foreach ($rutasExistentes as $ruta => $conf) {
                if (self::sonMismaRuta($rutaNueva, $ruta, true)) {
                    $existeLaRuta = true;
                }
            }

            if (!$existeLaRuta) {
                $rutasExistentes[$rutaNueva] = [
                    'tieneSesion' => false,
                    'tieneRol' => false,
                    'tieneHeaderHtml' => true,
                    'tieneControllador' => $tieneControlador,
                    'tieneEncriptacion' => false,
                    'rol' => "",
                    'controller' => $tieneControlador ? $this -> controller : ""
                ];
                $this->rutaRegistrada = $rutaNueva;
            }
        }

        $this->rutasPermitidas = $rutasExistentes;
    }

    /**
     * esRutaExistente
     * Método que comprueba que la ruta pasada por parametro esta registrada en el objeto Route
     * @param  string $rutaReq - ruta solicitada por el usuario.
     * @return bool
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private function esRutaExistente(string $rutaReq, bool $estaRegistrada = false): bool {
        $rutasPermitidas = $this->rutasPermitidas;
        $esEstrcito = !$estaRegistrada ? true : false;

        foreach ($rutasPermitidas as $ruta => $conf) {
            $sonLaMisma = self::sonMismaRuta($rutaReq, $ruta, $esEstrcito);

            if ($sonLaMisma) {
                return true;
            }
        }

        return false;
    }

    /**
     * esSolicitadaRutaValida
     * Método encargado de registrar, filtrar por dirctorios /home,/usuario y por sesiones (solicitada o no)  por acceso (permitido o no) retorna un true si la REQUES_URI cumple con todas estas condiciones
     * @param  mixed $rutaEnv - ruta que será registrada para el servidor
     * @return bool
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private function esSolicitadaRutaValida($rutaEnv = null): bool {
        $rutas = $this -> rutasPermitidas;
        $sonLaMismaRuta = false;
        $tieneAccesoPermitido = true;

        $rutaEnv ??= $this->rutaRegistrada;
        $rutaReq = $_SERVER["REQUEST_URI"];

        $esRutaDeArchivo = self::existenCoincidencias("/\.[pdf|jpeg|png|php|html|js|jpg|gif|txt|xhtml|yml|xml]$/", $rutaReq);

        if ($esRutaDeArchivo) {
            return false;
        }

        $isInitialRoute = $rutaEnv == "/" || $rutaReq == "/";
        $tieneSesion = $isInitialRoute ? false : $rutas[$rutaEnv]['tieneSesion'];
        $tieneRol = $isInitialRoute ? false : $rutas[$rutaEnv]['tieneRol'];
        $tieneEncriptacion = $isInitialRoute ? false : $rutas[$rutaEnv]['tieneEncriptacion'];

        if ($tieneSesion || $tieneEncriptacion || $tieneRol) {
            $tieneAccesoPermitido = false;
        }

        $sonLaMismaRuta = $isInitialRoute ? $rutaEnv == $rutaReq : self::sonMismaRuta($rutaReq, $rutaEnv);

        // Tiene o no sesión
        if ($sonLaMismaRuta && $tieneSesion) {
            $tieneAccesoPermitido = $this->tienePermisoParaAcceder();
        }

        // Tiene sesión pero no rol adecuado
        if ($sonLaMismaRuta && $tieneAccesoPermitido && $tieneRol) {
            $rol = $this->sesion->get('_typeRol');
            $tieneAccesoPermitido = !isset($rol) ? false : $this->esRolValido($rol, $rutaEnv);
        }

        if ($sonLaMismaRuta && !$tieneAccesoPermitido) {
            redirect('/');
        }

        // Esa ruta esta registrada en el array
        if ($tieneAccesoPermitido) {
            $tieneAccesoPermitido = $isInitialRoute ? true : $this->esRutaExistente($rutaReq, true);
        }

        if ($sonLaMismaRuta && !$tieneAccesoPermitido) {
            redirect('/');
        }

        // Esa ruta debe estar encriptada
        if ($tieneAccesoPermitido && $tieneEncriptacion) {
            $tieneAccesoPermitido = self::estaCodificado(rawurldecode($rutaReq));
        }

        if ($sonLaMismaRuta && !$tieneAccesoPermitido) {
            redirect('/perfil');
        }

        return $sonLaMismaRuta && $tieneAccesoPermitido;
    }

    /**
     * esMetodoValido
     * Método encargado de a partir de expresiones regulares, HTTP_METHOD_ALLOWED put|delete|get|post !php solo soporta POST & GET si solicita una medida mas
     * @param  string $metodo - cadena con el metodo
     * @return bool
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private function esMetodoValido(string $metodo): bool {
        $metodoActual = $_SERVER["REQUEST_METHOD"];

        if (!self::existenCoincidencias("/put|post|get|head|delete/i", $metodoActual)) {
            return false;
        }

        if (self::existenCoincidencias("/post|put/i", $metodoActual)) {
            $datosCabecera = file_get_contents('php://input');
            $datosPorCabecera = json_decode($datosCabecera != false ? $datosCabecera : "{}", true);

            if (is_array($datosPorCabecera) && count($datosPorCabecera) > 0) {
                foreach ($datosPorCabecera as $clave => $dato) {
                    $_POST[$clave] = $dato;
                }
            }

            file_put_contents('php://input', "");
            $metodoActual = $_POST['_method'] ?? "";

            if (!Filtro::validate([$metodoActual => "notnull|hasValue"])) {
                return false;
            }

            if (!self::existenCoincidencias("/put|delete|post/i", $metodoActual)) {
                return false;
            }
        }

        if (!self::existenCoincidencias("/$metodo/i", $metodoActual)) {
            return false;
        }

        return true;
    }

    /**
     * esRolValido
     * Método que comprueba que la ruta pasada por parámetro coincide con alguna ruta que deba incluir un rol.
     * @param  string $rutaEnv - generalmente la ruta de entorno del servidor
     * @return bool
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private function esRolValido(string $rol, string $rutaEnv): bool {
        $rutas = $this->rutasPermitidas;
        if (empty($rutas)) {
            return false;
        }

        $rolNecesario = "";

        foreach ($rutas as $ruta => $conf) {
            if (self::sonMismaRuta($rutaEnv, $ruta, true)) {
                $rolNecesario = $conf['rol'];
                $rolNecesario = !is_array($rolNecesario) ? self::separarString($rolNecesario, '/\|/') : $rolNecesario;
            }
        }

        if (is_string($rolNecesario)) {
            return $rolNecesario === $rol;
        }

        if (is_array($rolNecesario)) {
            return in_array($rol, $rolNecesario);
        }

        return false;
    }

    /**
     * getRutaRegistrada
     * Método que recibe una ruta por parámetro si esta vacía buscará la ruta anterior ya registrada en el router y la retornará, si no, registra la nueva ruta y la retornará
     * @param ?string $ruta - ruta añadida al enroutador
     * @default ""
     * @throws \Exception
     * @return string
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private function getRutaRegistrada(?string $ruta = ""): string {
        $ruta = (isset($ruta) && !empty($ruta)) ? $ruta : $this->rutaRegistrada;

        if(empty($ruta)){
            throw new Exception('No se puede registrar una ruta vacía');
        }

        if ($ruta != "/" && !$this->esRutaExistente($ruta)) {
            $this->registrarRuta($ruta);
        }

        return $ruta;

    }

    /**
     * cabeceraRespuesta
     * Método encargado de mandar una cabecera concreta y un mensaje en formato json si procede
     * @param  ?int $codigo
     * @default null
     * @param  mixed $mensaje
     * @return never
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public static function cabeceraRespuesta(?int $codigo = null, mixed $mensaje): never {
        $mensajeCodigo = match ($codigo) {
            200 => "Ok",
            302 => "Forbidden",
            400 => "Bad request",
            406 => "Not Acceptable",
            409 => "Conflict",
            500 => "Internal Server Error",
            default => "Not found"
        };

        $mensajeError = "HTTP/1.1 $codigo $mensajeCodigo";
        $cuerpo = match (gettype($mensaje)) {
            "string" => json_encode(['mensaje' => $mensaje], JSON_PRETTY_PRINT),
            "array" => json_encode($mensaje, JSON_PRETTY_PRINT),
            default => null
        };

        if (isset($codigo) && is_int($codigo)) {
            header($mensajeError);
        }

        if (isset($cuerpo)) {
            header("Content-Type: application/json");
            echo $cuerpo;
        }
        die;
    }
}
?>