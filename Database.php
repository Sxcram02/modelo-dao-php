<?php
    namespace Src\App;
    use PDOStatement;

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * @final
     * Database
     * Objeto usado para la conexión de la base de datos con una única instancia que abrirá y cerrará la conexión en tanto se haga la consulta y devuelva un resultado falle o retorne valor
     * @implements Singelton
    */
    final class Database implements Singelton {
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Cadena de texto donde se incluye el driver, el nombre de la BBDD y el host donde esta alojada y la colección de caracteres.
         * @private
         * @readonly
         * @var string
         */
        private readonly string $dsn;
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Cadena de texto con el usuario de la base de datos.
         * @private
         * @readonly
         * @var string
         */
        private readonly string $user;
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Cadena de texto con la contraseña del usuario de la base de datos.
         * @private
         * @readonly
         * @var string
         */
        private readonly string $pass;
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Instancia del objeto Database para la reutilización de la instancia.
         * @protected
         * @static
         * @var ?Database
         * @default null
         */
        protected static ?Database $conexion = null;
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Instancia del objeto de PDO usado para la conexión con la base de datos.
         * @private
         * @var ?\PDO
         * @default null
         */
        private ?\PDO $pdo = null;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Cadena con la última consulta realizada.
         * @private
         * @var ?Consulta
         * @default null
         */
        private ?Consulta $consulta = null;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Último resultado obtenido de una consulta select o show.
         * @private
         * @var ?PDOStatement
         * @default null
         */
        private ?PDOStatement $resultado = null;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * __construct
         * Método constructor del DSN con el driver usado "mysql" el nombre de la base de datos, el host, usuario y contraseña.
         * Solo puede existir una instancia de este objeto.
         * @return void
         */
        private function __construct($env) {
            $this->dsn = "mysql:dbname=" . $env['DB_NAME'] . ';host=' . $env['DB_HOSTNAME'].";charset=utf8mb4";
            $this->user = $env['DB_USER'];
            $this->pass = $env['DB_PASS'];
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * __get
         * Método mágico que obtiene un atributo a partir de un parámetro del objeto.
         * @param  string $atributo
         * @return mixed
         */
        public function __get(string $atributo): mixed {
            return $this->$atributo;
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * __set
         * Método mágica que setea el valor de un atributo pasado como parametro al objeto
         * @param  string $atributo
         * @param  mixed $propiedad
         * @return void
         */
        public function __set(string $atributo, mixed $propiedad): void {
            $this->$atributo = $propiedad;
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @static
         * getInstance
         * Método que intancia el objeto Database y realiza la conexión con la misma
         * @static
         * @return Database
         */
        public static function getInstance(): Database {
            if (self::$conexion === null) {
                $env = require_once 'env.php';
                $datosDB = new Database($env);
                $datosDB -> connect();
            }

            return self::$conexion;
        }
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * connect
         * Método que establece la conexión con la base de datos para su uso.
         * @return void
         * @throws \Exception Error en la conexión
         */
        private function connect(): void {
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

                $conexionDB ->exec("SET NAMES 'utf8mb4'");


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
         * @author Sxcram02 ms2d0v4@gmail.com
         * query
         * Método que recibe una cadena con una consulta y un callback para manejar el error en caso de que la consulta falle.
         * @param  string $query
         * @param  ?callable $error
         * @return mixed
         */
        public function query(string $query,?callable $error = null): mixed {
            $errorFuncion = $error ?? function() {
                return false;
            };

            $resultado = false;
            $pdoStatment = $this -> obtenerPdo($query);
            $pdo = self::$conexion -> pdo;
            $pdo -> beginTransaction();
            try {
                if(isset($pdoStatment)){
                    $ejecucion = $pdoStatment->execute();
                }

                if ($ejecucion) {
                    $ultimoId = $pdo -> lastInsertId();
                    $pdo -> commit();
                    $this -> resultado = $pdoStatment;
                    
                    $resultado = match (substr($query, 0,   6)) {
                        "INSERT" => $ultimoId === "0" ? true : (int) $ultimoId,
                        "UPDATE" => true,
                        "DELETE" => true,
                        default => $pdoStatment
                    };
                    
                }else{
                    $pdo -> rollBack();
                    $errorPDO = $pdoStatment->errorInfo();
                    throw new \Exception("Error en la ejecución de la consulta: $query --- $errorPDO[2]",1);
                }
            } catch (\Exception $excepcion) {
                $resultado = $errorFuncion($excepcion);
            } finally {
                $this -> pdo = null;
                return $resultado;
            }
        }
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * obtenerPdo
         * Método encargado de obtener el PDOStatment o false, recibe una consulta y prepara la consulta para luego retornarla
         * @param  string $query
         * @return ?PDOStatement
         */
        private function obtenerPdo(string $query):?PDOStatement {
            $pdoStatment = null;
            $db = self::$conexion;
            $pdo = $db-> pdo;

            if(!isset($pdo)){
                $db -> connect();
                $pdo = $db -> pdo;
            }

            if(!$this -> existeConexion($db, $pdo)){
                return null;
            }

            $consultaAnterior = $this -> consulta;
            $consultaAnterior = $consultaAnterior -> consulta ?? "";
            $consulta = new Consulta($query);

            if($consultaAnterior != $query){
                $this -> consulta = $consulta;
                $pdoStatment = $pdo->prepare(query: $consulta -> consultaPreparada);
                
                if ($consulta -> tieneParametros()) {
                    foreach ($consulta -> parametrosPreparados as $parametro => $detalles) {
                        $tipo = $detalles['tipo'];
                        $valor = $detalles['valor'];
                        $valor = match($tipo){
                            \PDO::PARAM_NULL => null,
                            \PDO::PARAM_INT => (int) $valor,
                            \PDO::PARAM_BOOL => (bool) $valor,
                            default => (string) $valor
                        };

                        $pdoStatment->bindValue($parametro,$valor,$tipo);
                    }
                }
            }else{
                if(isset($this -> resultado)){
                    $pdoStatment = $this -> resultado;
                }
            }

            return $pdoStatment;
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * existeConexion
         * Método que comprueba que exista instancia de Database y del objeto PDO 
         * @param Database $db
         * @param \PDO $pdo 
         * @return bool
         */
        private function existeConexion(Database $db,\PDO $pdo): bool{
            return $db !== null || $pdo !== null && $pdo instanceof \PDO;
        }
    }
?>
