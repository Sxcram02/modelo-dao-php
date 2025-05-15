<?php
    namespace Src\App;

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * Openssl
     * Trait usada para la encriptación y desencriptación de datos con clave asimétrica.
     */
    trait Openssl {

        use GestorStrings;
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * La clave pública para su uso al encriptar los datos.
         * @static
         * @var string
         */
        public static string $clavePublica;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * La clave privada que será usada para desencriptar los datos.
         * @private
         * @static
         * @var string
         */
        private static string $clavePrivada;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * @static
         * getPrivateKey
         * Método que crea un par de claves privadas y públicas con un algoritmo sha256 de 2048 bits y del tipo RSA, exporta la clave y de esta obtiene la calve publica, las cuales serán seteadas en sus respectivos atributos.
         * @return void
         */
        private static function getPrivateKey(): void {

            $hasPrivateKey = file_exists('src/conf/private_key.pem');
            $hasPublicKey = file_exists('src/conf/public_key.pem');

            if($hasPrivateKey){
                $clavePrivada = file_get_contents('src/conf/private_key.pem');
            }else{
                $parClavesAsimetricas = openssl_pkey_new([
                    "digest_al" => "sha256",
                    "private_key_bits" => 2048,
                    "private_key_type" => OPENSSL_KEYTYPE_RSA
                ]);
                openssl_pkey_export($parClavesAsimetricas, $clavePrivada);

                file_put_contents('src/conf/private_key.pem',$clavePrivada);
            }

            if($hasPublicKey){
                $clavePublica = file_get_contents('src/conf/public_key.pem');
            }else{
                $detallesClavePublica = openssl_pkey_get_details($parClavesAsimetricas);
                $clavePublica = $detallesClavePublica['key'];
                file_put_contents('src/conf/public_key.pem',$clavePublica);
            }

            self::$clavePublica = $clavePublica;
            self::$clavePrivada = $clavePrivada;
        }
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * token
         * Método que retorna un token con el cual se comprobará que los datos son seguros y será usado para el cifrado de los mismos.
         * @return string
         * @throws \Exception En caso de no existir o no tener clave pública.
         */
        private function token(): string {
            if(!isset(self::$clavePrivada) || !isset(self::$clavePublica)){
                throw new \Exception('Las claves no se crearon adecuadamente');
            }

            return "<input type='hidden' name='clave-publica' value='".htmlspecialchars(self::$clavePublica) ."' />";
        }
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * @static
         * encript
         * Método encargado de encriptar los datos gracias a la clave pública generá en la función getInstance.
         * @param  string|array $datos
         * @return mixed
         */
        private static function encript(string|array $datos): mixed {

            if(!isset(self::$clavePublica)){
                throw new \Exception('No existe clave pública disponible');
            }

            if(!is_array($datos)){
                openssl_public_encrypt($datos,$datoEncriptado,self::$clavePublica,OPENSSL_PKCS1_PADDING);
                $datoCodificado = self::encode($datoEncriptado);
                return $datoCodificado;
            }


            foreach($datos as $indice => $dato){
                openssl_public_encrypt($dato,$datoEncriptado,self::$clavePublica,OPENSSL_PKCS1_PADDING);
                $datoCodificado = self::encode($datoEncriptado);
                $datos[$indice] = $datoCodificado;
            }

            return $datos;

        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * @static
         * descript
         * Método encargado de desencriptar los datos recibidos gracias a la clave privada ya generada anteriormente.
         * @param string|array $datos
         * @return mixed
         */
        private static function decript(string|array $datos): mixed {

            if(!isset(self::$clavePrivada)){
                throw new \Exception('No existe clave privada disponible');
            }

            if(!is_array($datos)){
                $datoDescodificado = self::decode($datos);
                $estaDesencriptado = openssl_private_decrypt($datoDescodificado,$datoDesencriptado,self::$clavePrivada,OPENSSL_PKCS1_PADDING);

                return $estaDesencriptado ? $datoDesencriptado : '';
            }

            foreach($datos as $indice => $dato){
                $datoDescodificado = self::decode($dato);
                $estaDesencriptado = openssl_private_decrypt($datoDescodificado,$datoDesencriptado,self::$clavePrivada,OPENSSL_PKCS1_PADDING);
                $datos[$indice] = $estaDesencriptado ? $datoDesencriptado : '';
            }

            return $datos;
        }
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * @static
         * encode
         * Metodo encargado de codificar unos datos en base64 por defecto
         * @param  mixed $datos
         * @param  string $base
         * @default base64
         * @return mixed
         */
        private static function encode(mixed $datos,string $base = "base64"): mixed {
            if(!in_array($base,['base64'])){
                throw new \Exception("No esta permitido $base como formato de codificación");
            }

            if(!is_array($datos) && isset($datos) && !empty($datos)){
                return match($base){
                    "hexa" => hex2bin($datos),
                    default => base64_encode($datos)
                };
            }

            if(empty($datos)){
                return null;
            }

            foreach($datos as $indice => $dato){
                if(!is_array($dato) && isset($dato) && !empty($dato)){
                    $datos[$indice] = match($base){
                        "hexa" => hex2bin($dato),
                        default => base64_encode($dato)
                    };
                }
            }

            return $datos;

        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * @static
         * decode
         * Metodo encargado de descodificar unos datos en base64 por defecto
         * @param  mixed $datos
         * @param  string $base
         * @default base64
         * @return mixed
         */
        private static function decode(mixed $datos,string $base = "base64"): mixed {
            if(!in_array($base,['base64'])){
                throw new \Exception("No esta permitido $base como formato de codificación");
            }

            if(!is_array($datos) && isset($datos) && !empty($datos)){
                return match($base){
                    "hexa" => bin2hex($datos),
                    "base64" => base64_decode($datos),
                    default => $datos
                };
            }

            if(empty($datos)){
                return $datos;
            }

            foreach($datos as $indice => $dato){
                if(!is_array($dato) && isset($dato) && !empty($dato)){
                    $datos[$indice] = match($base){
                        "hexa" => bin2hex($dato),
                        "base64" => base64_decode($dato),
                        default => $dato
                    };
                }
            }

            return $datos;

        }
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @static
         * estaCodificado
         * Método encargado de comprobar si una cadena esta o no codificada
         * @param  mixed $datos
         * @return bool
         */
        public static function estaCodificado(mixed $datos): bool {
            if(!is_array($datos) && isset($datos) && !empty($datos)){

                return self::existenCoincidencias("/([a-zA-Z0-9\/\r\n\+]+={1,2}$)/",$datos) ? true : false;
            }

            if(empty($datos)){
                return false;
            }

            foreach($datos as $dato){
                if(!is_array($datos) && isset($datos) && !empty($datos)){
                    if(self::existenCoincidencias("/([a-zA-Z0-9\/\r\n\+]*={1,2}$)/",$datos)){
                        return  true;
                    }
                }
            }

            return false;
        }
    }
?>
