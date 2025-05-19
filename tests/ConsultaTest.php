<?php
    declare(strict_types=1);
    namespace Src\App\Tests;

    require_once __DIR__ . '/../GestorStrings.php';
    require_once __DIR__ . '/../Consulta.php';

    use PDOStatement;
    use Src\App\Consulta;
    use PHPUnit\Framework\TestCase;
    use PHPUnit\Framework\Attributes\Test;
    use PHPUnit\Framework\Attributes\TestDox;

    
    final class ConsultaTest extends TestCase {
        
        /**
         * obtenerParametrosPreparados
         * Método encargado de mostrar los parametros preparados
         * @param Consulta $consulta
         * @return void
         */
        private function obtenerParametrosPreparados(Consulta $consulta): void {
            $this -> assertNotEmpty($consulta -> parametrosPreparados,'No se encontrarón parámetros preparados');

            $parametros = "";
            foreach($consulta -> parametrosPreparados as $nombre => $parametro){
                $parametros .= "\t * El parametro $nombre cuyo valor es " . $parametro['valor'] . " y su tipo es " . $parametro['tipo'] . "\n";
            }

            echo "✅ Los parametros preparados son: \n $parametros";
        }
        
        /**
         * obtenerParametros
         * Método encargado de obtener todos los valores seteados de una columna de una tabla
         * @param Consulta $consulta
         * @return void
         */
        private function obtenerParametros(Consulta $consulta): void {
            $this -> assertNotEmpty($consulta -> parametros,'No se encontrarón parámetros');

            $parametros = "";
            foreach($consulta -> parametros as $parametro){
                $parametros .= " $parametro,";
            }

            echo "✅ Cuyos valores son: $parametros respectivamente \n";
        }
        
        /**
         * obtenerClaves
         * Método encargado de obtener todas las columnas de una tabla
         * @param Consulta $consulta
         * @return void
         */
        private function obtenerClaves(Consulta $consulta): void {
            $this -> assertNotEmpty($consulta -> claves,'No se encontrarón claves en la clausula');

            $claves = "";
            foreach($consulta -> claves as $clave){
                $claves .= " $clave,";
            }

            echo "✅ Parametros encontrados: $claves \n";
        }

        /**
         * select
         * Método encargado de comprobar una consulta select con un where simple
         * @return void
         */
        #[Test]
        #[TestDox('Prueba de la creación de una consulta select con un where con solo una columna')]
        public function select(): void {
            $consulta = "SELECT * FROM usuarios WHERE nombre LIKE %D€%";
            echo "\nLa consulta es \"$consulta\"\n";

            $consulta = new Consulta($consulta);
            $this -> obtenerClaves($consulta);
            $this -> obtenerParametros($consulta);
            $this -> obtenerParametrosPreparados($consulta);
            $consultaPreparada = $consulta -> consultaPreparada;
            echo "\n🔥 Consulta preparada: $consultaPreparada \n";
        }

        /**
         * insert
         * Método encargado de comprobar una consulta insert
         * @return void
         */
        #[Test]
        #[TestDox('Prueba de creación de una consulta insert')]
        public function insert(): void {
            $consulta = "INSERT INTO test (nombre, valor, valor2) VALUES ('test1', TRUE, 'test2)";
            echo "\nLa consulta es \" $consulta \"\n";

            $consulta = new Consulta($consulta);
            $this -> obtenerClaves($consulta);
            $this -> obtenerParametros($consulta);
            $this -> obtenerParametrosPreparados($consulta);
            $consultaPreparada = $consulta -> consultaPreparada;
            echo "\n🔥 Consulta preparada: $consultaPreparada \n";
        }

        /**
         * update
         * Método encargado de comprobar una consulta update
         * @return void
         */
        #[Test]
        #[TestDox('Prueba de creación de una consulta update con where')]        
        public function update(): void {
            $consulta = "UPDATE FROM test SET nombre = 'test1', valor = TRUE, valor2 = 'test2' WHERE testId = 376";
            echo "\nLa consulta es \" $consulta \"\n";

            $consulta = new Consulta($consulta);
            $this -> obtenerClaves($consulta);
            $this -> obtenerParametros($consulta);
            $this -> obtenerParametrosPreparados($consulta);
            $consultaPreparada = $consulta -> consultaPreparada;
            echo "\n🔥 Consulta preparada: $consultaPreparada \n";
        }
    }
?>