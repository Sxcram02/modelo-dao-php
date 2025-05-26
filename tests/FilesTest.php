<?php
    declare(strict_types=1);
    namespace Src\App\Tests;

    use Src\App\Files;
    use PHPUnit\Framework\TestCase;
    use PHPUnit\Framework\Attributes\Test;
    use PHPUnit\Framework\Attributes\TestDox;
    use PHPUnit\Framework\Attributes\TestWith;
    
    final class FilesTest extends TestCase {
        /**
         * imagen
         * Método encargado de testear la subida de una imagen
         * @param string $archivo
         * @return void
         */
        #[Test]
        #[TestDox('Método encargado de filtrar y subir una imagen al servidor')]
        #[TestWith(['public\img\Zurera & Perfiles (67).jpg'])]
        public function imagen(string $archivo):void {
            $datosImagen = [
                'name' => basename($archivo),
                'type' => mime_content_type($archivo),
                'tmp_name' => $archivo,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($archivo)
            ];

            $imagen = Files::filtrarImagen($datosImagen);
            
            if(!is_object($imagen['resultado'])){
                echo "\n ❌ Error: " . $imagen['mensaje'] . "\n";
            }

            $imagen = $imagen['resultado'];
            $this -> assertInstanceOf(Files::class,$imagen,'El archivo no se subio correctamente ❌');

            $nombreArchivo = $imagen -> getNombreArchivo();
            $rutaArchivo = $imagen -> getUbicacionArchivo();
            $tamanioArchivo = $imagen -> getTamanioArchivo();

            $this -> assertNotEmpty($nombreArchivo,'El archivo no tiene nombre');

            echo "\n ✅ La imagen se subió: \n * Con este nombre: $nombreArchivo \n * En esta ruta: $rutaArchivo \n * Con un tamaño de $tamanioArchivo MB";
        }
    }
?>