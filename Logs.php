<?php

    namespace Src\App;

    /**
     * @author Sxcram02 ms2d0v4@gmail.com
     * Clase encargada de mostrar y crear logs de headers y apache
     */
    class Logs {
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
         * @author Sxcram02 ms2d0v4@gmail.com
         * Array con todos los logs obtenidos de apache
         * @private
         * @var array
         * @default []
         */
        private array $logs = [];
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Array con todos los header recibidos 
         * @private
         * @var array
         * @default []
         */
        private $headers = [];
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Total de request registradas en ejecución
         * @private
         * @var int
         * @default 0
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
         * úmero que almacena el número total de logs en el archivo access.log
         * @private
         * @var int
         * @default 0
         */
        private $totalDeLogs = 0;

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * Megabytes maximos por archivo log
         * @private
         * @var float|int
         * @default 5
         */
        private float|int $maxFileSize = 5 * 1024 * 1024;
        
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
        private function getApacheLogs(): void  {
            $estadisticasPersonalizadas = [];
            $pagina = [];

            $bytes = filesize('C:\xampp\apache\logs\access.log');
            $kiloBytes = $bytes / 1024;
            $megaBytesArchivo = $kiloBytes / 1024;

            $estadisticas = "";
            if ($megaBytesArchivo > 5) {
                $megaBytesAleer = 1024**2;
                $megaBytesLeidos = 0;
                $gestor = fopen('C:\xampp\apache\logs\access.log','rb');

                while($megaBytesLeidos < $megaBytesArchivo){
                    $estadisticas .= fread($gestor,$megaBytesAleer);
                    $megaBytesLeidos += $megaBytesAleer;
                }

                fclose($gestor);
            } else {
                $estadisticas .= file_get_contents('C:\xampp\apache\logs\access.log');
            }

            $estadisticas = preg_replace('/\-\s\-/', '', $estadisticas);
            $estadisticas = preg_replace('/(?!^)(?=\b[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+\b)/', '??', $estadisticas);
            $estadisticas = preg_split('/\?\?/',$estadisticas);

            foreach($estadisticas as $indice => $log){
                $log = preg_replace("/(.+)?\s+\[(.+)\]\s+\"(.+)\s+(.+)\s+(.+)\"\s+(.+)\s+(.+)\s+\"(.+)?\"\s+\"(.+)?\"/","$1=$2=$3=$4=$5=$6=$7=$8=$9",$log);
                $estadisticas[$indice] = $log;
            }
        

            $logsPersonalizadosPuestos = 0;
            $indice = 0;
            $limiteLogsPagina = 10;
            $totalLogs = count($estadisticas);
            $this -> totalDeLogs = $totalLogs;

            do{
                $log = $estadisticas[$indice];
                $logArray = preg_split('/\=/',$log);

                $logPersonalizado = [
                    'num' => $indice + 1,
                    'ip_remota' => $logArray[0],
                    'fecha' => $logArray[1],
                    'metodo' => $logArray[2],
                    'URI' => $logArray[3],
                    'protocolo' => $logArray[4],
                    'codigo' => $logArray[5],
                    'pid' => $logArray[6],
                    'URL' => $logArray[7],
                    'agente' => $logArray[8]
                ];

                if($totalLogs <= $limiteLogsPagina){
                    $estadisticasPersonalizadas[] = $logPersonalizado;
                    $logsPersonalizadosPuestos++;
                    $indice++;
                }else{
                    if(count($pagina) >= $limiteLogsPagina){
                        $estadisticasPersonalizadas[] = $pagina;
                        $pagina = [];
                    }else{
                        $pagina[] = $logPersonalizado;
                        $logsPersonalizadosPuestos++;
                        $indice++;
                    }
                } 
            }while($logsPersonalizadosPuestos != $totalLogs);

            $this->logs = $estadisticasPersonalizadas;
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
                    existenCoincidencias("/\b$valorLog\b/i",$valor)){
                        $logsFiltrados[] = $log;
                    }
                }
            }

            return $logsFiltrados;
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

        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * getTotalRequests
         * Método que obtien todas las request registradas
         * @return int
         */
        public function getTotalRequests(): int {
            return $this->totalRequests;
        }
        public function countLogs():int {
            return $this -> totalDeLogs;
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
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * writeToFile
         * Método encargado de escribir en el archivo la cadena pasa por parametro al final del archivo
         * @param  mixed $line
         * @return void
         */
        private function writeToFile($line): void {
            file_put_contents($this->logFile, $line . PHP_EOL, FILE_APPEND);
        }
        
        /**
         * @author Sxcram02 ms2d0v4@gmail.com
         * @private
         * rotateLogIfNeeded
         * Método encargado de crear un nuevo archivo de log en caso de que supere la cantidad maxima
         * @return void
         */
        private function rotateLogIfNeeded(): void{
            if (file_exists($this->logFile) && filesize($this->logFile) > $this->maxFileSize) {
                $timestamp = date('Ymd_His');
                rename($this->logFile, $this->logPath . "/request_$timestamp.log");
            }
        }
    }
?>