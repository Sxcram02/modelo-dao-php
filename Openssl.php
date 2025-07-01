<?php

namespace Src\App;

/**
 * Openssl
 * Trait usada para la encriptación y desencriptación de datos con clave asimétrica.
 * @author Sxcram02 <ms2d0v4@gmail.com>
 */
trait Openssl
{

    use GestorStrings;
    /**
     * La clave pública para su uso al encriptar los datos.
     * @var string
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public static string $clavePublica;

    /**
     * La clave privada que será usada para desencriptar los datos.
     * @var string
     * @private
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private static string $clavePrivada;

    /**
     * getPrivateKey
     * Método que crea un par de claves privadas y públicas con un algoritmo sha256 de 2048 bits y del tipo RSA, exporta la clave y de esta obtiene la calve publica, las cuales serán seteadas en sus respectivos atributos.
     * @return void
     * @private
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private static function getPrivateKey(): void {

        $hasPrivateKey = file_exists('src/conf/private_key.pem');
        $hasPublicKey = file_exists('src/conf/public_key.pem');

        if ($hasPrivateKey) {
            $clavePrivada = file_get_contents('src/conf/private_key.pem');
        } else {
            $parClavesAsimetricas = openssl_pkey_new([
                "digest_al" => "sha256",
                "private_key_bits" => 2048,
                "private_key_type" => OPENSSL_KEYTYPE_RSA
            ]);
            openssl_pkey_export($parClavesAsimetricas, $clavePrivada);

            file_put_contents('src/conf/private_key.pem', $clavePrivada);
        }

        if ($hasPublicKey) {
            $clavePublica = file_get_contents('src/conf/public_key.pem');
        } else {
            $detallesClavePublica = openssl_pkey_get_details($parClavesAsimetricas);
            $clavePublica = $detallesClavePublica['key'];
            file_put_contents('src/conf/public_key.pem', $clavePublica);
        }

        self::$clavePublica = $clavePublica;
        self::$clavePrivada = $clavePrivada;
    }

    /**
     * token
     * Método que retorna un token con el cual se comprobará que los datos son seguros y será usado para el cifrado de los mismos.
     * @return string
     * @throws \Exception En caso de no existir o no tener clave pública.
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private function token(): string {
        if (!isset(self::$clavePrivada) || !isset(self::$clavePublica)) {
            throw new \Exception('Las claves no se crearon adecuadamente');
        }

        return "<input type='hidden' name='clave-publica' value='" . htmlspecialchars(self::$clavePublica) . "' />";
    }

    /**
     * encript
     * Método encargado de encriptar los datos gracias a la clave pública generá en la función getInstance.
     * @param  string|array $datos
     * @return mixed
     * @private
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private static function encript(string|array $datos, string $base = "base64"): mixed {

        if (!isset(self::$clavePublica)) {
            throw new \Exception('No existe clave pública disponible');
        }

        if (!is_array($datos)) {
            openssl_public_encrypt($datos, $datoEncriptado, self::$clavePublica, OPENSSL_PKCS1_OAEP_PADDING);
            $datoCodificado = self::encode($datoEncriptado, $base);
            return $datoCodificado;
        }


        foreach ($datos as $indice => $dato) {
            openssl_public_encrypt($dato, $datoEncriptado, self::$clavePublica, OPENSSL_PKCS1_OAEP_PADDING);
            $datoCodificado = self::encode($datoEncriptado, $base);
            $datos[$indice] = $datoCodificado;
        }

        return $datos;
    }

    /**
     * descript
     * Método encargado de desencriptar los datos recibidos gracias a la clave privada ya generada anteriormente.
     * @param string|array $datos
     * @return mixed
     * @private
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private static function decript(string|array $datos, string $base = "base64"): mixed {
        if (!isset(self::$clavePrivada)) {
            throw new \Exception('No existe clave privada disponible');
        }

        if (!is_array($datos)) {
            $datoDescodificado = self::decode($datos, $base);
            $estaDesencriptado = openssl_private_decrypt($datoDescodificado, $datoDesencriptado, self::$clavePrivada, OPENSSL_PKCS1_OAEP_PADDING);

            return $estaDesencriptado ? $datoDesencriptado : '';
        }

        foreach ($datos as $indice => $dato) {
            $datoDescodificado = self::decode($dato, $base);
            $estaDesencriptado = openssl_private_decrypt($datoDescodificado, $datoDesencriptado, self::$clavePrivada, OPENSSL_PKCS1_OAEP_PADDING);
            $datos[$indice] = $estaDesencriptado ? $datoDesencriptado : '';
        }

        return $datos;
    }

    /**
     * encode
     * Metodo encargado de codificar unos datos en base64 por defecto
     * @param  mixed $datos
     * @param  string $base
     * @default base64
     * @return mixed
     * @private
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private static function encode(mixed $datos, string $base = "base64"): mixed {
        if (!in_array($base, ['base64', 'url'])) {
            throw new \Exception("No esta permitido $base como formato de codificación");
        }

        if (!isset($datos) || empty($datos)) {
            return false;
        }

        if (!is_array($datos)) {
            $datos =  match ($base) {
                "hexa" => hex2bin($datos),
                default => base64_encode($datos)
            };

            $datos = preg_replace("/\/+/", "@", $datos);
            $datos = preg_replace('/\+{1,}/', '!', $datos);

            if ($base == "url") {
                $datos = rawurlencode($datos);
            }

            return $datos;
        }

        foreach ($datos as $indice => $dato) {
            if (isset($dato) && !is_array($dato) && !empty($dato)) {
                $dato = match ($base) {
                    "hexa" => hex2bin($dato),
                    default => base64_encode($dato)
                };

                $dato = preg_replace("/\/+/", "@", $dato);
                $datos = preg_replace('/\+{1,}/', '!', $datos);

                if ($base == "url") {
                    $datos[$indice] = rawurlencode($dato);
                }
            }
        }

        return $datos;
    }

    /**
     * decode
     * Metodo encargado de descodificar unos datos en base64 por defecto
     * @param  mixed $datos
     * @param  string $base
     * @default base64
     * @return mixed
     * @private
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private static function decode(mixed $datos, string $base = "base64"): mixed {
        if (!in_array($base, ['base64', 'url'])) {
            throw new \Exception("No esta permitido $base como formato de codificación");
        }

        if (!isset($datos) || empty($datos)) {
            return false;
        }

        if (!is_array($datos)) {
            if ($base == "url") {
                $datos = rawurldecode($datos);
            }

            $datos = preg_replace("/\@+/", '/', $datos);
            $datos = preg_replace("/\!+/", '+', $datos);

            $datos = match ($base) {
                "hexa" => bin2hex($datos),
                default => base64_decode($datos,true)
            };

            return $datos;
        }

        foreach ($datos as $indice => $dato) {
            if (!is_array($dato) && isset($dato) && !empty($dato)) {
                if ($base == "url") {
                    $dato = rawurldecode($dato);
                }

                $dato = preg_replace("/\@+/", '/', $dato);
                $dato = preg_replace("/\!+/", '+', $dato);

                $datos[$indice] = match ($base) {
                    "hexa" => bin2hex($dato),
                    default => base64_decode($dato)
                };
            }
        }

        return $datos;
    }

    /**
     * estaCodificado
     * Método encargado de comprobar si una cadena esta o no codificada
     * @param  mixed $datos
     * @return bool
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public static function estaCodificado(mixed $datos): bool {
        if (!is_array($datos) && isset($datos) && !empty($datos)) {
            if (strlen($datos) >= 350) {
                return true;
            }

            return self::existenCoincidencias("/([a-zA-Z0-9\/\r\n\+]+={1,2}$)/", $datos) ? true : false;
        }

        if (empty($datos)) {
            return false;
        }

        foreach ($datos as $dato) {
            if (!is_array($dato) && isset($dato) && !empty($dato)) {
                if (strlen($dato) >= 350) {
                    return true;
                }

                if (self::existenCoincidencias("/([a-zA-Z0-9\/\r\n\+\@\!\_]*={1,2}$)/", $dato)) {
                    return  true;
                }
            }
        }

        return false;
    }
}
?>