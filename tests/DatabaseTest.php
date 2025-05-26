<?php
    declare(strict_types=1);
    namespace Src\App\Tests;

    require_once __DIR__ . '/../GestorStrings.php';
    require_once __DIR__ . '/../GestorRutas.php';
    require_once __DIR__ . '/../Singelton.php';
    require_once __DIR__ . '/../Consulta.php';
    require_once __DIR__ . '/../Database.php';

    use PDOStatement;
    use Src\App\Database;
    use PHPUnit\Framework\TestCase;
    use PHPUnit\Framework\Attributes\Test;
    use PHPUnit\Framework\Attributes\TestDox;
    
    final class DatabaseTest extends TestCase {

        #[Test]
        #[TestDox('Prueba de la conexion con la base de datos')]
        public function conectar() {
            $database = Database::getInstance();

            $this -> assertInstanceOf(Database::class,$database,"No se realizo la conexión correctamente");
        }

        #[Test]
        #[TestDox('Prueba del una consulta simple de select')]

        public function select(){
            $database = Database::getInstance();
            $pdoStatment = $database -> query('SELECT * FROM usuarios');

            $this -> assertInstanceOf(PDOStatement::class,$pdoStatment,"La consulta tiene un error en la sintaxis");

            $this -> assertIsArray($pdoStatment -> fetchAll(),"No existen resultados para la consulta select");
        }
    }
?>