<?php
// Controlador para autenticacion, registro y redireccion por rol.

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

class AuthController
{
    public static function login($email, $password)
    {
        $user = User::findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'El correo o la contraseña no son correctos.'];
        }

        $messages = [
            'pendiente' => 'Tu cuenta está pendiente de aprobación administrativa.',
            'rechazado' => 'Tu cuenta no fue aprobada. Contacta con administración si necesitas ayuda.',
            'bloqueado' => 'Tu cuenta está bloqueada. Contacta con administración.',
            'inactivo' => 'Tu cuenta se encuentra inactiva. Contacta con administración.',
        ];
        if ($user['estado'] !== 'activo') {
            return [
                'success' => false,
                'message' => $messages[$user['estado']] ?? 'No se puede acceder con esta cuenta.',
            ];
        }

        session_regenerate_id(true);
        rotateCsrfToken();

        $_SESSION['user'] = [
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'email' => $user['email'],
            'rol_id' => $user['rol_id'],
            'rol' => $user['rol'],
            'telefono' => $user['telefono'],
            'idioma' => $user['idioma'],
        ];

        return ['success' => true, 'message' => 'Acceso correcto.'];
    }

    public static function register($nombre, $email, $password, $telefono, $rol)
    {
        $allowedRoles = ['cliente', 'organizador', 'proveedor'];

        if ($nombre === '' || $email === '' || $password === '' || $rol === '') {
            return ['success' => false, 'message' => 'Completa todos los campos obligatorios.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Ingresa un email valido.'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'La contrasena debe tener al menos 6 caracteres.'];
        }

        if (!in_array($rol, $allowedRoles, true)) {
            return ['success' => false, 'message' => 'Rol no permitido para registro publico.'];
        }

        if (User::emailExists($email)) {
            return ['success' => false, 'message' => 'El email ya esta registrado.'];
        }

        $created = User::create($nombre, $email, $password, $telefono, $rol);

        $message = $rol === 'cliente'
            ? 'Registro exitoso. Ahora puedes iniciar sesión.'
            : 'Registro exitoso. Tu cuenta quedó pendiente de aprobación administrativa.';

        return [
            'success' => $created,
            'message' => $created ? $message : 'No se pudo registrar el usuario.',
        ];
    }

    public static function logout()
    {
        logoutUser();
    }

    public static function redirectByRole()
    {
        $user = currentUser();

        if (!$user) {
            redirect('/GoldenHoursEvents/views/auth/login.php');
        }

        $routes = [
            'cliente' => '/GoldenHoursEvents/views/client/dashboard.php',
            'organizador' => '/GoldenHoursEvents/views/organizer/dashboard.php',
            'proveedor' => '/GoldenHoursEvents/views/provider/dashboard.php',
            'admin' => '/GoldenHoursEvents/views/admin/dashboard.php',
        ];

        redirect($routes[$user['rol']] ?? '/GoldenHoursEvents/index.php');
    }
}
