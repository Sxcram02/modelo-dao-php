<?php
    namespace Src\App;
    use DateInterval;
    use DateTime;
    
    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * Clase encargada de gestionar cualquier tipo de fecha
     * @todo implementación de diferencia de semanas
     */
    class Fecha {
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Objeto DateTime nativo de php con la fecha Actual del sistema
         * @var DateTime
         */
        public DateTime $fechaActual;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Objeto DateTime con la fecha instanciada
         * @var DateTime
         */
        public DateTime $fecha;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Booleno que determina si paso un minuto y mas de 60 seg
         * @static
         * @var bool
         */
        public static bool $pasoUnMinuto = false;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Booleno que determina si paso una hora y mas de 60 min
         * @static
         * @var bool
         */
        public static bool $pasoUnaHora = false;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Booleno que determina si paso un día y mas de 24 horas
         * @static
         * @var bool
         */
        public static bool $pasoUnDia = false;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Booleno que determina si paso un mes y mas de 29 días
         * @static
         * @var bool
         * @todo Comprobar meses con febrero o los impares
         */
        public static bool $pasoUnMes = false;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Booleno que determina si paso un anio y mas de 11 meses 
         * @static
         * @var bool
         */
        public static bool $pasoUnAnio = false;
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * __construct
         * Método constructor que recibe una fecha y seteada las variables fecha y fechaActual
         * @param  string $fecha
         * @return void
         */
        public function __construct(string $fecha){
            $fecha = new DateTime($fecha);
            $fechaActual = new DateTime();

            $this -> fechaActual = $fechaActual;
            $this -> fecha = $fecha;
        }
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * getDiferencia
         * Método que retorna un objeto con las diferencias de seg, min, hrs, dias, mes, anio de la fecha actual
         * @return DateInterval
         */
        private function getDiferencia(): DateInterval{
            return $this -> fechaActual -> diff($this -> fecha);
        }
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * getDiffSegundos
         * Método encargado de obtener una cadena con la diferencia en segundos de la fecha instancia y el dia de hoy
         * @return string
         */
        private function getDiffSegundos(): string{
            $diferencia = $this -> getDiferencia();
            $diferenciaSegundos = $diferencia -> s;
            $pasoUnSegundo = $diferenciaSegundos > 0;
            return $pasoUnSegundo ? "$diferenciaSegundos segundos" : "$diferenciaSegundos segundo";
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * getDiffMinutos
         * Método encargado de obtener una cadena con la diferencia en minutos de la fecha instancia y el dia de hoy
         * @return string
         */
        private function getDiffMinutos(): string{
            $diferencia = $this -> getDiferencia();
            $diferenciaMinutos = $diferencia -> i;
            $pasoUnMinuto = $diferenciaMinutos > 0;

            if($pasoUnMinuto && $diferencia -> s >= 59){
                self::$pasoUnMinuto = true;
            }
            return $pasoUnMinuto > 1 ? "$diferenciaMinutos minutos" : "$diferenciaMinutos minuto";
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * getDiffHoras
         * Método encargado de obtener una cadena con la diferencia en horas de la fecha instancia y el dia de hoy
         * @return string
         */
        private function getDiffHoras(): string{
            $diferencia = $this -> getDiferencia();
            $diferenciaHoras = $diferencia -> h;
            $pasoUnaHora = $diferenciaHoras > 0;

            if($pasoUnaHora && $diferencia -> m >= 59){
                self::$pasoUnaHora = true;
            }

            return $pasoUnaHora > 1 ? "$diferenciaHoras horas" : "$diferenciaHoras hora";
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * getDiffDias
         * Método encargado de obtener una cadena con la diferencia en dias de la fecha instancia y el dia de hoy
         * @return string
         */
        private function getDiffDias(): string{
            $diferencia = $this -> getDiferencia();
            $diferenciaDias = $diferencia -> d;
            $pasoUnDia = $diferenciaDias > 0;

            if($pasoUnDia && $diferencia -> h >= 24){
                self::$pasoUnMes = true;
            }

            return $pasoUnDia > 1 ? "$diferenciaDias días" : "$diferenciaDias día";
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * getDiffSemanas
         * Método encargado de obtener una cadena con la diferencia en semanas de la fecha instancia y el dia de hoy
         * @todo imlpementar busqueda
         * @return string
         */
        private function getDiffSemanas(): string{
            $diferencia = $this -> getDiferencia();
            return "";
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * getDiffMeses
         * Método encargado de obtener una cadena con la diferencia en meses de la fecha instancia y el dia de hoy
         * @return string
         */
        private function getDiffMeses(): string{
            $diferencia = $this -> getDiferencia();
            $diferenciaMeses = $diferencia -> m;
            $pasoUnMes = $diferenciaMeses > 0;

            if($pasoUnMes && $diferencia -> d >= 29){
                self::$pasoUnMes = true;
            }

            return $pasoUnMes > 1 ? "$diferenciaMeses meses" : "$diferenciaMeses mes";
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * getDiffAnios
         * Método encargado de obtener una cadena con la diferencia en años de la fecha instancia y el dia de hoy
         * @return string
         */
        private function getDiffAnios(): string{
            $diferencia = $this -> getDiferencia();
            $diferenciaAnios = $diferencia -> y;
            $pasoUnAnio = $diferenciaAnios > 0;

            if($pasoUnAnio > 0 && $diferencia -> m >= 12){
                self::$pasoUnAnio = true;
            }

            return $pasoUnAnio > 1 ? "$diferenciaAnios años" : "$diferenciaAnios año";
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * obtenerDiferencia
         * Método que determina de forma automatica si paso un dia para mostrar los diferencia de dias y no la de meses
         * @return string
         */
        public function obtenerDiferencia(): string {
            if(self::$pasoUnAnio){
                return $this -> getDiffAnios();
            }

            if(self::$pasoUnMes){
                return $this -> getDiffMeses();
            }

            if(self::$pasoUnDia){
                return $this -> getDiffDias();
            }

            if(self::$pasoUnaHora){
                return $this -> getDiffHoras();
            }

            if(self::$pasoUnMinuto){
                return $this -> getDiffMinutos();
            }

            return $this -> getDiffSegundos();
        }
    }
?>