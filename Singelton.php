<?php
    namespace Src\App;
    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * Singelton
     * Interfaz que se encargará de implementar el patrón de diseño Singelton.
     */
    interface Singelton {        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @static
         * getInstance
         * Método que se encargará de instanciar un objeto de la clase que implemente la interfaz Singelton.
         * @return object
         */
        public static function getInstance() :?object;
    }
?>