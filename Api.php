<?php

namespace Src\App;

use Src\Controllers\AspiranteController;
use Src\Controllers\ExperienciaController;
use Src\Controllers\FormacionController;
use Src\Controllers\IdiomaController;
use Src\Controllers\PublicacionController;
use Src\Controllers\UsuarioController;

/**
 * Api
 * Clase encargada de asignar la funciónalidad a las rutas asignadas a una API
 * @implements Singelton
 * @author Sxcram02 <ms2d0v4@gmail.com>
 * @todo darle una vuelta a su rol para ejecutar acciones
 */
class Api implements Singelton {
    /**
     * Variable que almacenará la primera y una instancia en el script
     * @var ?Api
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public static ?Api $instancia = null;

    /**
     * getInstance
     * Método implementado de el patrón Singelton
     * @return object
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public static function getInstance(): object {
        if (self::$instancia === null) {
            self::$instancia = new Api();
        }
        return self::$instancia;
    }

    /**
     * api
     * Método que en base a las variables obtenidas por GET asignara una función y un controlador y retornara una respuesta en formato json
     * @param  Request $request
     * @return void
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public static function api(Request $request):void {
        // Accion a realizar
        $accion = $request->__get('_action');

        // Parametros de acciones
        $pagina = empty($request->__get('_pagina')) ? 1 : (int) $request->__get('_pagina');

        if ($accion != 'pais') {
            $usuario = $request->__get('_user');
            $aspirante = AspiranteController::obtenerAspirante($usuario);
        }

        $resultados = match ($accion) {
            "publicaciones" => PublicacionController::obtenerPublicaciones($usuario, $pagina),
            "experiencias" => ExperienciaController::obtenerExperiencias($usuario, $pagina),
            "formaciones" => FormacionController::obtenerFormaciones($usuario, $pagina),
            "idiomas" => IdiomaController::obtenerIdiomas($usuario, $pagina),
            "dni" => AspiranteController::buscarPorDni($request->_user),
            "email" => UsuarioController::existeEmail($request->_user),
            "actividad" => UsuarioController::getActividad(),
            "filtrar","pais" => UsuarioController::locationFilter($request->_user, 'pais', !isset($request->_pagina) || empty($request->_pagina) ? 1 : $request->_pagina),
            "seguidores" => UsuarioController::obtenerSeguidores($request->_user),
            "seguidos" => UsuarioController::obtenerSeguidos($request->_user),
            'seguir' => UsuarioController::seguir($request->_user),
            'guardado' => UsuarioController::obtenerPublicacionesGuardadas($request->_user),
            default => []
        };

        if (is_object($resultados)) {
            $resultados = ($resultados->isColeccion()) ? $resultados->paginate($pagina)->array() : [$resultados->array()];
        }

        (!$resultados) ? Route::cabeceraRespuesta(404, []) : Route::cabeceraRespuesta(200, $resultados);
    }

    /**
     * paises
     * Método que retorna un json con todos los paises del mundo y las provincias y ciudades de España
     * @return void
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public static function paises() :void {
        header('Connection: close');
        header('Access-Allow-Control-Methods: GET');
        header('Access-Allow-Control-Origin: https://contratame.dev.com https://www.contratame.dev.com https://www.contratame.blog.com https://mdom.cifpceuta.com');

        Route::cabeceraRespuesta(200, [
            'paises' => ["Afganistán","Albania","Alemania","Andorra","Angola","Antigua y Barbuda","Arabia Saudita","Argelia","Argentina","Armenia","Australia","Austria","Azerbaiyán","Bahamas","Bangladés","Barbados","Baréin","Bélgica","Belice","Benín","Bielorrusia","Birmania","Bolivia","Bosnia y Herzegovina","Botsuana","Brasil","Brunéi","Bulgaria","Burkina Faso","Burundi","Bután","Cabo Verde","Camboya","Camerún","Canadá","Catar","Chad","Chile","China","Chipre","Colombia","Comoras","Corea del Norte","Corea del Sur","Costa de Marfil","Costa Rica","Croacia","Cuba","Dinamarca","Dominica","Ecuador","Egipto","El Salvador","Emiratos Árabes Unidos","Eritrea","Eslovaquia","Eslovenia","España","Estados Unidos","Estonia","Esuatini","Etiopía","Filipinas","Finlandia","Fiyi","Francia","Gabón","Gambia","Georgia","Ghana","Granada","Grecia","Guatemala","Guinea","Guinea-Bisáu","Guinea Ecuatorial","Guyana","Haití","Honduras","Hungría","India","Indonesia","Irak","Irán","Irlanda","Islandia","Islas Marshall","Islas Salomón","Israel","Italia","Jamaica","Japón","Jordania","Kazajistán","Kenia","Kirguistán","Kiribati","Kosovo","Kuwait","Laos","Lesoto","Letonia","Líbano","Liberia","Libia","Liechtenstein","Lituania","Luxemburgo","Madagascar","Malasia","Malaui","Maldivas","Malí","Malta","Marruecos","Mauricio","Mauritania","México","Micronesia","Moldavia","Mónaco","Mongolia","Montenegro","Mozambique","Namibia","Nauru","Nepal","Nicaragua","Níger","Nigeria","Noruega","Nueva Zelanda","Omán","Países Bajos","Pakistán","Palaos","Panamá","Papúa Nueva Guinea","Paraguay","Perú","Polonia","Portugal","Reino Unido","República Centroafricana","República Checa","República del Congo","República Democrática del Congo","República Dominicana","Ruanda","Rumanía","Rusia","Samoa","San Cristóbal y Nieves","San Marino","San Vicente y las Granadinas","Santa Lucía","Santo Tomé y Príncipe","Senegal","Serbia","Seychelles","Sierra Leona","Singapur","Siria","Somalia","Sri Lanka","Sudáfrica","Sudán","Sudán del Sur","Suecia","Suiza","Surinam","Tailandia","Tanzania","Tayikistán","Timor Oriental","Togo","Tonga","Trinidad y Tobago","Túnez","Turkmenistán","Turquía","Tuvalu","Ucrania","Uganda","Uruguay","Uzbekistán","Vanuatu","Vaticano","Venezuela","Vietnam","Yemen","Yibuti","Zambia","Zimbabue"
            ],
            'Espania' => [
                "Andalucía" => ["Almería", "Cádiz", "Córdoba", "Granada", "Huelva", "Jaén", "Málaga", "Sevilla"],
                "Aragón" => ["Huesca", "Teruel", "Zaragoza"],
                "Asturias" => ["Asturias"],
                "Islas Baleares" => ["Baleares"],
                "Canarias" => ["Las Palmas", "Santa Cruz de Tenerife"],
                "Cantabria" => ["Cantabria"],
                "Castilla-La Mancha" => ["Albacete", "Ciudad Real", "Cuenca", "Guadalajara", "Toledo"],
                "Castilla y León" => ["Ávila", "Burgos", "León", "Palencia", "Salamanca", "Segovia", "Soria", "Valladolid", "Zamora"],
                "Cataluña" => ["Barcelona", "Girona", "Lleida", "Tarragona"],
                "Extremadura" => ["Badajoz", "Cáceres"],
                "Galicia" => ["A Coruña", "Lugo", "Ourense", "Pontevedra"],
                "Madrid" => ["Madrid"],
                "Murcia" => ["Murcia"],
                "Navarra" => ["Navarra"],
                "La Rioja" => ["La Rioja"],
                "País Vasco" => ["Álava", "Guipúzcoa", "Vizcaya"],
                "Comunidad Valenciana" => ["Alicante", "Castellón", "Valencia"],
                "Ceuta y Melilla" => ["Ceuta", "Melilla"]
            ]
        ]);
    }
}
?>