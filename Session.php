<?php

namespace Src\App;

/**
 * Session
 * Clase que se encargará de la gestión de los  valores de las sesiones de los usuarios y su autentificación.
 * @author Sxcram02 <ms2d0v4@gmail.com>
 * @implements Singelton
 * @use GestorStrings
 */
class Session implements Singelton
{

    use GestorStrings;

    /**
     * La instancia creada de la sesión la cual será reutilizada varias veces para evitar instanciar mas de una clase e iniciar mas de una sesión.
     * @author Sxcram02 <ms2d0v4@gmail.com>
     * @private
     * @static
     * @var ?object
     * @default null
     */
    private static ?object $instancia = null;

    /**
     * Cadena de texto cuyo contenido será el id de la sesión.
     * @author Sxcram02 <ms2d0v4@gmail.com>
     * @private
     * @var ?string
     * @default null
     */
    private ?string $idSession = null;

    /**
     * Array con todos los parámetros del array $_SESSION
     * @author Sxcram02 <ms2d0v4@gmail.com>
     * @protected
     * @var array
     * @default []
     */
    protected array $parametrosSesion = [];

    /**
     * Booleano que comprueba que se tiene o no sesión
     * @author Sxcram02 <ms2d0v4@gmail.com>
     * @private
     * @static
     * @var bool
     * @default false
     */
    private static bool $tieneSesion = false;

    /**
     * __construct
     * Método mágico que setea el atributo idSession con el id de la sesión
     * @return void
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private function __construct()
    {
        $this->idSession = session_id();
        $_SESSION['isLogged'] = true;
        self::$tieneSesion = true;
    }

    /**
     * __wakeup
     * Método mágico usado para cuando se realiza unserialize que la instancia y la sesion no cambie si se inicio correctamente.
     * @return void
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function __wakeup(): void
    {
        if (self::hasActiveSession()) {
            self::$instancia = $this;
            self::$tieneSesion = true;
        }
    }

    /**
     * hasSession
     * Método que comprueba si el objeto tiene sesion
     * @return bool
     * @author Sxcram02 <ms2d0v4@gmail.com>
     * @static
     */
    public static function hasSession(): bool
    {
        return self::$tieneSesion;
    }

    /**
     * hasActiveSession
     * Método que comprueba si el estado de la sesión esta activa 
     * @return bool
     * @author Sxcram02 <ms2d0v4@gmail.com>
     * @static
     */
    public static function hasActiveSession(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * isLogged
     * Método que retorna un booleano si tiene sesion o la tuvo
     * @return bool
     * @author Sxcram02 <ms2d0v4@gmail.com>
     * @static
     */
    public static function isLogged(): bool
    {
        $tieneSesionActiva = self::hasActiveSession();
        $tuvoSesion = self::huboSesion();

        return $tieneSesionActiva || $tuvoSesion;
    }

    /**
     * setLogged
     * Método que setea si un usuario inicio o no sesión si existe instancia del objeto Session
     * @param  bool $value - true si el usuario inicio o false por el contrario.
     * @return void
     * @author Sxcram02 <ms2d0v4@gmail.com>
     * @static
     */
    public static function setLogged(bool $value = true): void
    {
        if (self::$instancia !== null) {
            self::$tieneSesion = $value;
        }
    }

    /**
     * getInstance
     * Método que obtiene una única instancia de la clase Session.
     * @return ?object
     * @author Sxcram02 <ms2d0v4@gmail.com>
     * @static
     */
    public static function getInstance(): ?object {
        if (self::hasActiveSession() && self::$instancia === null) {
            $sesion = new Session();
            self::$instancia = $sesion;
        }

        return self::$instancia;
    }


    /**
     * huboSesion
     * Método que comprobara si esta la sesión activa o existe una cookie de sesión
     * @return bool
     * @author Sxcram02 <ms2d0v4@gmail.com>
     * @static
     */
    public static function huboSesion(): bool
    {
        return !self::hasActiveSession() && isset($_COOKIE[session_name()]);
    }


    /**
     * destruirSesion
     * Método encargado de destruir la sesión, desetear los valores y eliminar el archivo temporal de la sesion
     * @return void
     * @author Sxcram02 <ms2d0v4@gmail.com>
     * @static
     */
    public static function destruirSesion(): void
    {
        if (!self::hasActiveSession()) {
            session_start();
        }

        // Por si acaso
        foreach ($_SESSION as $clave => $valor) {
            $_SESSION[$clave] = null;
            unset($_SESSION[$clave]);
        }

        self::$instancia = null;
        self::$tieneSesion = false;

        $archivoTemporalSesion = session_save_path() . '\sess_' . session_id();
        if (file_exists($archivoTemporalSesion)) {
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
     * setSesionVariables
     * Método que toma una cadena como nombre de un parámetro y el valor que tomará esta variable de sesión si esta logeado el cliente.
     * @param  string $nombreParametro 
     * @param  mixed $valor
     * @return void
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function setSesionVariables(string $nombreParametro, mixed $valor): void
    {
        if (self::$instancia !== null) {
            $parametrosSesion = $this->parametrosSesion;
            $parametrosSesion[$nombreParametro] = $valor;
            $this->parametrosSesion = $parametrosSesion;
        }
    }

    /**
     * save
     * Método que setea todos los valores de los parámetros de sesión en el array assoc $_SESSION
     * @return void
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function save(): void
    {
        foreach ($this->parametrosSesion as $nombreParametro => $parametro) {
            if (!is_array($parametro)) {
                $esObjeto =  self::existenCoincidencias("/([iaOs]{1}:[0-9]{1,3}:\"?.*\"?)+/", $parametro);

                if (!$esObjeto) {
                    $parametro = self::filtrarContenido($parametro);
                }
            }

            $_SESSION[$nombreParametro] = $parametro;
        }
    }

    /**
     * get
     * Método que obtiene todos los valores de los parámetros de sesión o solo el pasado por parametro
     * @param string $variable
     * @return mixed
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function get(string $variable = ""): mixed
    {
        if (self::$instancia !== null) {
            $valores = [];
            $parametros = $this->parametrosSesion;
            foreach ($parametros as $nombre => $valor) {
                $esObjeto =  self::existenCoincidencias("/([iaOs]{1}:[0-9]{1,3}:\"?.*\"?)+/", $valor);

                $valores[$nombre] = $esObjeto ? unserialize($valor) : $valor;
            }

            if (!isset($valores[$variable])) {
                return null;
            }

            return (!empty($variable)) ? $valores[$variable] : $valores;
        }

        return null;
    }
}
