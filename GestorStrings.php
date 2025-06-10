<?php
    namespace Src\App;

    trait GestorStrings {

        /**
         * estaCifrada
         * Función que determina si una cadena esta en cifrada o no, comprobando si la cadena contiene caractéres que no son imprimibles, además de comprobar si la longitud de la cadena se corresponde con 2^4 hasta 2^10 como sulen ser lss cadebas cifradas.
         * @param string $cadena - cadena de texto que será analizada
         * @return bool
         */
        public static function estaCifrada(string $cadena): bool {
            if (!ctype_print($cadena)){
                return true;
            }

            if(in_array(strlen($cadena),[16,32,64,128,256,512,1028])){
                return true;
            }

            // Parece que no es una cadena cifrada.
            return false;
        }
        /**
         * esCadenaTextoValida
         * Función qu recibe una cadena de texto y comprueba si el dato existe y no esta vacío.
         * @param  string $cadena - cadena de caractéres que será analizada.
         * @return bool
         */
        public static function esCadenaTextoValida(string $cadena): bool {
            return (isset($cadena) && !empty($cadena)) ? true : false;
        }

        /**
         * separarString
         * Función que recibe un pattern regex  comprueba que este un separador como "," o ";" o ":" o "-" existe en la cadena, si es asi separa la cadena en un array y en caso contrario retorna la cadena.
         *
         * @param string $cadena
         * @return array|string
         */
        public static function separarString(string $cadena,string $pattern = "/(?=.*[a-z]+)[,:;]/"): array|string {
            $separadores = [];
            if (preg_match($pattern, $cadena, $separadores)) {
                $indiceSeparador = strpos($cadena,$separadores[0]);
                $separador = $cadena[$indiceSeparador];
                return explode(separator: $separador, string: $cadena);
            }
            return $cadena;
        }

        /**
         * buscarString
         * Función que recibe como parametro un caracter buscado en una cadena y devuelve el indice de este caracter en caso de encontrarlo por el contrario retorna false.
         * @param  string $buscado - el carácter buscado en una cadena.
         * @param  string $cadena - la cadena en la que se busca el carácter.
         * @return string|false
         */
        public static function buscarString(string $buscado, string $cadena): int|false {

            if(!self::existenCoincidencias("/$buscado/",$cadena)){
                return false;
            }

            for ($indiceStr = 0; $indiceStr < strlen(string: $cadena); $indiceStr++) {
                if ($cadena[$indiceStr] == $buscado) {
                    return $indiceStr;
                }
            }
            return false;
        }

        /**
         * filtrarContenido
         * Función que recibe un objeto con la estructura de un objeto HTML y filtra su contenido en texto devuelve una cadena filtrada.
         * @param  string $elemento - un dati cuan sea
         * @return string
         */
        public static function filtrarContenido(?string $elemento = ""): ?string {

            if(isset($elemento)){
                if(self::existenCoincidencias("/^\s+|\s+$/i",$elemento)){
                    $elemento = preg_replace("/^\s*/","",$elemento);
                    $elemento = preg_replace("/\s+$/","",$elemento);
                }
    
                if(self::existenCoincidencias("/[\`\'\´\(\)\[\{\}]+|\]+/",$elemento)){
                    $elemento = preg_replace("/[\`\'\´\(\)\[\{\}]+|\]+/","",$elemento);
                }
    
                
                if(self::existenCoincidencias("/(?![\wñáíóúüöÁÍÉÚÓÖÜ\_]+)/",$elemento)){
                    $elemento = htmlspecialchars($elemento);
                }
    
                $elemento = stripslashes($elemento);
            }
            return $elemento;
            
        }

        /**
         * existenCoincidencias
         * Función que dado un pattern y una cadena retorna true si el número de coincidencias no es 0 y el indice no es false, en caso contrario retorna false.
         * Además por parámetro de salida le añadimos las coincidencias encontradas en caso de haberlas.
         * @param  string $pattern - pattern regex que será usado.
         * @param  string $cadena - cadena que será analizada con el pattern.
         * @param  array $coincidencias - todas las coincidencias obtenidas en la cadena.
         * @return bool
         */
        
        public static function existenCoincidencias(string $pattern,string $cadena,array &$coincidencias = []): bool {
            $indiceCoincidencia = preg_match($pattern,$cadena,$coincidencias);

            return $indiceCoincidencia === false || empty($coincidencias) ? false : true;
        }
    }
?>