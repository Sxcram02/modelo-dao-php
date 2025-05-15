<?php
    namespace Src\App;

    use Src\Controllers\AspiranteController;
    use Src\Controllers\ExperienciaController;
    use Src\Controllers\FormacionController;
    use Src\Controllers\IdiomaController;
    use Src\Controllers\UsuarioController;

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * Api
     * Clase encargada de asignar la funciónalidad a las rutas asignadas a una API
     * @todo darle una vuelta a su rol para ejecutar acciones
     * @implements Singelton
     */
    class Api implements Singelton {
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Variable que almacenará la primera y una instancia en el script
         * @static
         * @var ?Api
         */
        public static ?Api $instancia = null;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * getInstance
         * Método implementado de el patrón Singelton
         * @return object
         */
        public static function getInstance(): object {
            if (self::$instancia === null) {
                self::$instancia = new Api();
            }
            return self::$instancia;
        }
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * api
         * Método que en base a las variables obtenidas por GET asignara una función y un controlador y retornara una respuesta en formato json
         * @param  Request $request
         * @return void
         */
        public static function api(Request $request) {
            // Accion a realizar
            $accion = $request->__get('_action');

            // Parametros de acciones
            $pagina = empty($request->__get('_pagina')) ? 1 : (int) $request->__get('_pagina');

            $usuario = $request->__get('_user');
            $aspirante = AspiranteController::obtenerAspirante($usuario);
            
            
            $resultados = match ($accion) {
                "experiencias" => ExperienciaController::obtenerExperiencias($usuario, $aspirante->dni, $pagina),
                "formaciones" => FormacionController::obtenerFormaciones($usuario, $aspirante->dni),
                "idiomas" => IdiomaController::obtenerIdiomas($usuario, $aspirante->dni),
                "dni" => AspiranteController::buscarPorDni($request->_user),
                "email" => UsuarioController::buscarPorEmail($request->_user),
                "actividad" => UsuarioController::getActividad($request),
                default => []
            };

            if (is_object($resultados) && $resultados->isColeccion()) {
                $resultados = $resultados->array();
            }
            
            (!$resultados) ? Route::cabeceraRespuesta(404,[]) : Route::cabeceraRespuesta(200,$resultados);
        }
    }
?>