<?php

namespace Src\App;

use PDOStatement;

/**
 * Database
 * Objeto usado para la conexión de la base de datos con una única instancia que abrirá y cerrará la conexión en tanto se haga la consulta y devuelva un resultado falle o retorne valor
 * @final
 * @implements Singelton
 * @author Sxcram02 <ms2d0v4@gmail.com>
 */
final class Database implements Singelton
{
    /**
     * Cadena de texto donde se incluye el driver, el nombre de la BBDD y el host donde esta alojada y la colección de caracteres.
     * @private
     * @readonly
     * @var string
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private readonly string $dsn;
    /**
     * Cadena de texto con el usuario de la base de datos.
     * @private
     * @readonly
     * @var string
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private readonly string $user;
    /**
     * Cadena de texto con la contraseña del usuario de la base de datos.
     * @private
     * @readonly
     * @var string
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private readonly string $pass;
    /**
     * Instancia del objeto Database para la reutilización de la instancia.
     * @protected
     * @static
     * @var ?Database
     * @default null
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    protected static ?Database $conexion = null;
    /**
     * Instancia del objeto de PDO usado para la conexión con la base de datos.
     * @private
     * @var ?\PDO
     * @default null
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private ?\PDO $pdo = null;

    /**
     * Cadena con la última consulta realizada.
     * @private
     * @var ?Consulta
     * @default null
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private ?Consulta $consulta = null;

    /**
     * Último resultado obtenido de una consulta select o show.
     * @private
     * @var ?PDOStatement
     * @default null
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private ?PDOStatement $resultado = null;

    /**
     * __construct
     * Método constructor del DSN con el driver usado "mysql" el nombre de la base de datos, el host, usuario y contraseña.
     * Solo puede existir una instancia de este objeto.
     * @return void
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private function __construct($env)
    {
        $this->dsn = "mysql:dbname=" . $env['DB_NAME'] . ';host=' . $env['DB_HOSTNAME'] . ";charset=utf8mb4";
        $this->user = $env['DB_USER'];
        $this->pass = $env['DB_PASS'];
    }

    /**
     * __get
     * Método mágico que obtiene un atributo a partir de un parámetro del objeto.
     * @param  string $atributo
     * @return mixed
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function __get(string $atributo): mixed
    {
        return $this->$atributo;
    }

    /**
     * __set
     * Método mágica que setea el valor de un atributo pasado como parametro al objeto
     * @param  string $atributo
     * @param  mixed $propiedad
     * @return void
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function __set(string $atributo, mixed $propiedad): void
    {
        $this->$atributo = $propiedad;
    }

    /**
     * @static
     * getInstance
     * Método que intancia el objeto Database y realiza la conexión con la misma
     * @static
     * @return Database
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public static function getInstance(): Database
    {
        if (self::$conexion === null) {
            $env = require_once 'env.php';
            $datosDB = new Database($env);
            $datosDB->connect();
        }

        return self::$conexion;
    }

    /**
     * connect
     * Método que establece la conexión con la base de datos para su uso.
     * @return void
     * @throws \Exception Error en la conexión
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private function connect(): void
    {
        $datosDB = $this;
        $errorInfo = [];
        try {
            $conexionDB = new \PDO(
                // Atributos privados
                $datosDB->__get(atributo: 'dsn'),
                $datosDB->__get(atributo: 'user'),
                $datosDB->__get(atributo: 'pass'),
                [
                    // Silenciar los erros del objeto PDOException.
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_SILENT,
                    // Asegurar que el fetch se con clave númerica y associativa.
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_BOTH,
                    //Desactivamos el autocommit
                    \PDO::ATTR_AUTOCOMMIT => false,
                    \PDO::ATTR_PERSISTENT => false
                ]
            );

            $conexionDB->exec("SET NAMES 'utf8mb4'");
            $datosDB->__set(atributo: 'pdo', propiedad: $conexionDB);
            self::$conexion = $datosDB;
            // Esto es por testeo
            $errorInfo = $conexionDB->errorInfo();
        } catch (\Exception $error) {
            throw new \Exception(
                message: "Error en la conexión de la base de datos, Error PDO: $errorInfo[2]",
                // Esto es por testeo
                previous: new \Exception($errorInfo[2])
            );
        }
    }

    /**
     * query
     * Método que recibe una cadena con una consulta y un callback para manejar el error en caso de que la consulta falle.
     * @param  string $query
     * @param  ?callable $error
     * @return mixed
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function query(string $query, ?callable $error = null): mixed
    {
        $errorFuncion = $error ?? function () {
            return false;
        };

        $resultado = false;
        $pdoStatment = $this->obtenerPdo($query);
        $clausula = substr($query,0,6);
        $pdo = self::$conexion->pdo;
        $pdo->beginTransaction();
        try {
            if (isset($pdoStatment)) {
                $ejecucion = $pdoStatment->execute();
            }

            if ($ejecucion) {
                $ultimoId = $pdo->lastInsertId();
                $pdo->commit();
                $this->resultado = $pdoStatment;

                $resultado = match ($clausula) {
                    "INSERT" => $ultimoId === "0" ? true : (int) $ultimoId,
                    "UPDATE" => true,
                    "DELETE" => true,
                    default => $pdoStatment
                };
            } else {
                $pdo->rollBack();
                $errorPDO = $pdoStatment->errorInfo();
                throw new \Exception("Error en la ejecución de la consulta: $query --- $errorPDO[2]", 1);
            }
        } catch (\Exception $excepcion) {
            $resultado = $errorFuncion($excepcion);
        } finally {
            $this->pdo = null;
            return $resultado;
        }
    }

    /**
     * obtenerPdo
     * Método encargado de obtener el PDOStatment o false, recibe una consulta y prepara la consulta para luego retornarla
     * @param  string $query
     * @return ?PDOStatement
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private function obtenerPdo(string $query): ?PDOStatement
    {
        $pdoStatment = null;
        $db = self::$conexion;
        $pdo = $db->pdo;

        if (!isset($pdo)) {
            $db->connect();
            $pdo = $db->pdo;
        }

        if (!$this->existeConexion($db, $pdo)) {
            return null;
        }

        $consultaAnterior = $this->consulta;
        $consultaAnterior = $consultaAnterior->consulta ?? "";
        $consulta = new Consulta($query);

        if ($consultaAnterior != $query) {
            $this->consulta = $consulta;
            $pdoStatment = $pdo->prepare(query: $consulta->consultaPreparada);

            if ($consulta->tieneParametros()) {
                foreach ($consulta->parametrosPreparados as $parametro => $detalles) {
                    $tipo = $detalles['tipo'];
                    $valor = $detalles['valor'];
                    $valor = match ($tipo) {
                        \PDO::PARAM_NULL => null,
                        \PDO::PARAM_INT => (int) $valor,
                        \PDO::PARAM_BOOL => (bool) $valor,
                        default => (string) $valor
                    };

                    $pdoStatment->bindValue($parametro, $valor, $tipo);
                }
            }
        } else {
            if (isset($this->resultado)) {
                $pdoStatment = $this->resultado;
            }
        }

        return $pdoStatment;
    }

    /**
     * @private
     * existeConexion
     * Método que comprueba que exista instancia de Database y del objeto PDO 
     * @param Database $db
     * @param \PDO $pdo 
     * @return bool
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private function existeConexion(Database $db, \PDO $pdo): bool
    {
        return $db !== null || $pdo !== null && $pdo instanceof \PDO;
    }
}
?>