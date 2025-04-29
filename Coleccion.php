<?php
    namespace Src\App;
    
    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * Coleccion
     * Clase encargada de agrupar y gestionar varios Modelos
     */
    class Coleccion {
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Array con todos los modelos creados
         * @private
         * @var array
         * @default []
         */
        private array $modelos = [];

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Número total de modelos en la Colección
         * @var int
         * @default 0
         */
        private int $numModelos = 0;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Clase tomada por referencia para la coleccion, es decir Coleccion de Modelo::class
         * @private
         * @var string
         */
        private string $clase;
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * __construct
         * Método que recibe un array de registros con datos y una clase de Modelo sobre la que se crear un modelo con los datos
         * @param  array $registros
         * @param  string $clase
         * @return void
         */
        public function __construct(array $registros,string $clase){
            $this -> clase = $clase;
            foreach($registros as $registro){
                $this -> modelos[] = $clase::createModel($registro);
            }
            $this -> numModelos = count($this -> modelos);
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * where
         * Método encargado ejecutar un where en base a la consulta ralizada anteriormente
         * @param  array|string $condicionado
         * @param  array|string $operador
         * @param  mixed $condicion
         * @return object
         */
        public function where(array|string $condicionado,array|string $operador,mixed $condicion):object {
            return $this -> modelos[0] -> where($condicionado,$operador,$condicion);
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * join
         * Método encargado ejecutar un join en base a la consulta ralizada anteriormente
         * @param  string $tablaUnida
         * @param  array|string $clavesForaneas
         * @return object
         */
        public function join(string $tablaUnida, array|string $clavesForaneas):object {
            return $this -> modelos[0] -> join($tablaUnida,$clavesForaneas);
        }

        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * getModelsArray
         *  Método que retorna el array de modelos
         * @return array
         */
        public function getModelsArray(): array {
            return $this -> modelos;
        }
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * array
         * Método que retorna un array de arrays con los datos de los modelos
         * @return array
         */
        public function array():array {
            $modelos = [];
            foreach($this -> modelos as $modelo){
                $modelos[] = $modelo -> array();
            }
            return $modelos;
        }
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * isColeccion
         * Método encargado de comprobar que la instancia actual es una Colección
         * @return bool
         */
        public function isColeccion(): bool {
            return $this instanceof Coleccion;
        }
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * paginate
         * Método que recibe la pagina solicitada para ver y los elementos deseados por cada pagina retornara la pagina x con los registros y
         * @param  int $numPagina
         * @default 1
         * @param  int $elementosPorPagina
         * @default 4
         * @return object
         */
        public function paginate(int $numPagina = 1,int $elementosPorPagina = 4):object {
            $modelos = $this -> getModelsArray();
            $elementosTotales = count($modelos);
            if($elementosTotales < $elementosPorPagina){
                $elementosPorPagina = $elementosTotales;
            }

            $numPaginas = $elementosTotales / $elementosPorPagina;
            $resto = $elementosTotales % $elementosPorPagina;
            $numPaginas = $resto > 0 ? intval($numPaginas) + 1 : $numPaginas;

            $paginasHechas = $indice = $elementosPuestosPorPagina = 0;
            $paginas = $pagina = [];

            do{
                if($elementosPuestosPorPagina < $elementosPorPagina && $indice < $elementosTotales){
                    $pagina[] = $modelos[$indice];
                    $indice++;
                    $elementosPuestosPorPagina++;
                }else{
                    $paginas[] = $pagina;
                    $pagina = [];
                    $elementosPuestosPorPagina = 0;
                    $paginasHechas++;
                }
            }while($paginasHechas != $numPaginas);

            $indice = $numPagina <= 0 ? $numPagina : $numPagina - 1;
            $this -> modelos = count($paginas) > 1 ? $paginas[$indice] : $paginas[0];
            return $this;
        }
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * json
         * Método encargado de retornar un json de un array de arrays con los datos de los modelos
         * @return bool|string
         */
        public function json(): bool|string {
            return json_encode($this -> array(),JSON_PRETTY_PRINT);
        }
    }
?>