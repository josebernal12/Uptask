<?php

namespace Controllers;

use Model\Proyecto;
use Model\Usuario;
use MVC\Router;

class DashboardController
{
    public static function index(Router $router)
    {
        session_start();
        isAuth();
        $id = $_SESSION['id'];
        $proyectos = Proyecto::belongsTo('propietarioid', $id);
        $router->render('dashboard/index', [
            'titulo' => 'Proyectos',
            'proyectos' => $proyectos
        ]);
    }
    public static function crear_proyecto(Router $router)
    {
        session_start();
        isAuth();
        $alertas = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $proyecto = new Proyecto($_POST);
            // debuguear($proyecto);
            $alertas = $proyecto->validarProyecto();

            if (empty($alertas)) {
                //generar una url unica
                $hash = md5(uniqid());
                $proyecto->url = $hash;
                //almacenar el creador del proyecto
                $proyecto->propietarioid = $_SESSION['id'];

                $proyecto->guardar();

                header('location: /proyecto?id=' . $proyecto->url);
            }
        }
        $router->render('dashboard/crear-proyecto', [
            'alertas' => $alertas,
            'titulo' => 'Crear Proyecto'
        ]);
    }
    public static function proyecto(Router $router)
    {
        session_start();
        isAuth();
        $token = $_GET['id'];
        if (!$token) header('location: /dashboard');
        //revisar que la persona que visita el proyecto es quien lo creo
        $proyecto = Proyecto::where('url', $token);
        if ($proyecto->propietarioid !== $_SESSION['id']) {
            header('location: /dashboard');
        }
        $router->render('dashboard/proyecto', [
            'titulo' => $proyecto->proyecto
        ]);
    }
    public static function perfil(Router $router)
    {
        session_start();
        isAuth();
        $router->render('dashboard/perfil', [
            'titulo' => 'Perfil'
        ]);
    }
}
