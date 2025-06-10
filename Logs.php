<?php

    namespace Src\App;

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * Clase encargada de mostrar y crear logs de headers y apache
     */
    class Logs {

        use GestorStrings;
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Instancia privada del objeto Logs 
         * @private
         * @static
         * @var ?Logs
         * @default null
         */
        private static ?Logs $instance = null;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Variable con el archivo de log para las request recibidas
         * @private
         * @var string
         */
        private string $logFile;

        /**
         * Array con todos los logs obtenidos de apache
         * @private
         * @var array
         * @default []
         * @author Sxcram02 ms2d0v4@gmail.com
         */
        private array $logs = [];
        /**
         * Array con todos los header recibidos 
         * @private
         * @var array
         * @default []
         * @author Sxcram02 ms2d0v4@gmail.com
         */
        private $headers = [];
        /**
         * Total de request registradas en ejecución
         * @private
         * @var int
         * @default 0
         * @author Sxcram02 ms2d0v4@gmail.com
         */
        private $totalRequests = 0;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Ruta donde se situarán los logs
         * @private
         * @var string
         * @default 'src/logs'
         */
        private $logPath = 'src/logs';


        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Megabytes maximos por archivo log
         * @private
         * @var float|int
         * @default 5
         */
        private float|int $maxFileSize = 5 * 1024 * 1024;

        private int $logsTotales;
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * __construct
         * Método encargado de crear la ruta de logs si no existe y setea la variable logFile con el archivo
         * @param  string $filename
         * @return void
         */
        private function __construct(string $filename = 'request.log'){
            if (!is_dir($this->logPath)) {
                mkdir($this->logPath, 0755, true);
            }
            $this->logFile = $this->logPath . '/' . $filename;
            $this->getApacheLogs();

        }
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @static
         * getInstance
         * Método que asegura una única instancia de la clase Logs
         * @return Logs
         */
        public static function getInstance(): Logs {
            if (self::$instance === null) {
                self::$instance = new Logs();
            }
            return self::$instance;
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * getLogs
         * Método encargado de obtener un array de arrays con un array con los datos de cada log recibe como parametro, el número de página
         * @param  ?int $pagina
         * @default null
         * @return array
         */
        public function getLogs(?int $pagina = null):array {
            $estadisticasPersonalizadas = $this->logs;
            if(isset($pagina)){
                $indice = $pagina <= 0 ? 0 : $pagina - 1;
                $estadisticasPersonalizadas = $estadisticasPersonalizadas[$indice];
            }

            return $estadisticasPersonalizadas;
        }
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * getApacheLogs
         * Método encargado de obtener el contenido de los logs de apache separarlos, y formatear cada log en un array personalizado de log y setea los log con un array con lod datos del log
         * @return void
         */
        private function getApacheLogs(bool $paginado = true): void  {
            $logs = $this -> obtenerLogsArchivo('C:/xampp/htdocs/contratame/src/logs/request.log');
            $logs = $this -> formatearTextoLogs($logs);
            $totalLogs = count($logs);
            $this -> logsTotales = $totalLogs;
            
            foreach($logs as $indice => $log){
                $logs[$indice] = [
                    'num' => $indice + 1,
                    'ip_remota' => $log[0] ?? "",
                    'fecha' => $log[1] ?? "",
                    'metodo' => $log[2] ?? "",
                    'URI' => $log[3] ?? "",
                    'protocolo' => $log[4] ?? "",
                    'codigo' => $log[5] ?? "",
                    'pid' => $log[6] ?? "",
                    'URL' => $log[7] ?? "",
                    'agente' => $log[8] ?? ""
                ];
            }
            
            $paginas = [];
            $pagina = [];
            $logPuestoPorPagina = 0;
            $indice = 0;
            $limiteLogsPagina = 10;

            while($indice < $totalLogs){
                if($logPuestoPorPagina == $limiteLogsPagina){
                    $logPuestoPorPagina = 0;
                    $paginas[] = $pagina;
                    $pagina = [];
                }else{
                    $pagina[] = $logs[$indice];
                    $logPuestoPorPagina++;
                }
                $indice++;
            }

            $this->logs = array_reverse($paginas);
        }

        private function filtrarLog(string $filtro,string $valor){
            $filtros = ['ip_remota','metodo','codigo','URI'];
            $logsActuales = $this -> logs;
            $logsFiltrados = [];

            if(!in_array($filtro,$filtros)){
                return [$filtro];
            }

            foreach($logsActuales as $paginaLog){
                foreach($paginaLog as $log){
                    // Escapar caracteres raros
                    $valorLog = $log[$filtro];

                    if(isset($valorLog) && !empty($valorLog) && 
                    self::existenCoincidencias("/\b$valorLog\b/i",$valor)){
                        $logsFiltrados[] = $log;
                    }
                }
            }

            return array_reverse($logsFiltrados);
        }


        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * logRequest
         * Método encargado de crear un log personalizado de la última request lanzada al servidor y lo escribe en el archivo de logFile
         * @return void
         */
        public function logRequest(): void {
            $this->totalRequests++;

            $headers = getallheaders();
            $this->headers[] = $headers;

            $clientIp = $_SERVER['REMOTE_ADDR'] ?? '-';
            $date = date('d/M/Y:H:i:s O');
            $method = $_SERVER['REQUEST_METHOD'] ?? '-';
            $uri = $_SERVER['REQUEST_URI'] ?? '-';
            $protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '-';
            $statusCode = http_response_code();

            $logEntry = sprintf(
                '%s - - [%s] "%s %s %s" %d "%s"',
                $clientIp,
                $date,
                $method,
                $uri,
                $protocol,
                $statusCode,
                $userAgent
            );

            $this->rotateLogIfNeeded();
            $this->writeToFile($logEntry);
        }
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * getHeaders
         * Método encargado de retornar los headers logeados
         * @return array
         */
        public function getHeaders(): array {
            return $this->headers;
        }


        public function countLogs(): int {
            return $this -> logsTotales;
        }

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * getTotalRequests
         * Método que obtien todas las request registradas
         * @return int
         */
        public function getTotalRequests(): int {
            return $this->totalRequests;
        }
        public function filtrarIp(string $valor){
            return $this -> filtrarLog('ip_remota',$valor);
        }

        public function filtrarUri(string $valor){
            return $this -> filtrarLog('URI',$valor);
        }
        public function filtrarMetodo(string $valor){
            return $this -> filtrarLog('metodo',$valor);
        }

        public function filtrarCodigo(string $valor){
            return $this -> filtrarLog('codigo',$valor);
        }

        public function filtrarTexto(string $valor){
            $logsActuales = $this -> logs;
            $logs = [];
            foreach($logsActuales as $paginaLog){
                foreach($paginaLog as $log){
                    $logs[] = $log;
                }
            }

            return $logs;
        }
        
        /**
         * @private
         * writeToFile
         * Método encargado de escribir en el archivo la cadena pasa por parametro al final del archivo
         * @param  mixed $line
         * @return void
         * @author Sxcram02 ms2d0v4@gmail.com
         */
        private function writeToFile($line): void {
            file_put_contents($this->logFile, $line . PHP_EOL, FILE_APPEND);
        }
        
        /**
         * @private
         * rotateLogIfNeeded
         * Método encargado de crear un nuevo archivo de log en caso de que supere la cantidad maxima
         * @return void
         * @author Sxcram02 ms2d0v4@gmail.com
         */
        private function rotateLogIfNeeded(): void{
            if (file_exists($this->logFile) && filesize($this->logFile) > $this->maxFileSize) {
                $timestamp = date('Ymd_His');
                rename($this->logFile, $this->logPath . "/request_$timestamp.log");
            }
        }
        
        /**
         * obtenerLogsArchivo
         * Método que recibe una ruta de un archivo con logs y si su tamaño es mayor a 1.5 MB lo ira leyendo por partes y retornara un texto con los logs o un texto vacio.
         * @param  string $archivo - ruta de archivo
         * @return string
         */
        private function obtenerLogsArchivo(string $archivo): string {
            if(!file_exists($archivo)){
                return "";
            }

            $bytes = filesize($archivo);
            $kiloBytes = $bytes / 1024;
            $megaBytesArchivo = $kiloBytes / 1024;

            $bytesMaximosArchivo = $megaBytesArchivo >= 1.5 ? $megaBytesArchivo - ($megaBytesArchivo / 2) : $megaBytesArchivo; 
            
            $bytesMaximosArchivo *= 1024;
            $bytesMaximosArchivo *= 1024;

            $texto = "";
            if ($megaBytesArchivo >= 1.5) {
                $bytesLeidos = 0;
                $bytesAleer = 4096;
                $gestor = fopen($archivo,'ra');

                while($bytesLeidos < $bytesMaximosArchivo){
                    $texto .= fread($gestor,$bytesAleer);
                    $bytesLeidos += $bytesAleer;
                }

                fclose($gestor);
            } else {
                $texto .= file_get_contents($archivo);
            }

            return $texto;
        }
        
        /**
         * formatearTextoLogs
         * Método que recibe el texto de los logs con el formato de apache y lo separará por log y formateara cada log con un formato propio retorna un array de logs formateados
         * @param  string $logs
         * @return array
         */
        private function formatearTextoLogs(string $logs): array|bool{
            
            $logs = preg_replace('/\-\s\-/', '', $logs);
            $regex ="/^(.*)\s*\[(.*)\]\s*\"(.*)\s+(.*)\s+(.*)\"\s*(\d+)\s*\"(.*)\"\s*(.*)/";

            $logs = preg_replace('/(?!^)(?=\b[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+\b)/', '??', $logs);
            $logs = preg_split('/\?\?/',$logs);

            foreach($logs as $indice => $log){
                $log = preg_replace($regex,"$1=$2=$3=$4=$5=$6=$7=$8=$9",$log);
                $log = preg_replace("/\s{2,}/","",$log);

                $log = preg_split("/\=/",$log);
                // Tiene la información mínima
                if(count($log) >= 9){
                    $logs[$indice] = $log;
                }
            }

            return empty($logs) ? [] : $logs;
        }
    }
?>