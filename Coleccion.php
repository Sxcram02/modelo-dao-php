<?php

namespace Src\App;

/**
 * Coleccion
 * Clase encargada de agrupar y gestionar varios Modelos
 * @author Sxcram02 <ms2d0v4@gmail.com>
 */
class Coleccion {
    /**
     * Array con todos los modelos creados
     * @var array
     * @default []
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private array $modelos = [];

    /**
     * Número total de modelos en la Colección
     * @var int
     * @default 0
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private int $numModelos = 0;

    /**
     * Clase tomada por referencia para la coleccion, es decir Coleccion de Modelo::class
     * @var string
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private string $clase;

    /**
     * __construct
     * Método que recibe un array de registros con datos y una clase de Modelo sobre la que se crear un modelo con los datos
     * @param  array $registros
     * @param  string $clase
     * @return void
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function __construct(array $registros, string $clase) {
        $this->clase = $clase;
        foreach ($registros as $registro) {
            $this->modelos[] = $clase::createModel($registro);
        }
        $this->numModelos = count($this->modelos);
    }

    /**
     * where
     * Método encargado ejecutar un where en base a la consulta ralizada anteriormente
     * @param  array|string $condicionado
     * @param  array|string $operador
     * @param  mixed $condicion
     * @return object
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function where(array|string $condicionado, array|string $operador, mixed $condicion): object {
        return $this->modelos[0]->where($condicionado, $operador, $condicion);
    }

    /**
     * join
     * Método encargado ejecutar un join en base a la consulta ralizada anteriormente
     * @param  string $tablaUnida
     * @param  array|string $clavesForaneas
     * @return object
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function join(string $tablaUnida, array|string $clavesForaneas): object {
        return $this->modelos[0]->join($tablaUnida, $clavesForaneas);
    }


    /**
     * getModelsArray
     *  Método que retorna el array de modelos
     * @return array
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function getModelsArray(): array {
        return $this->modelos;
    }

    /**
     * array
     * Método que retorna un array de arrays con los datos de los modelos
     * @return array
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function array(): array {
        $modelos = [];
        foreach ($this->modelos as $modelo) {
            $modelos[] = $modelo->array();
        }
        return $modelos;
    }

    /**
     * isColeccion
     * Método encargado de comprobar que la instancia actual es una Colección
     * @return bool
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function isColeccion(): bool {
        return $this instanceof Coleccion;
    }

    /**
     * paginate
     * Método que recibe la pagina solicitada para ver y los elementos deseados por cada pagina retornara la pagina x con los registros y
     * @param  int $numPagina
     * @default 1
     * @param  int $elementosPorPagina
     * @default 4
     * @return object
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function paginate(int $numPagina = 1, int $elementosPorPagina = 4): object {
        $modelos = $this->getModelsArray();
        $elementosTotales = count($modelos);
        if ($elementosTotales < $elementosPorPagina) {
            $elementosPorPagina = $elementosTotales;
        }

        $numPaginas = $elementosTotales / $elementosPorPagina;
        $resto = $elementosTotales % $elementosPorPagina;
        $numPaginas = $resto > 0 ? intval($numPaginas) + 1 : $numPaginas;

        $paginasHechas = $indice = $elementosPuestosPorPagina = 0;
        $paginas = $pagina = [];

        do {
            if ($elementosPuestosPorPagina < $elementosPorPagina && $indice < $elementosTotales) {
                $pagina[] = $modelos[$indice];
                $indice++;
                $elementosPuestosPorPagina++;
            } else {
                $paginas[] = $pagina;
                $pagina = [];
                $elementosPuestosPorPagina = 0;
                $paginasHechas++;
            }
        } while ($paginasHechas != $numPaginas);

        $indice = $numPagina <= 0 ? $numPagina + 1 : $numPagina - 1;
        $this->modelos = ($indice > count($paginas) - 1) ? []
            : $paginas[$indice];
        return $this;
    }

    /**
     * json
     * Método encargado de retornar un json de un array de arrays con los datos de los modelos
     * @return bool|string
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function json(): bool|string {
        return json_encode($this->array(), JSON_PRETTY_PRINT);
    }
}
?>