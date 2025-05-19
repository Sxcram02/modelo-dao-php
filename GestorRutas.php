<?php
    namespace Src\App;
    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * GestorRutas
     * Trait usado para la separación y comprobación de las rutas personalizadas
     */
    trait GestorRutas {
        use GestorStrings;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @static
         * separarRutas
         * Método encargado de devolver un array con las expresiones coincidentes en una url que empiecen con un slash
         * @param  string $ruta - "/ruta"
         * @return array
         */
        public static function separarRutas(string $ruta): array {
            $partesDeRuta = [];
            // La aserción ?: es para evitar seleccionar un subconjunto
            preg_match_all("/\/(?:[^\/]+)/i", $ruta, $partesDeRuta);

            return $partesDeRuta[0];
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @static
         * tienenMismasPartes
         * Método estático que recibe dos rutas, la primera es la ruta requerida y la segunda la de entorno o que será usada si todas sus partes sin incluir los parámetros son iguales retorna true.
         * @param  string $rutaReq - la ruta solicitada por el usuario
         * @param  string $rutaEnv - la ruta permitida y definida en el objeto
         * @return bool
         */
        public static function tienenMismasPartes(string $rutaReq, string $rutaEnv): bool {
            $partesRutaReq = self::separarRutas($rutaReq);
            $partesRutaEnv = self::separarRutas($rutaEnv);

            $numConcidenciasReq = count($partesRutaReq);
            $numConincidenciasEnv = count($partesRutaEnv);

            if(self::existenCoincidencias("/\(.*\)/",$rutaEnv)){
                if($numConincidenciasEnv > $numConcidenciasReq && $numConcidenciasReq != $numConincidenciasEnv){
                    $numConincidenciasEnv -= 1;
                }
            }

            if ($numConcidenciasReq != $numConincidenciasEnv) {
                return false;
            }


            return true;
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @static
         * tieneParametros
         * Método estatico encargado a partir de una expresion regular determinar si en una url existen parámetros.
         * @param  string $ruta - url a comprobar
         * @return bool
         */
        public static function tieneParametros(string $ruta): bool {
            return self::existenCoincidencias("/\{.*\}|\(.*\)/", $ruta);
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @static
         * sonMismaRuta
         * Método que recibe ds rutas de una url, las divide en segmentos y compará cada uno de los segmentos, en caso de no ser estricto toda parte que posea parámetros marcados con {} en las dos rutas no se incluye en la comparación.
         * @param  string $rutaReq - generalmente la ruta que solicita el usuario
         * @param  string $rutaEnv - la ruta de entorno de servidor
         * @param  bool $esEstricto - si deben o no coincidir la estructura con parametros inclusive
         * @default false
         * @return bool
         */
        public static function sonMismaRuta(string $rutaReq, string $rutaEnv, bool $esEstricto = false): bool {
            $sonLaMismaRuta = true;
            $partesDeRutaReq = self::separarRutas($rutaReq);
            $partesDeRutaEnv = self::separarRutas($rutaEnv);

            if (!self::tienenMismasPartes($rutaReq, $rutaEnv)) {
                $sonLaMismaRuta = false;
            } else {
                foreach ($partesDeRutaEnv as $indice => $parte) {
                    if ($esEstricto && $parte != $partesDeRutaReq[$indice]) {
                        $sonLaMismaRuta = false;
                    } else {
                        if (!self::tieneParametros($parte) && $parte != $partesDeRutaReq[$indice]) {
                            $sonLaMismaRuta = false;
                        }
                    }
                }
            }

            return $sonLaMismaRuta;
        }
    }
?>