<?php
    namespace Src\App;
    
    use TCPDF;
    use Imagick;
    class PDF extends TCPDF {

        public function __construct(array $datos){

        }

        private function formatearImagen(string $rutaImagen){
            // CREACIÓN IMAGEN PERFIL
            $imagePng = imagecreatefromstring(file_get_contents($rutaImagen));
            imagepng($imagePng, 'fotoperfil.png');
            imagedestroy($imagePng);

            $dest = '/fotoredondeada.png'; // Imagen de salida
            $path =  '/fotoperfil.png';

            if (file_exists($path)) {
                $image = new \Imagick($path);
                $image->setImageFormat('png');

                // Recorte centrado cuadrado
                $width = $image->getImageWidth();
                $height = $image->getImageHeight();
                $size = min($width, $height);
                $x = ($width - $size) / 2;
                $y = ($height - $size) / 2;
                $image->cropImage($size, $size, $x, $y);
                $image->resizeImage(280, 280, \Imagick::FILTER_LANCZOS, 1);

                // Asegurar transparencia y canal alfa
                $image->setImageMatte(true);
                $image->setImageAlphaChannel(\Imagick::ALPHACHANNEL_SET);

                // Crear la máscara circular (blanco visible, negro transparente)
                $mask = new \Imagick();
                $mask->newImage(280, 280, new \ImagickPixel('black'), 'png');

                $draw = new \ImagickDraw();
                $draw->setFillColor('white');
                $draw->circle(140, 140, 140, 0);
                $mask->drawImage($draw);

                // Aplicar la máscara al canal alfa
                $image->compositeImage($mask, \Imagick::COMPOSITE_COPYOPACITY, 0, 0);

                // Guardar imagen con transparencia
                $image->writeImage($dest);

                // Liberar recursos
                $image->clear();
                $mask->clear();
            }
        }


        private function formatearFormaciones(array $formaciones){
            // FORMACIONES DE LA CUENTA
            if (!empty($curriculum->formaciones)) {
                $pdf->SetTextColor(12, 155, 239);
                $apartado = "<h3>FORMACIONES Y CURSOS</h3><hr />";
                $pdf->writeHTMLCell(180, $alturaCelda, 10, $posicionActual, $apartado);
                $posicionActual += $alturaCelda;
                $pdf->SetTextColor(0, 0, 0);
                foreach ($curriculum->formaciones as $formacion) {
                    $formacion = $formacion->array();
                    $inicio = $formacion['anioInicio'];
                    $fin = $formacion['anioFin'];

                    $titulo = $formacion['titulo'];
                    $centro = $formacion['centro'];
                    $notaMedia = $formacion['notaMedia'];

                    $tablaFormacion = "<table><tr>
                        <td>[$inicio - $fin]</td>
                        <td>$titulo</td>
                        <td>$centro</td>
                        <td>$notaMedia</td>
                    </tr></table>";

                    $pdf->writeHTMLCell(225, $alturaCelda, 10, $posicionActual, $tablaFormacion);
                    $posicionActual += $alturaCelda;
                }
            }
        }
    }
?>