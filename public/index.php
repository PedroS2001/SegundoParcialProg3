<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../src/poo/Usuario.php';
require_once __DIR__ . '/../src/poo/Auto.php';
require_once __DIR__ . '/../src/poo/Middlewares.php';



$app = AppFactory::create();



###########################################################################################################

$app->post('/usuarios', Usuario::class . ':AgregarUsuario')->add(MW::class . '::ValidarCorreoEnLaBD')->add(MW::class . '::ValidarDatosVaciosUsuario')->add(MW::class . ':ValidarDatosSeteadosUsuario');

$app->get('/', Usuario::class . ':MostrarTodosLosUsuarios' )->add(MW::class . ':AccedePropietarioB')->add(MW::class . ':AccedeEncargadoB')->add(MW::class . ':AccedeEmpleadoB');

$app->post('/', Auto::class . ':AgregarAuto')->add(MW::class . ':VerificarAuto');

$app->get('/autos[/{id}]', Auto::class . ':MostrarTodosLosAutos')->add(MW::class . '::AccedePropietario')->add(MW::class . ':AccedeEmpleado')->add(MW::class . ':AccedeEncargado');

$app->post('/login', Usuario::class . ':LoginPost')->add(MW::class . ':ValidarDatosEnLaBD')->add(MW::class . '::ValidarDatosVaciosUsuario')->add(MW::class . ':ValidarDatosSeteadosUsuario');

$app->get('/login', Usuario::class . ':LoginGet');

#PARTE 3
$app->delete('/', Auto::class . ':EliminarAuto');//->add(MW::class . '::VerificarPropietario')->add(MW::class . ':VerificarTokenValido');

$app->put('/', Auto::class . ':ModificarAuto')->add(MW::class . ':VerificarEncargado')->add(MW::class . ':VerificarTokenValido');
###########################################################################################################

$app->run();
