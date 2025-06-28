<?php

namespace Src\App;

use DateInterval;
use DateTime;

/**
 * Clase encargada de gestionar cualquier tipo de fecha
 * @author Sxcram02 <ms2d0v4@gmail.com>
 * @todo implementación de diferencia de semanas
 */
class Fecha {
    /**
     * Objeto DateTime nativo de php con la fecha Actual del sistema
     * @var DateTime
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public DateTime $fechaActual;

    /**
     * Objeto DateTime con la fecha instanciada
     * @var DateTime
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public DateTime $fecha;

    /**
     * Booleno que determina si paso un minuto y mas de 60 seg
     * @var bool
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public static bool $pasoUnMinuto = false;

    /**
     * Booleno que determina si paso una hora y mas de 60 min
     * @var bool
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public static bool $pasoUnaHora = false;

    /**
     * Booleno que determina si paso un día y mas de 24 horas
     * @var bool
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public static bool $pasoUnDia = false;

    /**
     * Booleno que determina si paso un mes y mas de 29 días
     * @var bool
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
     * @todo Comprobar meses con febrero o los impares
     */
    public static bool $pasoUnMes = false;

    /**
     * Booleno que determina si paso un anio y mas de 11 meses 
     * @var bool
     * @static
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public static bool $pasoUnAnio = false;

    /**
     * __construct
     * Método constructor que recibe una fecha y seteada las variables fecha y fechaActual
     * @param  string $fecha
     * @return void
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function __construct(string $fecha) {
        $fecha = new DateTime($fecha);
        $fechaActual = new DateTime();

        $this->fechaActual = $fechaActual;
        $this->fecha = $fecha;
    }

    /**
     * getDiferencia
     * Método que retorna un objeto con las diferencias de seg, min, hrs, dias, mes, anio de la fecha actual
     * @return DateInterval
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private function getDiferencia(): DateInterval {
        return $this->fechaActual->diff($this->fecha);
    }

    /**
     * getDiffSegundos
     * Método encargado de obtener una cadena con la diferencia en segundos de la fecha instancia y el dia de hoy
     * @return string
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private function getDiffSegundos(): string {
        $diferencia = $this->getDiferencia();
        $diferenciaSegundos = $diferencia->s;
        $pasoUnSegundo = $diferenciaSegundos > 0;
        return $pasoUnSegundo ? "$diferenciaSegundos segundos" : "$diferenciaSegundos segundo";
    }

    /**
     * getDiffMinutos
     * Método encargado de obtener una cadena con la diferencia en minutos de la fecha instancia y el dia de hoy
     * @return string
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private function getDiffMinutos(): string {
        $diferencia = $this->getDiferencia();
        $diferenciaMinutos = $diferencia->i;
        $pasoUnMinuto = $diferenciaMinutos > 0;

        if ($pasoUnMinuto && $diferencia->s >= 59) {
            self::$pasoUnMinuto = true;
        }
        return $pasoUnMinuto > 1 ? "$diferenciaMinutos minutos" : "$diferenciaMinutos minuto";
    }

    /**
     * getDiffHoras
     * Método encargado de obtener una cadena con la diferencia en horas de la fecha instancia y el dia de hoy
     * @return string
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private function getDiffHoras(): string {
        $diferencia = $this->getDiferencia();
        $diferenciaHoras = $diferencia->h;
        $pasoUnaHora = $diferenciaHoras > 0;

        if ($pasoUnaHora && $diferencia->m >= 59) {
            self::$pasoUnaHora = true;
        }

        return $pasoUnaHora > 1 ? "$diferenciaHoras horas" : "$diferenciaHoras hora";
    }

    /**
     * getDiffDias
     * Método encargado de obtener una cadena con la diferencia en dias de la fecha instancia y el dia de hoy
     * @return string
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private function getDiffDias(): string {
        $diferencia = $this->getDiferencia();
        $diferenciaDias = $diferencia->d;
        $pasoUnDia = $diferenciaDias > 0;

        if ($pasoUnDia && $diferencia->h >= 24) {
            self::$pasoUnMes = true;
        }

        return $pasoUnDia > 1 ? "$diferenciaDias días" : "$diferenciaDias día";
    }

    /**
     * getDiffSemanas
     * Método encargado de obtener una cadena con la diferencia en semanas de la fecha instancia y el dia de hoy
     * @return string
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     * @todo imlpementar busqueda
     */
    private function getDiffSemanas(): string {
        $diferencia = $this->getDiferencia();
        return "";
    }

    /**
     * getDiffMeses
     * Método encargado de obtener una cadena con la diferencia en meses de la fecha instancia y el dia de hoy
     * @return string
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private function getDiffMeses(): string {
        $diferencia = $this->getDiferencia();
        $diferenciaMeses = $diferencia->m;
        $pasoUnMes = $diferenciaMeses > 0;

        if ($pasoUnMes && $diferencia->d >= 29) {
            self::$pasoUnMes = true;
        }

        return $pasoUnMes > 1 ? "$diferenciaMeses meses" : "$diferenciaMeses mes";
    }

    /**
     * getDiffAnios
     * Método encargado de obtener una cadena con la diferencia en años de la fecha instancia y el dia de hoy
     * @return string
     * @private
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    private function getDiffAnios(): string {
        $diferencia = $this->getDiferencia();
        $diferenciaAnios = $diferencia->y;
        $pasoUnAnio = $diferenciaAnios > 0;

        if ($pasoUnAnio > 0 && $diferencia->m >= 12) {
            self::$pasoUnAnio = true;
        }

        return $pasoUnAnio > 1 ? "$diferenciaAnios años" : "$diferenciaAnios año";
    }

    /**
     * obtenerDiferencia
     * Método que determina de forma automatica si paso un dia para mostrar los diferencia de dias y no la de meses
     * @return string
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function obtenerDiferencia(): string {
        if (self::$pasoUnAnio) {
            return $this->getDiffAnios();
        }

        if (self::$pasoUnMes) {
            return $this->getDiffMeses();
        }

        if (self::$pasoUnDia) {
            return $this->getDiffDias();
        }

        if (self::$pasoUnaHora) {
            return $this->getDiffHoras();
        }

        if (self::$pasoUnMinuto) {
            return $this->getDiffMinutos();
        }

        return $this->getDiffSegundos();
    }


    /**
     * formatearFecha
     * Método encargado de formatear la fecha al idioma español y con un formato de "Miercoles, 12 de Abril de 2024"
     * @return string
     * @author Sxcram02 <ms2d0v4@gmail.com>
     */
    public function formatearFecha() {
        $fechaFormateada = $this->fecha->format('D,d,M,Y');
        $fechaFormateada = explode(',', $fechaFormateada);

        $nombreDia = match ($fechaFormateada[0]) {
            "Mon" => "Lunes",
            "Tue" => "Martes",
            "Wed" => "Miércoles",
            "Thu" => "Jueves",
            "Fri" => "Viernes",
            "Sat" => "Sábado",
            "Sun" => "Domingo",
            default => ""
        };

        $nombreMes = match ($fechaFormateada[2]) {
            "Jan" => "Enero",
            "Feb" => "Febrero",
            "Mar" => "Marzo",
            "Apr" => "Abril",
            "May" => "Mayo",
            "Jun" => "Junio",
            "Jul" => "Julio",
            "Ago" => "Agosto",
            "Sep" => "Septiembre",
            "Nov" => "Noviembre",
            "Dec" => "Diciembre",
            default => ""
        };

        return "$nombreDia, $fechaFormateada[1] de $nombreMes en $fechaFormateada[3]";
    }
}
?>