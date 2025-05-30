<?php
    namespace Src\App;
    /**
     * Consulta
     * Clase que obtendra los parametros y valores asociados a una consulta los separará, determinara su tipo y los preparará en una cada para una consulta
     * @author Sxcram02 ms2d0v4@gmail.com
     * @use GestorStrings
     * @final
     */
    final class Consulta {
        use GestorStrings;
        /**
         * Cadena con la consulta original
         * @protected
         * @var string
         * @default ""
         * @protected
         * @author Sxcram02 ms2d0v4@gmail.com
         */
        protected string $consulta = "";

        /**
         * Cadena con la consulta preparada con los parametros ":nombre"
         * @var string
         * @default ""
         * @protected
         * @author Sxcram02 ms2d0v4@gmail.com
         */
        protected string $consultaPreparada = "";

        /**
         * Array con todos los valores de la consulta original
         * @var array
         * @default []
         * @protected
         * @author Sxcram02 ms2d0v4@gmail.com
         */
        protected array $parametros = [];
        
        /**
         * Array con los parametros de la consulta
         * @var array
         * @default []
         * @protected
         * @author Sxcram02 ms2d0v4@gmail.com
         */
        protected array $claves = [];

        /**
         * Array asociativo con la clave como nombre del parametro y un array con el valor y el tipo como valor del array
         * @var array
         * @default []
         * @protected
         * @author Sxcram02 ms2d0v4@gmail.com
         */
        protected array $parametrosPreparados = [];

        
        /**
         * __construct
         * Método que necesita una cadena para comprobar si posee una de las clausulas SHOW, INSERT, UPDATE, ... y obtendra sus parametros, si los tiene la formateará y seteara la consulta preparada.
         * @param  string $consulta
         * @default ""
         * @return void
         * @author Sxcram02 ms2d0v4@gmail.com
         */
        public function __construct(string $consulta = ""){
            if(!empty($consulta)){
                $this->consulta = $consulta;
                
                if (str_contains($consulta, "INSERT")) {
                    $this ->obtenerParametrosInsert($consulta);
                }
                
                if (str_contains($consulta, "UPDATE")) {
                    $this->obtenerParametrosUpdate($consulta);
                }
                
                if (str_contains($consulta, "WHERE")) {
                    $this ->obtenerParametrosWhere($consulta);
                }
                
                if(!str_contains($consulta,'SHOW')){
                    $this -> formatearParametros();
                    $this -> prepararConsulta();
                }else{
                    $this -> consultaPreparada = $consulta;
                }
            }
        }
        
        /**
         * __get
         * Método mágico que obtendra un atributo de la clase Consulta
         * @param  string $propiedad
         * @return mixed
         * @author Sxcram02 ms2d0v4@gmail.com
         */
        public function __get(string $propiedad): mixed{
            return $this->$propiedad;
        }
        
        /**
         * __set
         * Método mágico que seteará una propiedad con un valor y en caso de ser los parametros o claves, añadira valores no sustituir
         * @param  string $propiedad
         * @param  mixed $valor
         * @return void
         * @author Sxcram02 ms2d0v4@gmail.com
         */
        public function __set(string $propiedad, $valor) {
            if($propiedad === "parametros" || $propiedad === "claves"){
                $this -> $propiedad = (empty($this -> $propiedad)) ? $valor : array_merge($this -> $propiedad, $valor);
            }else{
                $this -> $propiedad = $valor;
            }
        }

        /**
         * tieneParametros
         * Método que comprueba que la consulta tiene o no parametros
         * @return bool
         * @author Sxcram02 ms2d0v4@gmail.com
         */
        public function tieneParametros(): bool {
            return !empty($this -> parametros);
        }

        /**
         * obtenerParametrosWhere
         * Método que selecciona la parte de la consulta WHERE sin contar el LIMIT u ORDER y a partir de una regex determina las claves y los parametros y los setea
         * @param  string $query
         * @return void
         * @todo optimizar la regex
         * @private
         * @author Sxcram02 ms2d0v4@gmail.com
         */
        private function obtenerParametrosWhere(string $query): void {
            $posClausulaWhere = strpos($query,'WHERE');

            if(str_contains($query,'ORDER BY')){
                $query = preg_replace("/\bORDER BY\b\s.*/","",$query);
            }

            if(str_contains($query,'LIMIT')){
                $query = preg_replace("/\bLIMIT\b\s[0-9]*(\,[0-9]+)?/","",$query);
            }

            $query = substr($query,$posClausulaWhere,strlen($query));

            $parametros = [];
            $condiciones = preg_split("/\bAND\b/",$query);
            foreach($condiciones as $cadena){
                preg_match_all("/(?<=[=<>!]\s|\s\bLIKE\b\s|\bAND\b\s|\bOR\b\s)\'?[ñáéíóúÁÉÍÓÚ\w\W\@\.\s\%-]+\'?/",$cadena,$coincidencias);
                $parametros[] = $coincidencias[0][0];
            }


            $regexColumnas = "/\b[a-z_][a-zA-Z0-9_\.-]*(?=\s*(=|<|>|!=|LIKE|\bNOT\s+LIKE))/";
            // version 0
            $regexParametros = "/(?<=[=<>!]\s|\s\bLIKE\b\s|\bAND\b\s|\bOR\b\s)\'?[ñáéíóúÁÉÍÓÚ\w\@\.\s\%-]+\'?(?=\s*\bAND\b|\bOR\b|$)/";
            // version 1
            $regexParametros = "/(?<=[=<>!]\s|\s\bLIKE\b\s)\'?[ñáéíóúÁÉÍÓÚ\w\@\.\s\%-]+\'?(?=\s*\bAND\b|\bOR\b|$)/";
            // version 2
            // @todo Problemas cuando se realizan dos operadores LIKE seguidos
            $regexParametros = "/(?<=[=<>!]\s|\s\bLIKE\b\s)\'?([%ñáéíóúÁÉÍÓÚ\w\@\.\s\%-]+)\'?(?=\s*\bAND\b|\s*\bOR\b|\s*\bLIMIT\b\s*$)/";
            
            //preg_match_all($regexParametros,$query,$parametros);
            preg_match_all($regexColumnas, $query, $claves);
    
            // En la clave 1 obtiene los operadores
            $claves = $claves[0];
            
            foreach ($claves as $indice => $clave) {
                $claves[$indice] = preg_replace("/\s?[a-z]{3}\./", "", $clave);
            }

            $this -> __set('claves',$claves);
            $this -> __set('parametros',$parametros);
        }

        /**
         * obtenerParametrosInsert
         * Método que selecciona la consulta INSERT a partir de una regex determina las claves y los parametros y los setea
         * @param  string $query
         * @return void
         * @private
         * @author Sxcram02 ms2d0v4@gmail.com
         */
        private function obtenerParametrosInsert(string $query): void {
            preg_match_all("/\([^(]+\)/i", $query, $parametrosInsert);

            $parametrosInsert = $parametrosInsert[0];

            $claves = explode(',', $parametrosInsert[0]);
            $parametros = explode(',', $parametrosInsert[1]);

            $this -> __set('claves',$claves);
            $this -> __set('parametros',$parametros);
        }

        /**
         * obtenerParametrosUpdate
         * Método que selecciona la consulta Update a partir de una regex determina las claves y los parametros y los setea
         * @param  string $query
         * @return void
         * @private
         * @author Sxcram02 ms2d0v4@gmail.com
         */
        private function obtenerParametrosUpdate(string $query): void {

        $regexUpdate = "/\b(\w*) = (\'?[\w\@\.\-_\s\/áéíóúñö:ü\$]+\'?)\b/i";

            if(str_contains($query,'WHERE')){
                $posClausulaWhere = strpos($query,'WHERE');
                $query = substr($query,0,$posClausulaWhere);
            }

            preg_match_all($regexUpdate, $query, $parametros);

            $this -> __set('claves',$parametros[1]);
            $this -> __set('parametros',$parametros[2]);
        }

        /**
         * formatearParametros
         * Método encargado de setear los parametros preparados en formato [":nombre" => [tipo,valor]
         * @return void
         * @private
         * @author Sxcram02 ms2d0v4@gmail.com
         */
        private function formatearParametros(): void {
            $parametros = $this -> parametros;
            $claves = $this -> claves;
            if(str_contains($this->consulta,'LIMIT')){
                preg_match("/\bLIMIT\b\s([0-9]+)\,([0-9]+)?/",$this->consulta,$coincidencias);
                
                $limiteInicial = $coincidencias[1];
                $limiteFinal = $coincidencias[2];

                $claves[] = 'limiteInicial';
                $parametros[] = $limiteInicial;

                if(!empty($limiteFinal)){
                    $claves[] = 'limiteFinal';
                    $parametros[] = $limiteFinal;
                }
            }

            $parametrosPreparados = [];

            foreach ($parametros as $indice => $parametro) {
                $nombreParametro = $claves[$indice];

                if(!in_array($nombreParametro,["passUsuario"])){
                    $parametro = self::filtrarContenido($parametro);
                }

                $nombreParametro = self::filtrarContenido($nombreParametro);
                $nombreParametro = preg_replace("/\s?\w+\./", "", $nombreParametro);
                
                $parametrosPreparados[":$nombreParametro"] = [
                    "valor" => $parametro,
                    "tipo" => $this -> determinarTipo($parametro)
                ];
            }

            $this -> __set('parametrosPreparados',$parametrosPreparados);
        }

        /**
         * determinarTipo
         * Método que a partir de un dato detrmina su tipo y en caso de ser una cadena comprobar que es una cadena o un numero con regex [0-9]+ retorna el tipo en base a PDO
         * @param  mixed $dato
         * @return int
         * @private
         * @author Sxcram02 ms2d0v4@gmail.com
         */
        private function determinarTipo(mixed $dato): int {
            if(is_string($dato)){
                $tipo = \PDO::PARAM_STR;

                if(self::existenCoincidencias("/^[0-9]{1,5}$/",$dato)){
                    return \PDO::PARAM_INT;
                }
                
                if(self::existenCoincidencias("/^(?!NULL|null)[\bfalse\b|\btrue\b]$/i",$dato)){
                    return \PDO::PARAM_BOOL;
                }

                if(self::existenCoincidencias("/^(?!NULL|null)[ñáéíóúÁÉÍÓÚ\w\@\.\s\%]+$/i",$dato)){
                    return \PDO::PARAM_STR;
                }

            }

            if(is_int($dato)){
                $tipo = \PDO::PARAM_INT;
            }

            if(is_bool($dato)){
                $tipo = \PDO::PARAM_BOOL;
            }

            if(!isset($dato) || self::existenCoincidencias("/null/i",$dato)){
                $tipo = \PDO::PARAM_NULL;
            }

            return $tipo;
        }
        
        /**
         * prepararConsulta
         * Método encargado de sustituir los valores de la consulta por su :nombre 
         * @return void
         * @private
         * @author Sxcram02 ms2d0v4@gmail.com
         */
        private function prepararConsulta(): void {
            if(count($this -> parametros) == count($this -> claves)){
                
                $consulta = $this -> consulta;
                $parametrosPreparados = $this -> parametrosPreparados;
                
                foreach ($parametrosPreparados as $nombreParametro => $detalles) {
                    $parametro = $detalles['valor'];

                    if(self::existenCoincidencias("/[\Wñáéíóúñöü]+/i",$parametro)){
                        $regexEspecial = preg_quote($parametro,'/');
                        $consulta = preg_replace("/$regexEspecial/", $nombreParametro, $consulta,1);
                    }else{
                        $consulta = preg_replace("/$parametro/", $nombreParametro, $consulta,1);
                    }

                }
                
                $this -> __set('consultaPreparada',$consulta);
            }
        }
    }
?>