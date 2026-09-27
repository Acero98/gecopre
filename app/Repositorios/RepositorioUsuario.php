<?php
declare(strict_types=1);

namespace App\Repositorios;

use App\Core\ConexionBaseDatos;

final class RepositorioUsuario
{
    public function contar(): int
    {
        return (int) ConexionBaseDatos::obtener()->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    }

    public function buscarPorCorreo(string $correo): ?array
    {
        $consulta = ConexionBaseDatos::obtener()->prepare(
            'SELECT u.id, u.nombres, u.apellidos, u.correo, u.contrasena_hash, u.activo,
                    GROUP_CONCAT(r.nombre ORDER BY r.nombre SEPARATOR ", ") AS roles
             FROM usuarios u
             LEFT JOIN usuario_roles ur ON ur.usuario_id = u.id
             LEFT JOIN roles r ON r.id = ur.rol_id
             WHERE u.correo = :correo
             GROUP BY u.id'
        );
        $consulta->execute(['correo' => strtolower(trim($correo))]);
        $usuario = $consulta->fetch(\PDO::FETCH_ASSOC);
        return $usuario === false ? null : $usuario;
    }

    public function crearAdministrador(string $nombres, string $apellidos, string $correo, string $contrasena): void
    {
        $conexion = ConexionBaseDatos::obtener();
        $conexion->beginTransaction();
        try {
            $crear = $conexion->prepare(
                'INSERT INTO usuarios (nombres, apellidos, correo, contrasena_hash) VALUES (:nombres, :apellidos, :correo, :contrasena_hash)'
            );
            $crear->execute([
                'nombres' => trim($nombres),
                'apellidos' => trim($apellidos),
                'correo' => strtolower(trim($correo)),
                'contrasena_hash' => password_hash($contrasena, PASSWORD_DEFAULT),
            ]);
            $usuarioId = (int) $conexion->lastInsertId();

            $rol = $conexion->query("SELECT id FROM roles WHERE nombre = 'Administrador' LIMIT 1")->fetchColumn();
            if ($rol === false) {
                throw new \RuntimeException('No se encontró el rol Administrador.');
            }
            $asignar = $conexion->prepare('INSERT INTO usuario_roles (usuario_id, rol_id) VALUES (:usuario_id, :rol_id)');
            $asignar->execute(['usuario_id' => $usuarioId, 'rol_id' => (int) $rol]);
            $conexion->commit();
        } catch (\Throwable $error) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            throw $error;
        }
    }

    public function registrarAcceso(int $usuarioId): void
    {
        $consulta = ConexionBaseDatos::obtener()->prepare('UPDATE usuarios SET ultimo_acceso_en = CURRENT_TIMESTAMP WHERE id = :id');
        $consulta->execute(['id' => $usuarioId]);
    }
}
