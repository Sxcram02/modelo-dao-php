<?php

namespace Src\App;

use Src\App\Coleccion;
use Src\App\Database;

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * @abstract
     * Modelo
     * Clase abstracta que será usada por los modelos para obtener, crear y modificar los registros de la base de datos con los controladores.
     */
    abstract class Modelo {
        use GestorStrings;
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Cadena de texto con la indicas el nombre de la tabla si el nombre de la clase no corresponde con esta.
         * @protected
         * @static
         * @var ?string
         * @default null
         */
        protected ?string $tabla = null;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Cadena de texto con el que indicas la abreviación de la tabla.
         * @protected
         * @static
         * @var ?string
         * @default null
         */
        protected ?string $abreviacionTabla = null;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Instancia de la base de datos que realizará las consultas.
         * @private
         * @static
         * @var Database
         */
        private static ?Database $database = null;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Cadena con la consulta realizada usa para la formación dinámica de esta misma.
         * @private
         * @static
         * @var ?string
         * @default null
         */
        private ?string $consulta = null;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Último id insertado en caso de estar setado
         * @var int
         */
        public int $ultimoIdInsertado;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Un array con todas las columnas que deseas setear en el objeto
         * @protected
         * @var ?array
         * @default null
         */
        protected ?array $columnas = null;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * __construct
         *  Método mágico constructor de la plantilla Modelo que setea la variable self::$tabla tomando el nombre de clase instanciada y formateandolo para la consulta SQL, además setea la conexión a la BBDD de forma privada y con una única instancia.
         * @return void
         * @throws \Exception Tabla no encontrada
         * @throws \Exception Conexión con la base de datos nula
         */
        private function __construct() {
            // Formateo el nombre de la clase Usuarios -> usuarios
            $tabla = strtolower(substr($this::class, 11, strlen($this::class)));

            if (!self:: esCadenaTextoValida(cadena: $tabla)) {
                throw new \Exception(message: 'No se encuentra la tabla en la BBDD');
            }

            if(self::$database === null){
                self::$database = Database::getInstance();
            }
            
            if (!isset(self::$database)) {
                throw new \Exception(message: 'Algo fallo en la conexión');
            }

            if ($tabla != "modelo") {
                if(!isset($this -> tabla)){
                    $this -> tabla = $tabla;
                    $this -> abreviacionTabla ??= null;
                    $this->setColumnas(tabla: $tabla);
                }
            }
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @static
         * createObjects
         * Método que toma un array de registros e instancia una clase con las columnas de los registros recibidos.
         * @todo Solucionar la creación dinámica de atributos. (deprecated php-8.3)
         * @param array $registro - array asoc
         * @return object|false
         */
        public static function createModel(array $registro): object|false {
            $modelo = false;
            if (static::class != "Src\Models\Modelo") {
                $modelo = new static();
                if(!isset($modelo)){
                    return false;
                }
                
                foreach ($registro as $atributo => $propiedad) {
                    $modelo -> $atributo = $propiedad;
                }

                unset($modelo -> tabla);
                unset($modelo -> abreviacionTabla);
                unset($modelo -> columnas);
            }
            
            return $modelo;
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @static
         * create
         * Método qu recibe un array assoc cuyas claves son las columnas de la tabla y su valor retorna false si falla o un objeto de la tabla con los valores.
         * @todo función con arrays númericos.
         * 
         * @param  array $columnas - un array de las columnas necesarias para crear el modelo de la base de datos.
         * @return object|false
         */
        public static function create(array $columnas): object|false {
            $object = new static();
            $tabla = $object -> tabla;

            if (estaArrayVacio(array: $columnas) || !esArrayAssociativo(array: $columnas)) {
                return false;
            }

            $claves = array_keys($columnas);
            $cols = implode(", ", $claves);
            $values = implode(", ", $columnas);
            $consulta = "INSERT INTO `$tabla` ($cols) VALUES ($values)";

            $result = self::$database -> query(query: $consulta);

            if ($result == false) {
                return $result;
            }

            foreach ($columnas as $clave => $valor) {    
                if (!isset($object -> ultimoIdInsertado)) {
                    $object-> ultimoIdInsertado = $result;
                }

                $object -> $clave = $valor;
            }
            
            $object -> abreviacionTabla = null;
            $object -> columnas = null;
            $object -> tabla = null;

            return $object;
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @static
         * update
         * Método que recibe las columnas y el id del registro a actualizar
         * @param  array $columnas
         * @default []
         * @param  int|string|array $id
         * @return bool
         */
        public static function update(array $columnas = [], int|string|array $id): bool {
            $object = new static();
            $tabla = $object -> tabla;

            if (estaArrayVacio($columnas) || empty($id)) {
                return false;
            }
            
            if (!esArrayAssociativo(array: $columnas)) {
                return false;
            }
            
            $claves = array_keys($columnas);
            $indiceMax = count($columnas) - 1;

            $consulta = "UPDATE $tabla SET ";
            foreach ($claves as $indice => $clave) {
                $valor = $columnas[$clave];
                $consulta .= $indice < $indiceMax ? "$clave = $valor, " : "$clave = $valor ";
            }

            if(!esArrayAssociativo($id)){
                $idTabla = self::obtenerIdTabla();
                if(is_array($idTabla)){
                    $idTabla = $idTabla[0];
                }
                $consulta .= "WHERE $idTabla = $id";
                unset($object);
            }else{
                $esPrimeraClave = true;
                foreach($id as $nombre => $valor){
                    if($esPrimeraClave){
                        $consulta .= "WHERE $nombre = $valor";
                        $esPrimeraClave = false;
                    }else{
                        $consulta .= " AND $nombre = $valor";
                    }
                }
            }
            
            unset($object);
            return self::$database->query( $consulta);
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * delete
         * Método que recibe el id del registro a eliminar retorna un true o false
         * @param  int|string|array $id - el id o ids del registro
         * @return bool
         */
        public static function delete(int|string|array $id): bool {
            if (empty($id)) {
                throw new \Exception('No se puede actualizar un registro vacío');
            }

            $object = new static();
            $tabla = $object -> tabla;

            $consulta = "DELETE FROM $tabla";
            $idTabla = self::obtenerIdTabla();
            $numeroIds = is_array($idTabla) ? count($idTabla) : 1;
            
            if(is_array($idTabla) && $numeroIds == 1){
                $idTabla = $idTabla[0];
            }

            if(is_string($id) || is_int($id)){
                $consulta .= " WHERE $idTabla = $id";
            }

            if(is_array($id)){
                if($numeroIds != count($id)){
                    return false;
                }

                $primeraClave = true;
                if(esArrayAssociativo($id)){
                    $idTabla = array_keys($id);
                    $id = array_values($id);
                }

                foreach($idTabla as $indice => $nombreColumna){
                    if($primeraClave){
                        $consulta .= " WHERE $nombreColumna = " . $id[$indice];
                        $primeraClave = false;
                    }else{
                        $consulta .= " AND $nombreColumna = " . $id[$indice];
                    }
                }
            }

            $result = self::$database->query( $consulta);

            unset($object);
            return $result;
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @static
         * select
         * Método que toma un array ['columna1','columna2',...] o un string con formato columna1,columna2,...se le asignará un alias con las 3 primeras letras de la tabla a cada columna, si encuentra resultados devolverá un objeto con todos los resultados.
         * @param  string|array $columnas - un string con formato columna1,columna2 o un array con las columnas.
         * @return object|false
         */
        public static function select(string|array $columnas) : object|false {
            $object = new static();
            $tabla = $object -> tabla;

            $abreviacionTabla = (!isset($object -> abreviacionTabla)) ? substr($tabla, 0, 3) : $object -> abreviacionTabla;
            
            // Error a un SELECT vacío
            if (!is_string($columnas) && estaArrayVacio(array: $columnas)) {
                return false;
            }
            
            if (is_string($columnas)){
                $selecionaTodo = self::existenCoincidencias("#\*#",$columnas);
                if(!$selecionaTodo && self::existenCoincidencias("/,:;-/", $columnas)) {
                    $datos = self::separarString($columnas);
                }
            }else{
                $selecionaTodo = in_array('*',$columnas);
                $datos = $columnas;
            }
            
            if (!$selecionaTodo) {
                if (!isset($datos) || !is_array($datos)) {
                    $datos = "$abreviacionTabla.$columnas";
                } else {
                    // Tiene varias columnas o solo una.
                    if(count($datos) > 1) {
                        foreach ($datos as $indice => $dato) {
                            $datos[$indice] = "$abreviacionTabla.$dato";
                        }
                    }
                    
                    $datos = (count($datos) > 1) ? implode(', ', $datos) : "$abreviacionTabla.$datos[0]";
                }
            }
            
            $datos = $selecionaTodo ? "*" : $datos;
            $consulta = "SELECT $datos FROM $tabla $abreviacionTabla";
            if (empty($consulta)) {
                $object -> consulta = $consulta;
            }else{
                if($object -> consulta != $consulta){
                    $object -> consulta = $consulta;
                }
            }

            return $object;
        }
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @static
         * exists
         * Método encargado que a partir de un array asociativo retorna si ese registro existe o no 
         * @param  array $datos
         * @return bool
         */
        public static function exists(array $datos): bool {

            $modeloActual = new static();
            $columnas = $modeloActual -> columnas;
            unset($modeloActual);

            $modeloActual = static::class;

            if(esArrayAssociativo($datos)){
                $columnas = array_keys($datos);
                $datos = array_values($datos);

                $modelo = $modeloActual::select('*') -> where($columnas,'=',$datos) -> get();
    
                $noExiste = $modelo == false;
            }

            return  !isset($noExiste) || $noExiste ? false : true;
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @static
         * findById
         * Método encargado de obtener un modelo en base a su id o ids
         * @param  mixed $id
         * @return object|false
         */
        public static function findById(mixed $id): object|false {
            $object = new static();
            $ids = $object::obtenerIdTabla();

            // Un id por tabla
            if(!is_array($ids) && !is_array($id)){   
                $modelo = $object::select('*') -> where($ids,'=',$id) -> get();

                return $modelo == false ? false : $modelo;
            }

            // Varios ids
            if(is_array($ids) && !is_array($id)){
                return false;
            }

            $modelo = $object::select('*') -> where($ids,'=',$id) -> get();
            
            return $modelo == false ? false : $modelo;
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * join
         * Método que necesita tener una consulta previa, la cual es recogida de forma estática y obtiene la tabla de esta misma consulta, recibe como parámetro la tabla a la que va a ser unida y como segundo parametro un array de claves foraneas ['tabl.clave1','tab.clave2',...] o una cadena con formato 'tabk.clave1,tab.clave2,...' y unira a la consulta anterior y realizará la nueva union retornando un objeto con todos los resultados.
         * @todo Seleccionar la abrevicion personalizada (no recomendable).
         *
         * @public
         * @param string $tabla - cadena con la tabla a la que seá unida la consulta
         * @param string|array $claveForaneas - una cadena con formato clave1,clave2 o un array no asoc de estas.
         * @return ?object
         */
        public function join(string $tablaUnida, string|array $clavesForaneas): ?object {
            $tablaOriginal = $this -> formatTabla();
            $separador = self::buscarString(' ',$tablaOriginal);
            $abreviacionTablaOriginal = substr($tablaOriginal, $separador + 1, 3);

            $abreviaturas = [];

            $abreviacionTablaUnida = substr($tablaUnida, 0, 3);

            if ($abreviacionTablaOriginal === $abreviacionTablaUnida) {
                $abreviacionTablaUnida .= "1";
            }

            // tabla1 tb1
            if (self::existenCoincidencias("/\s[a-z]{3}/", $tablaUnida, $abreviaturas)) {
                $abreviacionTablaUnida = trim($abreviaturas[0]);
            } else {
                $tablaUnida =  "$tablaUnida $abreviacionTablaUnida";
            }


            if (empty($clavesForaneas)) {
                throw new \Exception(message: 'Debes poner al menos una clave foránea');
            }

            if (!is_array($clavesForaneas)) {
                $clavesForaneas = self::separarString($clavesForaneas);
            }

            if (estaArrayVacio(array: $clavesForaneas)) {
                throw new \Exception(message: 'El formato de las claves foraneas no es correcto');
            }

            $clavesForaneas[0] = "$abreviacionTablaOriginal.$clavesForaneas[0]";
            $clavesForaneas[1] = "$abreviacionTablaUnida.$clavesForaneas[1]";
            $consulta = $this -> consulta;

            // Omitimos la clausula where
            if (str_contains($consulta, 'WHERE')) {
                $indiceWhere = strpos($consulta,'WHERE');
                $consulta = substr( $consulta, 0, $indiceWhere + 1);
            }

            $consulta .= " JOIN $tablaUnida ON $clavesForaneas[0] = $clavesForaneas[1]";

            if (empty($consulta)) {
                $this -> consulta = $consulta;
            }else{
                if($this -> consulta != $consulta){
                    $this -> consulta = $consulta;
                }
            }
            return $this;
        }
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * where
         * Método que necesita de una consulta previa para funcionar, toma tre parámetros las columnas que van a ser evaluadas, uno o mas operadores para cada condición, el último se aplica a todas las demás
         * @param  string|array $columnas
         * @param  string|array $operador
         * @param  mixed $condicion
         * @return object
         */
        public function where(string|array $columnas, string|array $operador, mixed $condicion): object {
            foreach(func_get_args() as $parametro){
                if (is_string($parametro) && !self::estaCifrada($parametro)) {
                    $parametro = self::separarString($parametro);
                }
            }

            $consulta = $this -> consulta;
            if (str_contains($consulta, 'WHERE')) {
                $indiceWhere = strpos($consulta,'WHERE');
                $consulta = substr($consulta,0,$indiceWhere);
            }

            if (is_array($columnas) && is_array($condicion)) {
                $noSonIguales = count($columnas) != count($condicion);
                if ($noSonIguales) {
                    throw new \Exception('Las columnas buscadas no coincide con las condiciones');
                }

                $esPrimera = true;
                $query = "";
                if (!esArrayAssociativo($columnas)) {
                    foreach ($columnas as $indice => $columna) {
                        if(is_array($operador)){
                            $ultimoOperador = $operador[count($operador) - 1];

                            // Si añades 5 columnas con 2 operadores y 5 condiciones se aplicara el ultimo operador a todas las condiciones
                            $operadorUsado =  (count($condicion) == count($operador)) ? $operador[$indice]
                            : $operadorUsado = ($indice > count($operador) - 1) ? $ultimoOperador : $operador[$indice];
                        }else{
                            $operadorUsado = $operador;
                        }

                        if($operadorUsado == "LIKE"){
                            $condicion[$indice] = "%$condicion[$indice]%";
                        }

                        $query .= $esPrimera ? " WHERE $columna $operadorUsado $condicion[$indice]" : " AND $columna $operadorUsado $condicion[$indice]";

                        if ($esPrimera) {
                            $esPrimera = false;
                        }
                    }
                }
            } else {
                if($operador == "LIKE"){
                    $condicion = "%$condicion%";
                }
                
                $query = " WHERE $columnas $operador $condicion";
            }
            
            $consulta .= $query;

            if(!empty($consulta)){
                if($this -> consulta != $consulta){
                    $this -> consulta = $consulta;
                }
            }else{
                $this -> consulta = $consulta;
            }

            return $this;
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * json
         * Método encargado de retornar un json con las columnas de datos que no sea el id o la fehca de creación y actualización del registro
         * @return bool|string
         */
        public function json():bool|string {
            $columnas = $this -> columnas;
            $datos = [];
            foreach($columnas as $columna){
                if(!self::existenCoincidencias("/_at$|^\bid.*\b/i",$columna)){
                    $datos[$columna] = $this -> $columna;
                }
            }

            return json_encode($datos,JSON_PRETTY_PRINT);
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * array
         * Método que retorna un array con todos los atributos de la clase instanciada
         * @return array
         */
        public function array():array {
            $atributos = [];
            $atributosDeLaClase = get_object_vars($this);
            foreach($atributosDeLaClase as $atributo => $columna){
                if(isset($this -> $atributo)){
                    $atributos[$atributo] = $this -> $atributo;
                }
            }

            return $atributos;
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * get
         * Método encargado de ejecutar la consulta formada con la instancia
         * @return bool|object
         */
        public function get(): bool|object {
            return $this -> execute();
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * isColeccion
         * Método que comprueba si la clase instanciada es una Coleccion 
         * @return bool
         */
        public function isColeccion(): bool {
            return $this instanceof Coleccion;
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * getCreatedDate
         * Método encargado de generar una fecha con formato dinamico en segundos, minutos, horas, dias, meses o años
         * @return Fecha
         */
        public function getCreatedDate(): Fecha{
            return new Fecha($this -> __get('created_at'));
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * count
         * Método que realiza una consulta para comprobar la cuenta de registros en la base de datos
         * @return int|false
         */
        public function count(): int|false {
            $tabla = $this -> tabla;
            $consultaBase = "SELECT COUNT(*) as registros FROM $tabla " . $this -> abreviacionTabla ?? substr($tabla,0,3);
            $consulta = (!empty($this -> consulta)) ? $this -> consulta : $consultaBase;
            $tieneWhere = str_contains($consulta,'WHERE');
            $tieneJoin = str_contains($consulta,'JOIN');

            if($tieneWhere){
                $indice = strpos($consulta,'WHERE');
                $clausulaWhere =  substr($consulta,$indice);
                $consulta = preg_replace("/\b$clausulaWhere\b/i","",$consulta);
            }
            
            if($tieneJoin){
                $indice = strpos($consulta,'JOIN');
                $clausulaJoin = substr($consulta,$indice);
            }
            
            $consulta = $tieneJoin ? "$consultaBase $clausulaJoin" : $consultaBase;
            
            if($tieneWhere){
                $consulta = (!$tieneJoin) ? "$consulta $clausulaWhere" : "$consulta $clausulaWhere";
            }

            $query = self::$database -> query($consulta);
            if($query) {
                $resultado = $query -> fetchAll();
                return $resultado[0]['registros'];
            }

            return false;
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * limit
         * Método encargado de limitar la consulta formulada previamente
         * @param  int $limite
         * @default 10
         * @return object|false
         */
        public function limit(int $limite = 10): object|false {
            $consulta = $this -> consulta;

            if(empty($consulta) || $limite > 50){
                return false;
            }

            if(!str_contains($consulta,'LIMIT')){
                $consulta .= " LIMIT $limite";
            }else{
                preg_replace("/LIMIT [0-9]+/","LIMIT $limite",$consulta);
            }

            $this -> consulta = $consulta;

            return $this;
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * isSame
         * Método encargado de comprobar que un modelo pasado por parámetro es igual que el actualmente instanciado
         * @param  object $modelo
         * @return bool
         */
        public function isSame(object $modelo):bool {
            $registroActual = $this;
            $columnasActuales = $registroActual -> array();
            $columnasNuevas = $modelo -> array();

            $seComprobaronTodas = false;
            $numColumnasActuales = count($columnasActuales);
            $indiceActual = $numColumnasActuales - 1;
            $numColumnasNuevas = count($columnasNuevas);
            $indiceNuevo = $numColumnasNuevas - 1;

            $resultados = [];

            do{
                $columnaActual = $columnasActuales[$indiceActual];
                $columnaNueva = $columnasNuevas[$indiceNuevo];

                $resultados[] = $columnaActual == $columnaNueva;

                $indiceActual--;
                $indiceNuevo--;

                if($indiceActual <= 0 || $indiceNuevo <= 0){
                    $seComprobaronTodas = true;
                }
            }while(!$seComprobaronTodas);


            return !in_array(false,$resultados);
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * orderby
         * Método encargado de setear la consulta según el nombre de la columna pasada por parametro y el orden asc o desc
         * @param  string $columna
         * @param  string $orden
         * @return object
         */
        public function orderby(string $columna,string $orden):object {
            $consulta = $this -> consulta;
            $orden = match($orden){
                "asc" => "ASC",
                default => "DESC"
            };

            if(empty($consulta)){
                $object = new static();
                $tabla = $object -> tabla;
                $consulta = "SELECT * FROM $tabla ORDER BY $columna $orden";
            }else{
                if(str_contains($consulta,'ORDER BY')){
                    $indiceOrder = strpos($consulta,'ORDER BY');
                    $consulta = substr($consulta,0,$indiceOrder);
                }

                $consulta .= " ORDER BY $columna $orden";
            }
            
            $this -> consulta = $consulta;
            return $this;
        }

        public function paginate($pagina = 1,$elementosPorPagina = 4){
            $consulta = $this -> consulta;
            $limiteInicial = 0;
            $limiteFinal = $elementosPorPagina;
            $tabla = new static ();
            $tabla = $tabla -> tabla;

            $total = static::count();
            (int) $paginas = round($total / $elementosPorPagina);
            $paginas = $paginas <= 0 ? 1 : $paginas;

            if(empty($consulta)){
                $consulta = "SELECT * FROM $tabla";
            }else{
                if(str_contains($consulta,'LIMIT')){
                    $indice = strpos($consulta,'LIMIT');
                    $consulta = substr($consulta,0,$indice);
                }
            }
            
            if($pagina <= $paginas){
                for($iteracion = 0; $iteracion < $pagina; $iteracion++){
                    if($iteracion != 0){
                        $limiteInicial += $elementosPorPagina;
                        $limiteFinal += $elementosPorPagina;
                    }
                }
            }
            
            $consulta .= " LIMIT $limiteInicial,$limiteFinal";
            $this -> consulta = $consulta;
            return $this;
        }

        /**
         * execute
         * Método que ejecuta las consultas y retorna el resultado en una Coleccion o un Modelo si encuentra resultados
         * @return object|false
         * @private
         * @author Sxcram02 ms2d0v4@gmail.com
         */
        private function execute(): object|false {
            $db = self::$database;
            $consulta = $this -> consulta;
            if (empty($consulta)) {
                return false;
            }

            /*
            * @todo Implementar la función formatConsulta para poner columnas en vez de *
            *  if (str_contains($consulta, 'SELECT')) {
            *      self::formatConsulta();
            *  }
            */

            $query = $db->query(query: $consulta);
            if (!$query) {
                return false;
            }
            
            $registros = $query->fetchAll(\PDO::FETCH_ASSOC);
            if(count($registros) <= 0){
                return false;
            }

            return count($registros) > 1 ? new Coleccion($registros,static::class) : self::createModel($registros[0]);
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * formatTabla
         * Método que determina el nombre y abreviación de la tabla actual
         * @return string - Una cadena en formato "tabla tab"
         */
        private function formatTabla(): string {
            $tieneWhere = strpos($this -> consulta,'WHERE');
            $tieneJoin = strpos($this -> consulta,'JOIN',);
            $indiceNombreTabla = strrpos($this -> consulta,'FROM') + 5;

            $longitud = strlen($this -> consulta);

            if (!$tieneWhere && $tieneJoin) {
                $longitud = $tieneJoin;
                $longitud -= $indiceNombreTabla;
            }

            if ($tieneWhere && !$tieneJoin) {
                $longitud = $tieneWhere;
            }

            // froM tabla1 tab Join
            // froM tabla1 tab
            // froM tabla1 tab Where
            return substr($this -> consulta,$indiceNombreTabla,$longitud - 1);
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * setColumnas
         * Método que si la clase tiene columnas seteadas las crea de forma dinámica, si no las crea según sus columnas de la base de datos.
         * @param string $tabla
         * @return void
         */
        private function setColumnas(string $tabla): void {
            if ($this -> columnas !== null) {
                foreach ($this -> columnas as $atributo) {
                    $this->$atributo = null;
                }

            }else{
                $query = self::$database->query(query: "SHOW COLUMNS FROM $tabla");
                if ($query) {
                    $resultados = $query->fetchAll(\PDO::FETCH_ASSOC);
                    $columnas = [];

                    foreach ($resultados as $resultado) {
                        $columnas[] = $resultado['Field'];
                    }
                    $this -> columnas = $columnas;
                }
            }
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @protected
         * @static
         * obtenerIdTabla
         * Método encargado de obtener el id o ids de la tabla
         * @return mixed
         */
        protected static function obtenerIdTabla(): mixed  {
            $ids = [];
            $tabla = new static();
            $tabla = $tabla -> tabla;
            $query = self::$database->query(query: "SHOW COLUMNS FROM $tabla");

            if ($query) {
                $resultados = $query->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($resultados as $resultado) {
                    if(self::existenCoincidencias("/PRI/",$resultado['Key'])){
                        $ids[] = $resultado['Field'];
                    }
                }
            }

            if(count($ids) == 1){
                $ids = $ids[0];
            }

            return $ids;
        }
    }
?>