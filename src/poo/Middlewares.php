<?php
//MIDDLEWARES
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as ResponseMW;
use Slim\Routing\RouteContext;

require_once "AccesoDatos.php";
require_once "AutentificadoraJWT.php";
require_once "Usuario.php";

class MW
{
    public function ValidarDatosSeteadosUsuario(Request $request, RequestHandler $handler) : ResponseMW
    {
        $datos = new stdClass;
        $datos->status = 200;

        $arrayDeParametros = $request->getParsedBody();


        if( isset($arrayDeParametros['user'])  )
        {
            $objJson = $arrayDeParametros["user"] ;
        }
        else if( $arrayDeParametros['usuario']  )
        {
            $objJson = $arrayDeParametros["usuario"];
        }
        else
        {
            $objJson = null;
        }
        //$objJson = isset($arrayDeParametros['usuario'])? $arrayDeParametros['usuario'] : null;

        //verifico que este seteado el json con los datos
        if($objJson != null)
        {
            $obj = json_decode($objJson);
            $correo = isset($obj->correo)? isset($obj->correo) : null;
            $clave = isset($obj->clave)? isset($obj->clave) : null;
                
            if($correo != null && $clave != null){
                $response = $handler->handle($request);
                $datos = json_decode($response->getBody());
            }
            else if($correo != null && $clave == null){
                $datos->mensaje = "FALTA ATRIBUTO CLAVE";
                $datos->status = 403;
            }
            else if($correo == null && $clave != null){
                $datos->mensaje = "FALTA ATRIBUTO CORREO";
                $datos->status = 403;
            }
            else{
                $datos->mensaje = "FALTAN ATRIBUTOS CORREO Y CLAVE";
                $datos->status = 403;
            }
        }else{
            $datos->mensaje = "ERROR!!!!! NO SE PASO EL PARAMETRO USUARIO";
            $datos->status = 403;
        }

        //Al constructor de mw se le puede pasar el status como parametro
        $response = new ResponseMW($datos->status);

        //Escribo los datos en formato JSON.(estaba en stdClass)
        $response->getBody()->write(json_encode($datos));

        //Para avisaar que devuelvo un json
        return $response->withHeader('Content-Type', 'application/json');
    }


    public static function ValidarDatosVaciosUsuario(Request $request, RequestHandler $handler) : ResponseMW
    {
        $datos = new stdClass;
        $datos->status = 200;

        $arrayDeParametros = $request->getParsedBody();

        //se supone que esto ya esta validado en el anterior middleware
        if( isset($arrayDeParametros['user'])  )
        {
            $objJson = $arrayDeParametros["user"] ;
        }
        else
        {
            $objJson = $arrayDeParametros["usuario"];
        }

        //$objJson = $arrayDeParametros['usuario'] ;


        $obj = json_decode($objJson);
        $correo = $obj->correo;
        $clave = $obj->clave;
            
        if($correo != "" && $clave != ""){
            $response = $handler->handle($request);
            $datos = json_decode($response->getBody());
        }
        else if($correo != "" && $clave == ""){
            $datos->mensaje = "EL ATRIBUTO CLAVE ESTA VACIO";
            $datos->status = 409;
        }
        else if($correo == "" && $clave != ""){
            $datos->mensaje = "EL ATRIBUTO CORREO ESTA VACIO";
            $datos->status = 409;
        }
        else{
            $datos->mensaje = "LOS ATRIBUTOS CORREO Y CLAVE ESTAN VACIOS";
            $datos->status = 409;
        }


        //Al constructor de mw se le puede pasar el status como parametro
        $response = new ResponseMW($datos->status);

        //Escribo los datos en formato JSON.(estaba en stdClass)
        $response->getBody()->write(json_encode($datos));

        //Para avisaar que devuelvo un json
        return $response->withHeader('Content-Type', 'application/json');
    }


    public function ValidarDatosEnLaBD(Request $request, RequestHandler $handler) : ResponseMW
    {
        $datos = new stdClass;
        $datos->status = 200;

        $arrayDeParametros = $request->getParsedBody();

        if( isset($arrayDeParametros['user'])  )
        {
            $objJson = json_decode($arrayDeParametros["user"] );
        }
        else
        {
            $objJson = json_decode($arrayDeParametros["usuario"] );
        }
        //$objJson = json_decode($arrayDeParametros['usuario']);
        
        $correo = $objJson->correo;
        $clave = $objJson->clave;

        $UserBuscado = Usuario::TraerUsuarioCorreoClave($correo,$clave);

        //si encuentra el user en la bd
        if($UserBuscado != "")
        {
            $response = $handler->handle($request);
            $datos = json_decode($response->getBody());
        }
        else
        {
            $datos->mensaje = "ERROR, NO EXISTE UN USUARIO CON ESOS DATOS EN LA BASE DE DATOS";
            $datos->status = 403;
        }

        $newResponse = new ResponseMW($datos->status);

        $newResponse->getBody()->write(json_encode($datos));
      
        return $newResponse->withHeader('Content-Type', 'application/json');

    }


    public static function ValidarCorreoEnLaBD(Request $request, RequestHandler $handler) : ResponseMW
    {
        $datos = new stdClass;
        $datos->status = 200;

        $arrayDeParametros = $request->getParsedBody();

        if( isset($arrayDeParametros['user'])  )
        {
            $objJson = json_decode($arrayDeParametros["user"] );
        }
        else 
        {
            $objJson = json_decode($arrayDeParametros["usuario"] );
        }
        //$objJson = json_decode($arrayDeParametros['usuario']);
        
        $correo = $objJson->correo;

        $UserBuscado = Usuario::TraerUsuarioCorreo($correo);

        //si no encuentra el user en la bd
        if($UserBuscado == "")
        {
            $response = $handler->handle($request);
            $datos = json_decode($response->getBody());
        }
        else
        {
            $datos->mensaje = "ERROR, YA EXISTE UN USUARIO CON ESE CORREO EN LA BASE DE DATOS";
            $datos->status = 403;
        }

        $newResponse = new ResponseMW($datos->status);

        $newResponse->getBody()->write(json_encode($datos));
      
        return $newResponse->withHeader('Content-Type', 'application/json');

    }

    public function VerificarAuto(Request $request, RequestHandler $handler) : ResponseMW
    {
        $datos = new stdClass;
        $datos->status = 409;

        $arrayDeParametros = $request->getParsedBody();
        $autoJson = json_decode($arrayDeParametros['auto']);
        
        $precio = $autoJson->precio;
        $color = strtolower($autoJson->color);


        if($precio >= 50000 && $precio <= 600000 && $color != 'amarillo')
        {
            $datos->status = 200;
            $response = $handler->handle($request);
            $datos = json_decode($response->getBody());
        }
        else if( ($precio < 50000 || $precio > 600000) && $color != 'amarillo')
        {
            $datos->mensaje = "EL PRECIO TIENE QUE ESTAR ENTRE 50.000 Y 600.000";
        }else if($precio >= 50000 && $precio <= 600000 && $color == 'amarillo')
        {
            $datos->mensaje = "EL COLOR NO PUEDE SER AMARILLO";
        }else if( ($precio < 50000 || $precio > 600000) && $color == 'amarillo')
        {
            $datos->mensaje = "EL COLOR NO PUEDE SER AMARILLO Y EL PRECIO TIENE QUE ESTAR ENTRE 50.000 Y 600.000";
        }


        $newResponse = new ResponseMW($datos->status);
        $newResponse->getBody()->write(json_encode($datos));
      
        return $newResponse->withHeader('Content-Type', 'application/json');
    }






    //PARTE 3!

    public function VerificarTokenValido(Request $request, RequestHandler $handler) : ResponseMW
    {
        $datos = new stdClass;
        $datos->status = 403;

        $token = $request->getHeader("token")[0];
        $verificar = Autentificadora::VerificarJWT($token);

        if($verificar->verificado == true)
        {
            $datos->status = 200;
            $response = $handler->handle($request);
            $datos = json_decode($response->getBody());
        }
        else
        {
            $datos->mensaje = $verificar->mensaje  ;
        }

        $newResponse = new ResponseMW($datos->status);
        $newResponse->getBody()->write(json_encode($datos));
      
        return $newResponse->withHeader('Content-Type', 'application/json');

    }

    public static function VerificarPropietario(Request $request, RequestHandler $handler) : ResponseMW
    {
        $datos = new stdClass;
        $datos->propietario = false;
        $datos->mensaje = "Usted no es propietario";
        $datos->status = 409;

        $token = $request->getHeader("token")[0];

        $datosUser = Autentificadora::ObtenerPayLoad($token);
        $perfilUser = $datosUser->payload->data->perfil;
        if($perfilUser == 'propietario')
        {
            $datos->status = 200;
            $response = $handler->handle($request);
            $datos = json_decode($response->getBody());
        }

        $newResponse = new ResponseMW($datos->status);
        $newResponse->getBody()->write(json_encode($datos));
      
        return $newResponse->withHeader('Content-Type', 'application/json');
    }

    public function VerificarEncargado(Request $request, RequestHandler $handler) : ResponseMW
    {
        $datos = new stdClass;
        $datos->encargado = false;
        $datos->mensaje = "Usted no es encargado";
        $datos->status = 409;

        $token = $request->getHeader("token")[0];

        $datosUser = Autentificadora::ObtenerPayLoad($token);
        $perfilUser = $datosUser->payload->data->perfil;
        if($perfilUser == 'encargado')
        {
            $datos->status = 200;
            $response = $handler->handle($request);
            $datos = json_decode($response->getBody());
        }

        $newResponse = new ResponseMW($datos->status);
        $newResponse->getBody()->write(json_encode($datos));
      
        return $newResponse->withHeader('Content-Type', 'application/json');
    }





    function AccedeEncargado(Request $request, RequestHandler $handler) : ResponseMW
    {
        $response = $handler->handle($request);
        $datos = json_decode($response->getBody());

        $token = $request->getHeader("token")[0];

        $payloadUser = Autentificadora::ObtenerPayLoad($token);
        $datosUser = $payloadUser->payload->data;
        if($datosUser->perfil == 'encargado')
        {
    
            $tabla = $datos->dato;
    
            foreach($tabla as $item)
            {
                unset($item->id);
            }
    
            $datos->dato = $tabla;
        }

        $newResponse = new ResponseMW($datos->status);
        $newResponse->getBody()->write(json_encode($datos));
      
        return $newResponse->withHeader('Content-Type', 'application/json');
    }


    function AccedeEmpleado(Request $request, RequestHandler $handler) : ResponseMW
    {
        $response = $handler->handle($request);
        $datos = json_decode($response->getBody());

        $token = $request->getHeader("token")[0];

        $payloadUser = Autentificadora::ObtenerPayLoad($token);
        if($payloadUser->payload != null)
        {
            $datosUser = $payloadUser->payload->data;
            if($datosUser->perfil == 'empleado')
            {
                $tabla = $datos->dato;
        
                $colores = [];
                foreach($tabla as $item)
                {
                    array_push($colores,$item->color);
                }
                $cantColores = array_count_values($colores);
                $datos->mensaje = "Hay " . count($cantColores) . " colores distintos.";
            }
        }

        $newResponse = new ResponseMW($datos->status);
        $newResponse->getBody()->write(json_encode($datos));
      
        return $newResponse->withHeader('Content-Type', 'application/json');
    }


    static function AccedePropietario(Request $request, RequestHandler $handler) : ResponseMW
    {
        $response = $handler->handle($request);
        
        if(isset(RouteContext::fromRequest($request)->getRoute()->getArguments()['id']))
        {
            $id = RouteContext::fromRequest($request)->getRoute()->getArguments()['id'];
        }
        else
        {
            $id = "noid";
        }
        $datos = json_decode($response->getBody());

        $token = $request->getHeader("token")[0];

        $payloadUser = Autentificadora::ObtenerPayLoad($token);
        $datosUser = $payloadUser->payload->data;
        if($datosUser->perfil == 'propietario')
        {
            $tabla = $datos->dato;

            if($id != 'noid')
            {
                foreach($tabla as $item)
                {
                    if($item->id == $id)
                    {
                        $datos->mensaje = 'ACA TENES TU AUTO';
                        $datos->dato = $item;
                    }
                }
            }
    
        }


        $newResponse = new ResponseMW($datos->status);
        $newResponse->getBody()->write(json_encode($datos));
      
        return $newResponse->withHeader('Content-Type', 'application/json');
    }


    function AccedeEncargadoB(Request $request, RequestHandler $handler) : ResponseMW
    {
        $response = $handler->handle($request);
        $datos = json_decode($response->getBody());

        $token = $request->getHeader("token")[0];

        $payloadUser = Autentificadora::ObtenerPayLoad($token);
        $datosUser = $payloadUser->payload->data;
        if($datosUser->perfil == 'encargado')
        {
            $tabla = $datos->dato;
    
            foreach($tabla as $item)
            {
                unset($item->id);
                unset($item->clave);
            }
    
            $datos->dato = $tabla;
        }

        $newResponse = new ResponseMW($datos->status);
        $newResponse->getBody()->write(json_encode($datos));
      
        return $newResponse->withHeader('Content-Type', 'application/json');
    }


    function AccedeEmpleadoB(Request $request, RequestHandler $handler) : ResponseMW
    {
        $response = $handler->handle($request);
        $datos = json_decode($response->getBody());

        $token = $request->getHeader("token")[0];

        $payloadUser = Autentificadora::ObtenerPayLoad($token);
        $datosUser = $payloadUser->payload->data;
        if($datosUser->perfil == 'empleado')
        {
            $tabla = $datos->dato;
    
            foreach($tabla as $item)
            {
                unset($item->id);
                unset($item->clave);
                unset($item->perfil);
                unset($item->correo);
            }
    
            $datos->dato = $tabla;
        }

        $newResponse = new ResponseMW($datos->status);
        $newResponse->getBody()->write(json_encode($datos));
      
        return $newResponse->withHeader('Content-Type', 'application/json');
    }


    function AccedePropietarioB(Request $request, RequestHandler $handler) : ResponseMW
    {
        $response = $handler->handle($request);
        $datos = json_decode($response->getBody());

        $token = $request->getHeader("token")[0];
        if(isset(RouteContext::fromRequest($request)->getRoute()->getArguments()['apellido']))
        {
            $apellido = RouteContext::fromRequest($request)->getRoute()->getArguments()['apellido'];
        }
        else
        {
            $apellido = "noApellido";
        }

        $payloadUser = Autentificadora::ObtenerPayLoad($token);
        if($payloadUser->payload != null)
        {
            $datosUser = $payloadUser->payload->data;
            if($datosUser->perfil == 'propietario')
            {
                $tabla = $datos->dato;
                $listaApellidos = [];
                $todosLosApellidos = [];
        
                if($apellido != "noApellido")
                {
                    foreach($tabla as $item)
                    {
                        if($tabla->apellido == $apellido)
                        {
                            array_push($listaApellidos, $tabla->apellido);
                        }
                    }

                    if(count($listaApellidos) == NULL){
                        $cantidad = 0;
                    }else{
                        $cantidad = count($listaApellidos);
                    }
                    $datos->mensaje = $cantidad;

                }
                else
                {
                    foreach($tabla as $item){
                        array_push($todosLosApellidos,$item->apellido);
                    }

                    $todosLosApellidos = array_count_values($todosLosApellidos);

                    $datos->mensaje = $todosLosApellidos;
                }


            }
        }

        $newResponse = new ResponseMW($datos->status);
        $newResponse->getBody()->write(json_encode($datos));
      
        return $newResponse->withHeader('Content-Type', 'application/json');
    }



















}


?>