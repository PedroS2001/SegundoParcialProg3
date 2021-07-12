<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once "AccesoDatos.php";


class Auto
{
    public $id;
    public $color;
    public $marca;
    public $precio;
    public $modelo;


    public function AgregarAuto(Request $request, Response $response, array $args): Response 
	{
        $arrayDeParametros = $request->getParsedBody();
        $objJson = json_decode($arrayDeParametros['auto']);

        $retorno = new stdClass();
        $retorno->exito = false;
        $retorno->mensaje = "No se pudo agregar el Auto";
        $retorno->status = 418;

		$color = $objJson->color;
		$marca = $objJson->marca;
		$precio = $objJson->precio;
		$modelo  = $objJson->modelo;

        
        $miAuto = new Auto();
        $miAuto->color = $color;
        $miAuto->marca = $marca;
		$miAuto->precio = $precio;
		$miAuto->modelo = $modelo;

        $ultimoId = AccesoDatos::dameUnObjetoAcceso();


        $id_agregado = $miAuto->InsertarAuto();

        if($id_agregado!=null)
        {
            $retorno->exito = true;
            $retorno->mensaje = "Se agrego el auto correctamente";
            $retorno->status = 200;
        }

        $newResponse = $response->withStatus($retorno->status, "OK");
		$newResponse->getBody()->write(json_encode($retorno));	

		return $newResponse->withHeader('Content-Type', 'application/json');
    }


    public function MostrarTodosLosAutos(Request $request, Response $response, array $args): Response 
	{
        $respuesta = new stdClass();
        $respuesta->exito = false;
        $respuesta->mensaje = "No se pudieron traer todos los Autos";
        $respuesta->dato = "";
        $respuesta->status = 424;

		$todosLosAutos = Auto::TraerTodosLosAutos();
  
        if($todosLosAutos != null)
        {
            $respuesta->exito = true;
            $respuesta->mensaje = "Aca tenes todos los Autos ";
            $respuesta->dato = $todosLosAutos;
            $respuesta->status = 200;
        }
		$newResponse = $response->withStatus($respuesta->status, "OK");
		$newResponse->getBody()->write(json_encode($respuesta));

		return $newResponse->withHeader('Content-Type', 'application/json');	
	}



    #PARTE 3!

    public function EliminarAuto(Request $request, Response $response, array $args): Response
    {
        $respuesta = new stdClass();
        $respuesta->status = 200;
        $respuesta->mensaje = "";

        //$arrayDeParametros = $request->getParsedBody();
        //$id = $arrayDeParametros['id_auto']; 

        $id = $request->getHeader("id_auto")[0];
        
        $token = $request->getHeader("token")[0];

        $verificar = Autentificadora::VerificarJWT($token);

        if($verificar->verificado == true)
        {
            $datosUser = Autentificadora::ObtenerPayLoad($token);
            $perfilUser = $datosUser->payload->data->perfil;
            if($perfilUser == 'propietario')
            {
                $auto = new Auto();
                $auto->id = $id;
                $borro = $auto->BorrarAuto();
                if($borro > 0)
                {
                    $respuesta->mensaje = 'Auto borrado';
                }
                else
                {
                    $respuesta->mensaje = 'No se borro el auto';
                }
            }
            else
            {
                $respuesta->mensaje = 'Usted no es el propietario del auto. El usuario que intento modificar el auto es ' + $datosUser->payload->data;
            }

        }
        else
        {
            $respuesta->mensaje = $verificar->mensaje;
        }

        $newResponse = $response->withStatus($respuesta->status, "OK");
		$newResponse->getBody()->write(json_encode($respuesta));	

		return $newResponse->withHeader('Content-Type', 'application/json');

    }

    public function ModificarAuto(Request $request, Response $response, array $args): Response
    {
        $token = $request->getHeader("token")[0];

        $retorno = new stdClass();
        $retorno->exito = false;
        $retorno->mensaje = "No se pudo Modificar el Auto";
        $retorno->status = 418;

        //$arrayDeParametros = $request->getParsedBody();
        //$objJson = json_decode($arrayDeParametros['autoJson']);     
        $objJson = json_decode($request->getHeader("auto")[0]);   
        $id = $request->getHeader("id_auto")[0];   
        
        $verificar = Autentificadora::VerificarJWT($token);
        if($verificar->verificado == true)
        {
            $datosUser = Autentificadora::ObtenerPayLoad($token);
            $perfilUser = $datosUser->payload->data->perfil;
            if($perfilUser == 'encargado')
            {
                $miAuto = new Auto();
                $miAuto->id = $id;
                $miAuto->color = $objJson->color;
                $miAuto->marca = $objJson->marca;
                $miAuto->precio = $objJson->precio;
                $miAuto->modelo = $objJson->modelo;

                if($miAuto->ModificarAutoEnLaBD() > 0)
                {
                    $retorno->mensaje = "Se modifico el auto correctamente";
                    $retorno->exito = true;
                    $retorno->status = 200;
                }
                
            }
            else
            {
                $retorno->mensaje = 'Usted no es el Encargado del auto. el usuario que intento modificar el auto es ' + $datosUser->payload->data;
            }
        }
        else
        {
            $retorno->mensaje = $verificar->mensaje;
        }

        $newResponse = $response->withStatus($retorno->status, "OK");
		$newResponse->getBody()->write(json_encode($retorno));	

		return $newResponse->withHeader('Content-Type', 'application/json');


    }





























    #region BASE DE DATOS

    /** Trae todos los Autos de la base de datos
     *  Los devuelve convertidos en la clase Auto.php
     */
	public static function TraerTodosLosAutos()
	{
		$objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
		$consulta =$objetoAccesoDato->RetornarConsulta("SELECT * FROM autos");
		$consulta->execute();			
		return $consulta->fetchAll(PDO::FETCH_CLASS, "Auto");		
	}


    /** Agrega un usuario a la base de datos
     *  
     */
	public function InsertarAuto()
	{
		$objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
		$consulta =$objetoAccesoDato->RetornarConsulta("INSERT into autos (color, marca, precio, modelo)
                                                        values(:color, :marca, :precio, :modelo)");
		$consulta->bindValue(':color',$this->color, PDO::PARAM_STR);
		$consulta->bindValue(':marca', $this->marca, PDO::PARAM_STR);
		$consulta->bindValue(':precio', $this->precio, PDO::PARAM_STR);
		$consulta->bindValue(':modelo', $this->modelo, PDO::PARAM_STR);

		$consulta->execute();		
		return $objetoAccesoDato->RetornarUltimoIdInsertado();
	}

    public function BorrarAuto()
	{
	 	$objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
		$consulta =$objetoAccesoDato->RetornarConsulta("DELETE FROM autos WHERE id=:id");	
		$consulta->bindValue(':id',$this->id, PDO::PARAM_INT);		
		$consulta->execute();
		return $consulta->rowCount();
	}

    public function ModificarAutoEnLaBD()
	{
		$objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
		$consulta = $objetoAccesoDato->RetornarConsulta("
				UPDATE autos 
				SET color=:color,
				marca=:marca,
				precio=:precio,
				modelo=:modelo
				WHERE id=:id");
		$consulta->bindValue(':id',$this->id, PDO::PARAM_INT);
		$consulta->bindValue(':color',$this->color, PDO::PARAM_STR);
		$consulta->bindValue(':marca', $this->marca, PDO::PARAM_STR);
		$consulta->bindValue(':precio', $this->precio, PDO::PARAM_STR);
		$consulta->bindValue(':modelo', $this->modelo, PDO::PARAM_STR);
        
        $consulta->execute();
		return $consulta->rowCount(); 
	}



    #endregion


}

?>