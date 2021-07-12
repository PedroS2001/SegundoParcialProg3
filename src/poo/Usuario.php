<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . './AccesoDatos.php';
require_once __DIR__ . './AutentificadoraJWT.php';


class Usuario
{
    public $id;
    public $correo;
    public $clave;
    public $nombre;
    public $apellido;
    public $perfil;
    public $foto;



    //Se le envia una cadena json llamada usuario
    public function AgregarUsuario(Request $request, Response $response, array $args): Response 
	{
        $arrayDeParametros = $request->getParsedBody();
        $objJson = json_decode($arrayDeParametros['usuario']);

        $retorno = new stdClass();
        $retorno->exito = false;
        $retorno->mensaje = "No se pudo agregar el usuario";
        $retorno->status = 418;

		$nombre = $objJson->nombre;
		$apellido = $objJson->apellido;
		$correo = $objJson->correo;
		//$foto = $objJson->foto;
		$perfil  = $objJson->perfil;
		$clave = $objJson->clave;
        
        $miUser = new Usuario();
        $miUser->nombre = $nombre;
        $miUser->apellido = $apellido;
		$miUser->correo = $correo;
		$miUser->foto = "";
		$miUser->perfil = $perfil;
		$miUser->clave = $clave;


        $id_agregado = $miUser->InsertarUsuario();

        if($id_agregado!=null)
        {
            $retorno->exito = true;
            $retorno->mensaje = "Se agrego el usuario correctamente";
            $retorno->status = 200;
        }

	//*********************************************************************************************//
	//SUBIDA DE ARCHIVOS (SE PUEDEN TENER FUNCIONES DEFINIDAS)
	//*********************************************************************************************//

		//este seria como un $FILES
		$archivos = $request->getUploadedFiles();
        //verifico que haya cargado un archivo, //no funciona
        if($archivos != null)
        {
            $destino = __DIR__ . "/../Fotos/";
            //'foto' es como le pusimos en el form
            $nombreAnterior = $archivos['foto']->getClientFilename();
            $extension = explode(".", $nombreAnterior);

            $extension = array_reverse($extension);
            //muevo al destino, con el nombre 
            $destinoFinal = $destino . $correo . "_" . $id_agregado . "." . $extension[0];
            $archivos['foto']->moveTo($destinoFinal);

            #AGREGAR: MODIFICAR PARA PONER EL PATHFOTO EN LA BD
            $miUser->foto = $destinoFinal;
            $miUser->AgregarFotoBD();
        }
        
		
        /***************************** */
        $newResponse = $response->withStatus($retorno->status, "OK");
		$newResponse->getBody()->write(json_encode($retorno));	

		return $newResponse->withHeader('Content-Type', 'application/json');
    }


    public function MostrarTodosLosUsuarios(Request $request, Response $response, array $args): Response 
	{
        $respuesta = new stdClass();
        $respuesta->exito = false;
        $respuesta->mensaje = "No se pudieron traer todos los usuarios";
        $respuesta->dato = "";
        $respuesta->status = 424;

		$todosLosUsuarios = Usuario::TraerTodosLosUsuarios();
  
        if($todosLosUsuarios != null)
        {
            $respuesta->exito = true;
            $respuesta->mensaje = "Aca tenes todos los usuarios";
            $respuesta->dato = $todosLosUsuarios;
            $respuesta->status = 200;
        }
		$newResponse = $response->withStatus($respuesta->status, "OK");
		$newResponse->getBody()->write(json_encode($respuesta));

		return $newResponse->withHeader('Content-Type', 'application/json');	
	}


    public function LoginPost(Request $request, Response $response, array $args) : Response
    {
        $retorno = new stdClass();
        $retorno->exito = false ;
        $retorno->JWT = null;
        $retorno->status = 418;

        $arrayDeParametros = $request->getParsedBody();
        $objJson = json_decode($arrayDeParametros['user']);
        
        $correo = $objJson->correo;
        $clave = $objJson->clave;

        $UserBuscado = Usuario::TraerUsuarioCorreoClave($correo,$clave);

        if($UserBuscado != "")
        {
            $userSinClave = new Usuario();
            $userSinClave->id = $UserBuscado->id;
            $userSinClave->nombre = $UserBuscado->nombre;
            $userSinClave->apellido = $UserBuscado->apellido;
            $userSinClave->correo = $UserBuscado->correo;
            $userSinClave->perfil = $UserBuscado->perfil;
            $userSinClave->foto = $UserBuscado->foto;

            $jwt = Autentificadora::CrearJWT($userSinClave);

            $retorno->exito = true;
            $retorno->JWT = $jwt;
            $retorno->status = 200;
        }

        $newResponse = $response->withStatus($retorno->status);

        $newResponse->getBody()->write(json_encode($retorno));
      
        return $newResponse->withHeader('Content-Type', 'application/json');
        
    }

    public function LoginGet(Request $request, Response $response, array $args) : Response
    {
        $respuesta = new stdClass();
        $respuesta->status = 403;
        $token = $request->getHeader("token")[0];

        $verificar = Autentificadora::VerificarJWT($token);

        if($verificar->verificado == true)
        {
            $respuesta->status = 200;
        }
        
        $respuesta->mensaje = $verificar->mensaje;
        $newResponse = $response->withStatus($respuesta->status);

        $newResponse->getBody()->write(json_encode($respuesta));
        
        return $newResponse->withHeader('Content-Type', 'application/json');
    }


















    #region FUNCIONES ACCESO A DATOS

    public function LastID()
    {
		$objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
        return $objetoAccesoDato->RetornarUltimoIdInsertado();
    }
    /** Trae todos los usuarios de la base de datos
     *  Los devuelve convertidos en la clase Usuario.php
     */
	public static function TraerTodosLosUsuarios()
	{
		$objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
		$consulta =$objetoAccesoDato->RetornarConsulta("SELECT * FROM usuarios");
		$consulta->execute();			
		return $consulta->fetchAll(PDO::FETCH_CLASS, "usuario");		
	}

    /** Trae de la base de datos el usuario que coincida con el id que le pasamos
     *  Devuelve ese objeto Usuario
     */
	public static function TraerUnUsuario($id) 
	{
		$objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
		$consulta =$objetoAccesoDato->RetornarConsulta("SELECT * FROM usuarios WHERE id = $id");
		$consulta->execute();
		$UserBuscado= $consulta->fetchObject('Usuario');
		return $UserBuscado;		
	}

    /** Trae de la base de datos el usuario que coincida con el id que le pasamos
     *  Devuelve ese objeto Usuario
     */
	public static function TraerUsuarioCorreoClave($correo, $clave) 
	{
		$objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
		$consulta =$objetoAccesoDato->RetornarConsulta("SELECT * FROM usuarios WHERE clave=:clave and correo=:correo");
        $consulta->bindValue(':correo', $correo, PDO::PARAM_STR);
		$consulta->bindValue(':clave', $clave, PDO::PARAM_STR);

		$consulta->execute();
		$UserBuscado= $consulta->fetchObject('Usuario');
		return $UserBuscado;		
	}

    public static function TraerUsuarioCorreo($correo) 
	{
		$objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
		$consulta =$objetoAccesoDato->RetornarConsulta("SELECT * FROM usuarios WHERE correo=:correo");
        $consulta->bindValue(':correo', $correo, PDO::PARAM_STR);

		$consulta->execute();
		$UserBuscado= $consulta->fetchObject('Usuario');
		return $UserBuscado;		
	}

    /** Agrega un usuario a la base de datos
     *  
     */
	public function InsertarUsuario()
	{
		$objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
		$consulta =$objetoAccesoDato->RetornarConsulta("INSERT into Usuarios (nombre, apellido, correo, foto, perfil, clave)
                                                        values(:nombre, :apellido, :correo, :foto, :perfil, :clave)");
		$consulta->bindValue(':nombre',$this->nombre, PDO::PARAM_STR);
		$consulta->bindValue(':apellido', $this->apellido, PDO::PARAM_STR);
		$consulta->bindValue(':correo', $this->correo, PDO::PARAM_STR);
		$consulta->bindValue(':foto', $this->foto, PDO::PARAM_STR);
		$consulta->bindValue(':perfil', $this->perfil, PDO::PARAM_INT);
		$consulta->bindValue(':clave', $this->clave, PDO::PARAM_STR);

		$consulta->execute();		
		return $objetoAccesoDato->RetornarUltimoIdInsertado();
	}

    /** Modifica un usuario de la base de datos
     *  
     */
	public function ModificarUsuario()
	{
		$objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
		$consulta = $objetoAccesoDato->RetornarConsulta("
				UPDATE Usuarios 
				SET nombre=:nombre,
				apellido=:apellido,
				correo=:correo,
				foto=:foto,
				perfil=:perfil,
				clave=:clave
				WHERE id=:id");
		$consulta->bindValue(':id',$this->id, PDO::PARAM_INT);
		$consulta->bindValue(':nombre',$this->nombre, PDO::PARAM_STR);
		$consulta->bindValue(':apellido', $this->apellido, PDO::PARAM_STR);
		$consulta->bindValue(':correo', $this->correo, PDO::PARAM_STR);
		$consulta->bindValue(':foto', $this->foto, PDO::PARAM_STR);
		$consulta->bindValue(':perfil', $this->perfil, PDO::PARAM_STR);
		$consulta->bindValue(':clave', $this->clave, PDO::PARAM_STR);

		$consulta->execute();
		return $consulta->rowCount();
	 }

    /**Elimina un usuario de la base de datos
     * 
     */
	public function BorrarUsuario()
	{
	 	$objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
		$consulta =$objetoAccesoDato->RetornarConsulta("DELETE FROM Usuarios WHERE id=:id");	
		$consulta->bindValue(':id',$this->id, PDO::PARAM_INT);		
		$consulta->execute();
		return $consulta->rowCount();
	}

    public function AgregarFotoBD()
    {
	 	$objetoAccesoDato = AccesoDatos::dameUnObjetoAcceso(); 
		$consulta = $objetoAccesoDato->RetornarConsulta("UPDATE Usuarios 
                                                        SET foto=:foto");
		$consulta->bindValue(':foto', $this->foto, PDO::PARAM_STR);

        $consulta->execute();
		return $consulta->rowCount();
    }
    #endregion



}

?>