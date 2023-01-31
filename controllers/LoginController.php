<?php

namespace Controllers;

use Classes\Email;
use Model\Usuario;
use MVC\Router;

class LoginController
{
    public static function login(Router $router)
    {
        $alertas = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $auth = new Usuario($_POST);
            $alertas = $auth->validarLogin();

            if (empty($alertas)) {
                //verificar que el usuario exista
                $auth = Usuario::where('email', $auth->email);
                if (!$auth || !$auth->confirmado) {
                    Usuario::setAlerta('error', ' el usuario no existe');
                } else {
                    //el usuario existe
                    if (password_verify($_POST['password'], $auth->password)) {
                        if (!$_SESSION['nombre']) {
                            session_start();
                        }
                        $_SESSION['id'] = $auth->id;
                        $_SESSION['nombre'] = $auth->nombre;
                        $_SESSION['email'] = $auth->email;
                        $_SESSION['login'] = true;

                        //redireccionar
                        header('location: /dashboard');
                    } else {
                        Usuario::setAlerta('error', ' el password es incorrecto');
                    }
                }
            }
        }
        $alertas = Usuario::getAlertas();
        $router->render('auth/login', [
            'titulo' => 'Iniciar Sesión',
            'alertas' => $alertas
        ]);
    }
    public static function logout()
    {
        session_start();
        $_SESSION = [];
        header('location: /');
    }
    public static function crear(Router $router)
    {
        $alertas = [];
        $usuario = new Usuario;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario->sincronizar($_POST);
            $alertas = $usuario->validarNuevaCuenta();
            $existeUsuario = Usuario::where('email', $usuario->email);

            if (empty($alertas)) {
                if ($existeUsuario) {
                    Usuario::setAlerta('error', 'el usuario ya esta registrado');
                    $alertas = Usuario::getAlertas();
                } else {
                    //hashear el password
                    $usuario->hashPassword();

                    //generar token
                    $usuario->crearToken();

                    //crear usuario
                    $resultado = $usuario->guardar();

                    //enviar email
                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                    $email->enviarConfirmacion();

                    if ($resultado) {
                        header('location: /mensaje');
                    }

                    //eliminar password2
                    unset($usuario->password2);
                }
            }
        }
        $router->render('auth/crear', [
            'titulo' => 'Crear tu Cuenta en upTask',
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);
    }
    public static function olvide(Router $router)
    {
        $alertas = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = new Usuario($_POST);
            $alertas = $usuario->validarEmail();

            if (empty($alertas)) {
                //buscar el usuario
                $usuario = Usuario::where('email', $usuario->email);
                if ($usuario && $usuario->confirmado) {
                    //generar un nuevo token
                    $usuario->crearToken();
                    unset($usuario->password2);
                    //actualizar el usuario
                    $usuario->guardar();

                    //enviar el email
                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                    $email->enviarInstrucciones();
                    //imprimir alerta
                    Usuario::setAlerta('exito', 'Hemos enviado las instrucciones a tu email');
                } else {
                    Usuario::setAlerta('error', ' el usuario no existe o no esta confirmado');
                }
            }
        }
        $alertas = Usuario::getAlertas();

        $router->render('auth/olvide', [
            'titulo' => 'Olvide mi Password',
            'alertas' => $alertas

        ]);
    }
    public static function reestablecer(Router $router)
    {
        $alertas = [];
        $token = s($_GET['token']);
        $mostrar = true;
        if (!$token) header('location: /');

        $usuario = Usuario::where('token', $token);

        if (empty($usuario)) {
            Usuario::setAlerta('error', 'Token no Válido');
            $mostrar = false;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // añadir nuevo password
            $usuario->sincronizar($_POST);
            //valdiar el password
            $alertas = $usuario->validarPassword();

            if (empty($alertas)) {
                //hashear password
                $usuario->hashPassword();

                //eliminar token
                $usuario->token = null;

                //Guardar db
                $resultado = $usuario->guardar();

                if ($resultado) {
                    header('location: /');
                }
            }
        }
        $alertas = Usuario::getAlertas();
        $router->render('auth/reestablecer', [
            'titulo' => 'Reestablecer Password',
            'alertas' => $alertas,
            'mostrar' => $mostrar
        ]);
    }
    public static function mensaje(Router $router)
    {
        $router->render('auth/mensaje', [
            'titulo' => 'Cuenta Creada Exitosamente'
        ]);
    }
    public static function confirmar(Router $router)
    {
        $token = s($_GET['token']);
        if (!$token) header('location: /');

        //encontrar al usuario con este token
        $usuario = Usuario::where('token', $token);
        if (empty($usuario)) {
            Usuario::setAlerta('error', 'Token no Valido');
        } else {
            //confirmar la cuenta
            $usuario->confirmado = 1;
            $usuario->token = null;
            unset($usuario->password2);

            $usuario->guardar();
            Usuario::setAlerta('exito', 'Cuenta Comprobada Correctamente');
        }

        $alertas = Usuario::getAlertas();
        $router->render('auth/confirmar', [
            'titulo' => 'Confirmar tu cuenta UpTask',
            'alertas' => $alertas
        ]);
    }
}
