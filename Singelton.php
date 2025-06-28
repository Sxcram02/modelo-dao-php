<?php

namespace Src\App;

/**
 * Singelton
 * Interfaz que se encargará de implementar el patrón de diseño Singelton.
 * @author Sxcram02 <ms2d0v4@gmail.com>
 */
interface Singelton
{
    /**
     * getInstance
     * Método que se encargará de instanciar un objeto de la clase que implemente la interfaz Singelton.
     * @return object
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public static function getInstance(): ?object;
}
