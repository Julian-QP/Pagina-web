<?php

declare(strict_types=1);

class conexion
{
    private mysqli $db;

    public function __construct()
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        $host = getenv('DDP_DB_HOST') ?: 'sql104.infinityfree.com';
        $user = getenv('DDP_DB_USER') ?: 'if0_42943815';
        $password = getenv('DDP_DB_PASSWORD') ?: 'RogJJuli123';
        $database = getenv('DDP_DB_NAME') ?: 'if0_42943815_revista_digital';
        $port = (int) (getenv('DDP_DB_PORT') ?: 3306);

        try {
            $this->db = new mysqli($host, $user, $password, $database, $port);
            $this->db->set_charset('utf8mb4');
            $columna = $this->db->query(
                "SELECT COUNT(*) AS total
                 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'boletines'
                   AND COLUMN_NAME = 'titulo_boletin'"
            )->fetch_assoc();
            if ((int) ($columna['total'] ?? 0) === 0) {
                $this->db->query("ALTER TABLE boletines ADD COLUMN titulo_boletin VARCHAR(100) NOT NULL DEFAULT '' AFTER numero_boletin");
            }
            foreach ([
                'palabras_resaltadas' => "ALTER TABLE videos ADD COLUMN palabras_resaltadas VARCHAR(500) NOT NULL DEFAULT '' AFTER portada",
                'color_resaltado' => "ALTER TABLE videos ADD COLUMN color_resaltado CHAR(7) NOT NULL DEFAULT '#facc15' AFTER palabras_resaltadas",
                'modo_portada' => "ALTER TABLE videos ADD COLUMN modo_portada VARCHAR(20) NOT NULL DEFAULT 'titulo' AFTER color_resaltado",
            ] as $nombreColumna => $alterar) {
                $columnaVideo = $this->db->query(
                    "SELECT COUNT(*) AS total
                     FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE()
                       AND TABLE_NAME = 'videos'
                       AND COLUMN_NAME = '" . $nombreColumna . "'"
                )->fetch_assoc();
                if ((int) ($columnaVideo['total'] ?? 0) === 0) {
                    $this->db->query($alterar);
                }
            }
            $columnaDecoracion = $this->db->query(
                "SELECT COUNT(*) AS total
                 FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'reportajes'
                   AND COLUMN_NAME = 'decoracion_imagen'"
            )->fetch_assoc();
            if ((int) ($columnaDecoracion['total'] ?? 0) === 0) {
                $this->db->query("ALTER TABLE reportajes ADD COLUMN decoracion_imagen TEXT NULL AFTER foto_principal");
            }
            $this->db->query(
                "CREATE TABLE IF NOT EXISTS recuperacion_contrasenas (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    usuario_id INT(10) UNSIGNED NOT NULL,
                    token_hash CHAR(64) NOT NULL,
                    expira_en DATETIME NOT NULL,
                    usado_en DATETIME NULL,
                    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY uq_recuperacion_token (token_hash),
                    KEY idx_recuperacion_usuario (usuario_id),
                    CONSTRAINT fk_recuperacion_usuario FOREIGN KEY (usuario_id)
                        REFERENCES usuarios (id) ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            $this->db->query(
                "CREATE TABLE IF NOT EXISTS configuracion_sitio (
                    clave VARCHAR(100) NOT NULL PRIMARY KEY,
                    valor TEXT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        } catch (mysqli_sql_exception $exception) {
            throw new RuntimeException('No se pudo conectar con la base de datos.', 0, $exception);
        }
    }

    public function autenticarUsuario(string $email, string $password): array|false
    {
        if ($email === '' || $password === '') {
            return false;
        }

        $consulta = $this->db->prepare(
            'SELECT id, email, password_hash, rol
             FROM usuarios
             WHERE email = ?
             LIMIT 1'
        );
        $consulta->bind_param('s', $email);
        $consulta->execute();

        $usuario = $consulta->get_result()->fetch_assoc();
        $consulta->close();

        if ($usuario === null) {
            return false;
        }

        $passwordValida = password_verify($password, $usuario['password_hash']);

        // Migra credenciales antiguas al formato seguro en el primer acceso.
        if (!$passwordValida && hash_equals((string) $usuario['password_hash'], $password)) {
            $passwordValida = true;
            $nuevoHash = password_hash($password, PASSWORD_DEFAULT);
            $actualizar = $this->db->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?');
            $actualizar->bind_param('si', $nuevoHash, $usuario['id']);
            $actualizar->execute();
            $actualizar->close();
        }

        if (!$passwordValida) {
            return false;
        }

        unset($usuario['password_hash']);
        return $usuario;
    }

    public function crearTokenRecuperacion(string $email): ?array
    {
        $consulta = $this->db->prepare(
            'SELECT id, email, nombres FROM usuarios WHERE email = ? LIMIT 1'
        );
        $consulta->bind_param('s', $email);
        $consulta->execute();
        $usuario = $consulta->get_result()->fetch_assoc() ?: null;
        $consulta->close();

        if ($usuario === null) {
            return null;
        }

        $limpiar = $this->db->prepare(
            'DELETE FROM recuperacion_contrasenas WHERE usuario_id = ? OR expira_en < NOW()'
        );
        $limpiar->bind_param('i', $usuario['id']);
        $limpiar->execute();
        $limpiar->close();

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiraEn = date('Y-m-d H:i:s', time() + 3600);
        $guardar = $this->db->prepare(
            'INSERT INTO recuperacion_contrasenas (usuario_id, token_hash, expira_en)
             VALUES (?, ?, ?)'
        );
        $guardar->bind_param('iss', $usuario['id'], $tokenHash, $expiraEn);
        $guardar->execute();
        $guardar->close();

        return [
            'email' => $usuario['email'],
            'nombres' => $usuario['nombres'],
            'token' => $token,
        ];
    }

    public function restablecerContrasena(string $token, string $password): bool
    {
        $tokenHash = hash('sha256', $token);
        $this->db->begin_transaction();
        try {
            $consulta = $this->db->prepare(
                'SELECT id, usuario_id FROM recuperacion_contrasenas
                 WHERE token_hash = ? AND usado_en IS NULL AND expira_en >= NOW()
                 LIMIT 1 FOR UPDATE'
            );
            $consulta->bind_param('s', $tokenHash);
            $consulta->execute();
            $registro = $consulta->get_result()->fetch_assoc() ?: null;
            $consulta->close();

            if ($registro === null) {
                $this->db->rollback();
                return false;
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $actualizar = $this->db->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?');
            $actualizar->bind_param('si', $passwordHash, $registro['usuario_id']);
            $actualizar->execute();
            $actualizar->close();

            $marcar = $this->db->prepare('UPDATE recuperacion_contrasenas SET usado_en = NOW() WHERE id = ?');
            $marcar->bind_param('i', $registro['id']);
            $marcar->execute();
            $marcar->close();
            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

    public function obtenerUsuarios(): array
    {
        $consulta = $this->db->prepare(
            'SELECT id, nombres, ap_paterno, ap_materno, email, rol, created_at
             FROM usuarios
             ORDER BY id DESC'
        );
        $consulta->execute();
        $usuarios = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);
        $consulta->close();
        return $usuarios;
    }

    public function obtenerUsuario(int $id): ?array
    {
        $consulta = $this->db->prepare(
            'SELECT id, nombres, ap_paterno, ap_materno, email, rol
             FROM usuarios WHERE id = ? LIMIT 1'
        );
        $consulta->bind_param('i', $id);
        $consulta->execute();
        $usuario = $consulta->get_result()->fetch_assoc() ?: null;
        $consulta->close();
        return $usuario;
    }

    public function guardarUsuario(array $datos, ?int $id = null): int
    {
        if ($id === null) {
            $consulta = $this->db->prepare(
                'INSERT INTO usuarios
                    (nombres, ap_paterno, ap_materno, email, password_hash, rol)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $consulta->bind_param(
                'ssssss',
                $datos['nombres'], $datos['ap_paterno'], $datos['ap_materno'],
                $datos['email'], $datos['password_hash'], $datos['rol']
            );
            $consulta->execute();
            $id = $this->db->insert_id;
        } elseif ($datos['password_hash'] !== '') {
            $consulta = $this->db->prepare(
                'UPDATE usuarios
                 SET nombres = ?, ap_paterno = ?, ap_materno = ?, email = ?,
                     password_hash = ?, rol = ?
                 WHERE id = ?'
            );
            $consulta->bind_param(
                'sssssssi',
                $datos['nombres'], $datos['ap_paterno'], $datos['ap_materno'],
                $datos['email'], $datos['password_hash'], $datos['rol'], $id
            );
            $consulta->execute();
        } else {
            $consulta = $this->db->prepare(
                'UPDATE usuarios
                 SET nombres = ?, ap_paterno = ?, ap_materno = ?, email = ?, rol = ?
                 WHERE id = ?'
            );
            $consulta->bind_param(
                'sssssi',
                $datos['nombres'], $datos['ap_paterno'], $datos['ap_materno'],
                $datos['email'], $datos['rol'], $id
            );
            $consulta->execute();
        }
        $consulta->close();
        return $id;
    }

    public function obtenerReportajes(?int $usuarioId = null): array
    {
        $filtro = $usuarioId === null ? '' : ' WHERE r.usuario_id = ? ';
        $consulta = $this->db->prepare(
            'SELECT r.id, r.titulo, r.resumen_corto, r.foto_principal, r.estado,
                    r.fecha_publicacion, r.es_destacado,
                    CONCAT_WS(" ", a.nombres, a.ap_paterno, a.ap_materno) AS autor,
                    COUNT(rf.id) AS total_fotos,
                    GROUP_CONCAT(rf.url_foto ORDER BY rf.orden, rf.id SEPARATOR "||") AS fotos
             FROM reportajes r
             LEFT JOIN autores a ON a.id = r.autor_id
             LEFT JOIN reportajes_fotos rf ON rf.reportaje_id = r.id
             ' . $filtro . '
             GROUP BY r.id, r.titulo, r.resumen_corto, r.foto_principal,
                      r.estado, r.fecha_publicacion, r.es_destacado,
                      a.nombres, a.ap_paterno, a.ap_materno
             ORDER BY r.fecha_publicacion DESC, r.id DESC'
        );
        if ($usuarioId !== null) {
            $consulta->bind_param('i', $usuarioId);
        }
        $consulta->execute();
        $resultado = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);
        $consulta->close();

        return $resultado;
    }

    public function obtenerDecoracionReportajes(): ?string
    {
        $consulta = $this->db->prepare('SELECT valor FROM configuracion_sitio WHERE clave = ? LIMIT 1');
        $clave = 'decoracion_reportajes';
        $consulta->bind_param('s', $clave);
        $consulta->execute();
        $resultado = $consulta->get_result()->fetch_assoc();
        $consulta->close();
        return $resultado['valor'] ?? null;
    }

    public function guardarDecoracionReportajes(string $valor): void
    {
        $consulta = $this->db->prepare(
            'INSERT INTO configuracion_sitio (clave, valor) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
        );
        $clave = 'decoracion_reportajes';
        $consulta->bind_param('ss', $clave, $valor);
        $consulta->execute();
        $consulta->close();
    }

    public function obtenerPodcasts(?int $usuarioId = null): array
    {
        $filtro = $usuarioId === null ? '' : ' WHERE usuario_id = ? ';
        $consulta = $this->db->prepare(
            'SELECT id, titulo, url_embed, portada, estado, fecha_publicacion
             FROM podcasts
             ' . $filtro . '
             ORDER BY fecha_publicacion DESC, id DESC'
        );
        if ($usuarioId !== null) $consulta->bind_param('i', $usuarioId);
        $consulta->execute();
        $podcasts = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);
        $consulta->close();

        return $podcasts;
    }

    public function obtenerPodcast(int $id, ?int $usuarioId = null): ?array
    {
        $filtro = $usuarioId === null ? '' : ' AND usuario_id = ? ';
        $consulta = $this->db->prepare(
            'SELECT id, titulo, url_embed, portada, estado, fecha_publicacion
             FROM podcasts
             WHERE id = ?' . $filtro . '
             LIMIT 1'
        );
        if ($usuarioId === null) $consulta->bind_param('i', $id);
        else $consulta->bind_param('ii', $id, $usuarioId);
        $consulta->execute();
        $podcast = $consulta->get_result()->fetch_assoc() ?: null;
        $consulta->close();

        return $podcast;
    }

    public function guardarPodcast(array $datos, int $usuarioId, ?int $id = null): int
    {
        if ($id === null) {
            $consulta = $this->db->prepare(
                'INSERT INTO podcasts (titulo, url_embed, portada, estado, fecha_publicacion, usuario_id)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $consulta->bind_param(
                'sssssi',
                $datos['titulo'], $datos['url_embed'], $datos['portada'],
                $datos['estado'], $datos['fecha_publicacion'],
                $usuarioId
            );
            $consulta->execute();
            $id = $this->db->insert_id;
            $consulta->close();
        } else {
            $consulta = $this->db->prepare(
                'UPDATE podcasts
                 SET titulo = ?, url_embed = ?, portada = ?, estado = ?, fecha_publicacion = ?
                 WHERE id = ?'
            );
            $consulta->bind_param(
                'sssssi',
                $datos['titulo'], $datos['url_embed'], $datos['portada'],
                $datos['estado'], $datos['fecha_publicacion'],
                $id
            );
            $consulta->execute();
            $consulta->close();
        }

        return $id;
    }

    public function obtenerNoticias(?int $usuarioId = null): array
    {
        $filtro = $usuarioId === null ? '' : ' WHERE usuario_id = ? ';
        $consulta = $this->db->prepare(
            'SELECT id, titulo, foto, link_externo, estado, fecha_publicacion
             FROM noticias
             ' . $filtro . '
             ORDER BY fecha_publicacion DESC, id DESC'
        );
        if ($usuarioId !== null) $consulta->bind_param('i', $usuarioId);
        $consulta->execute();
        $noticias = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);
        $consulta->close();
        return $noticias;
    }

    public function obtenerNoticia(int $id, ?int $usuarioId = null): ?array
    {
        $filtro = $usuarioId === null ? '' : ' AND usuario_id = ? ';
        $consulta = $this->db->prepare(
            'SELECT id, titulo, foto, link_externo, estado, fecha_publicacion
             FROM noticias WHERE id = ?' . $filtro . ' LIMIT 1'
        );
        if ($usuarioId === null) $consulta->bind_param('i', $id);
        else $consulta->bind_param('ii', $id, $usuarioId);
        $consulta->execute();
        $noticia = $consulta->get_result()->fetch_assoc() ?: null;
        $consulta->close();
        return $noticia;
    }

    public function guardarNoticia(array $datos, int $usuarioId, ?int $id = null): int
    {
        if ($id === null) {
            $consulta = $this->db->prepare(
                'INSERT INTO noticias (titulo, foto, link_externo, estado, fecha_publicacion, usuario_id)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $consulta->bind_param(
                'sssssi',
                $datos['titulo'], $datos['foto'], $datos['link_externo'],
                $datos['estado'], $datos['fecha_publicacion'], $usuarioId
            );
            $consulta->execute();
            $id = $this->db->insert_id;
        } else {
            $consulta = $this->db->prepare(
                'UPDATE noticias
                 SET titulo = ?, foto = ?, link_externo = ?, estado = ?, fecha_publicacion = ?
                 WHERE id = ?'
            );
            $consulta->bind_param(
                'sssssi',
                $datos['titulo'], $datos['foto'], $datos['link_externo'],
                $datos['estado'], $datos['fecha_publicacion'], $id
            );
            $consulta->execute();
        }
        $consulta->close();
        return $id;
    }

    public function obtenerVideos(?int $usuarioId = null): array
    {
        $filtro = $usuarioId === null ? '' : ' WHERE usuario_id = ? ';
        $consulta = $this->db->prepare(
            'SELECT id, titulo, url_embed, portada, palabras_resaltadas, color_resaltado, modo_portada, estado, fecha_publicacion
             FROM videos
             ' . $filtro . '
             ORDER BY fecha_publicacion DESC, id DESC'
        );
        if ($usuarioId !== null) $consulta->bind_param('i', $usuarioId);
        $consulta->execute();
        $videos = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);
        $consulta->close();
        return $videos;
    }

    public function obtenerVideo(int $id, ?int $usuarioId = null): ?array
    {
        $filtro = $usuarioId === null ? '' : ' AND usuario_id = ? ';
        $consulta = $this->db->prepare(
            'SELECT id, titulo, url_embed, portada, palabras_resaltadas, color_resaltado, modo_portada, estado, fecha_publicacion
             FROM videos WHERE id = ?' . $filtro . ' LIMIT 1'
        );
        if ($usuarioId === null) $consulta->bind_param('i', $id);
        else $consulta->bind_param('ii', $id, $usuarioId);
        $consulta->execute();
        $video = $consulta->get_result()->fetch_assoc() ?: null;
        $consulta->close();
        return $video;
    }

    public function guardarVideo(array $datos, int $usuarioId, ?int $id = null): int
    {
        if ($id === null) {
            $consulta = $this->db->prepare(
                'INSERT INTO videos
                    (titulo, url_embed, portada, palabras_resaltadas, color_resaltado, modo_portada, estado, fecha_publicacion, usuario_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $consulta->bind_param(
                'ssssssssi',
                $datos['titulo'], $datos['url_embed'], $datos['portada'],
                $datos['palabras_resaltadas'], $datos['color_resaltado'], $datos['modo_portada'],
                $datos['estado'], $datos['fecha_publicacion'], $usuarioId
            );
            $consulta->execute();
            $id = $this->db->insert_id;
        } else {
            $consulta = $this->db->prepare(
                'UPDATE videos
                 SET titulo = ?, url_embed = ?, palabras_resaltadas = ?, color_resaltado = ?, modo_portada = ?,
                     estado = ?, fecha_publicacion = ?
                 WHERE id = ?'
            );
            $consulta->bind_param(
                'sssssssi',
                $datos['titulo'], $datos['url_embed'], $datos['palabras_resaltadas'],
                $datos['color_resaltado'], $datos['modo_portada'], $datos['estado'], $datos['fecha_publicacion'], $id
            );
            $consulta->execute();
        }
        $consulta->close();
        return $id;
    }

    public function obtenerBoletines(?int $usuarioId = null): array
    {
        $filtro = $usuarioId === null ? '' : ' WHERE usuario_id = ? ';
        $consulta = $this->db->prepare(
            'SELECT id, numero_boletin, titulo_boletin, resumen, foto_portada, archivo_pdf, estado, fecha_publicacion
             FROM boletines
             ' . $filtro . '
             ORDER BY fecha_publicacion DESC, id DESC'
        );
        if ($usuarioId !== null) $consulta->bind_param('i', $usuarioId);
        $consulta->execute();
        $boletines = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);
        $consulta->close();
        return $boletines;
    }

    public function eliminarContenido(string $tipo, int $id, ?int $usuarioId = null): void
    {
        $tablas = [
            'reportaje' => 'reportajes',
            'boletin' => 'boletines',
            'noticia' => 'noticias',
            'podcast' => 'podcasts',
            'video' => 'videos',
        ];
        if ($id < 1 || !isset($tablas[$tipo])) {
            throw new InvalidArgumentException('Contenido no válido para eliminar.');
        }
        $filtro = $usuarioId === null ? '' : ' AND usuario_id = ?';
        $consulta = $this->db->prepare('DELETE FROM ' . $tablas[$tipo] . ' WHERE id = ?' . $filtro);
        if ($usuarioId === null) $consulta->bind_param('i', $id);
        else $consulta->bind_param('ii', $id, $usuarioId);
        $consulta->execute();
        $consulta->close();
    }

    public function obtenerBoletin(int $id, ?int $usuarioId = null): ?array
    {
        $filtro = $usuarioId === null ? '' : ' AND usuario_id = ? ';
        $consulta = $this->db->prepare(
            'SELECT id, numero_boletin, titulo_boletin, resumen, foto_portada, archivo_pdf, estado, fecha_publicacion
             FROM boletines WHERE id = ?' . $filtro . ' LIMIT 1'
        );
        if ($usuarioId === null) $consulta->bind_param('i', $id);
        else $consulta->bind_param('ii', $id, $usuarioId);
        $consulta->execute();
        $boletin = $consulta->get_result()->fetch_assoc() ?: null;
        $consulta->close();
        return $boletin;
    }

    public function guardarBoletin(array $datos, int $usuarioId, ?int $id = null): int
    {
        if ($id === null) {
            $consulta = $this->db->prepare(
                'INSERT INTO boletines
                    (numero_boletin, titulo_boletin, resumen, foto_portada, archivo_pdf, estado, fecha_publicacion, usuario_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $consulta->bind_param(
                'sssssssi',
                $datos['numero_boletin'], $datos['titulo_boletin'], $datos['resumen'], $datos['foto_portada'],
                $datos['archivo_pdf'], $datos['estado'], $datos['fecha_publicacion'], $usuarioId
            );
            $consulta->execute();
            $id = $this->db->insert_id;
        } else {
            $consulta = $this->db->prepare(
                'UPDATE boletines
                 SET numero_boletin = ?, titulo_boletin = ?, resumen = ?, foto_portada = ?, archivo_pdf = ?,
                     estado = ?, fecha_publicacion = ?
                 WHERE id = ?'
            );
            $consulta->bind_param(
                'sssssssi',
                $datos['numero_boletin'], $datos['titulo_boletin'], $datos['resumen'], $datos['foto_portada'],
                $datos['archivo_pdf'], $datos['estado'], $datos['fecha_publicacion'], $id
            );
            $consulta->execute();
        }
        $consulta->close();
        return $id;
    }

    public function crearReportaje(array $datos, int $usuarioId): void
    {
        $this->db->begin_transaction();

        try {
            $autor = $this->db->prepare(
                'INSERT INTO autores (nombres, ap_paterno, ap_materno)
                 VALUES (?, ?, ?)'
            );
            $autor->bind_param(
                'sss',
                $datos['autor_nombres'],
                $datos['autor_ap_paterno'],
                $datos['autor_ap_materno']
            );
            $autor->execute();
            $autorId = $this->db->insert_id;
            $autor->close();

            $reportaje = $this->db->prepare(
                'INSERT INTO reportajes
                    (titulo, resumen_corto, desarrollo, foto_principal, estado,
                     fecha_publicacion, es_destacado, autor_id, usuario_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $reportaje->bind_param(
                'ssssssiii',
                $datos['titulo'],
                $datos['resumen_corto'],
                $datos['desarrollo'],
                $datos['foto_principal'],
                $datos['estado'],
                $datos['fecha_publicacion'],
                $datos['es_destacado'],
                $autorId,
                $usuarioId
            );
            $reportaje->execute();
            $reportaje->close();
            $this->db->commit();
        } catch (mysqli_sql_exception | RuntimeException $exception) {
            $this->db->rollback();
            throw $exception;
        }
    }

        public function obtenerReportaje(int $id, ?int $usuarioId = null): ?array
        {
            $filtro = $usuarioId === null ? '' : ' AND r.usuario_id = ? ';
            $consulta = $this->db->prepare(
                'SELECT r.*, a.nombres AS autor_nombres, a.ap_paterno AS autor_ap_paterno,
                        a.ap_materno AS autor_ap_materno
                 FROM reportajes r
                 INNER JOIN autores a ON a.id = r.autor_id
                 WHERE r.id = ?' . $filtro . '
                 LIMIT 1'
            );
            if ($usuarioId === null) $consulta->bind_param('i', $id);
            else $consulta->bind_param('ii', $id, $usuarioId);
            $consulta->execute();
            $reportaje = $consulta->get_result()->fetch_assoc() ?: null;
            $consulta->close();

            if ($reportaje !== null) {
                $fotos = $this->db->prepare(
                    'SELECT id, url_foto, orden, parrafo_despues, descripcion
                     FROM reportajes_fotos
                     WHERE reportaje_id = ?
                     ORDER BY orden, id'
                );
                $fotos->bind_param('i', $id);
                $fotos->execute();
                $reportaje['fotos'] = $fotos->get_result()->fetch_all(MYSQLI_ASSOC);
                $fotos->close();
            }

            return $reportaje;
        }

        public function guardarReportaje(array $datos, int $usuarioId, ?int $id = null): int
        {
            $this->db->begin_transaction();

            try {
                if ($id === null) {
                    $autor = $this->db->prepare(
                        'INSERT INTO autores (nombres, ap_paterno, ap_materno)
                         VALUES (?, ?, ?)'
                    );
                    $autor->bind_param('sss', $datos['autor_nombres'], $datos['autor_ap_paterno'], $datos['autor_ap_materno']);
                    $autor->execute();
                    $autorId = $this->db->insert_id;
                    $autor->close();

                    $reportaje = $this->db->prepare(
                        'INSERT INTO reportajes
                        (titulo, resumen_corto, desarrollo, foto_principal, decoracion_imagen, pdf_adjunto,
                         estado, fecha_publicacion, es_destacado, autor_id, usuario_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $reportaje->bind_param(
                        'ssssssssiii',
                        $datos['titulo'], $datos['resumen_corto'], $datos['desarrollo'],
                        $datos['foto_principal'], $datos['decoracion_imagen'], $datos['pdf_adjunto'], $datos['estado'],
                        $datos['fecha_publicacion'], $datos['es_destacado'], $autorId, $usuarioId
                    );
                    $reportaje->execute();
                    $id = $this->db->insert_id;
                    $reportaje->close();
                } else {
                    $autorId = (int) $datos['autor_id'];
                    $autor = $this->db->prepare(
                        'UPDATE autores SET nombres = ?, ap_paterno = ?, ap_materno = ? WHERE id = ?'
                    );
                    $autor->bind_param('sssi', $datos['autor_nombres'], $datos['autor_ap_paterno'], $datos['autor_ap_materno'], $autorId);
                    $autor->execute();
                    $autor->close();

                    $reportaje = $this->db->prepare(
                        'UPDATE reportajes
                         SET titulo = ?, resumen_corto = ?, desarrollo = ?, foto_principal = ?,
                             decoracion_imagen = ?, pdf_adjunto = ?, estado = ?, fecha_publicacion = ?, es_destacado = ?
                         WHERE id = ?'
                    );
                    $reportaje->bind_param(
                        'ssssssssii',
                        $datos['titulo'], $datos['resumen_corto'], $datos['desarrollo'],
                        $datos['foto_principal'], $datos['decoracion_imagen'], $datos['pdf_adjunto'], $datos['estado'],
                        $datos['fecha_publicacion'], $datos['es_destacado'], $id
                    );
                    $reportaje->execute();
                    $reportaje->close();
                }

                $fotoActualizacion = $this->db->prepare(
                    'UPDATE reportajes_fotos
                     SET orden = ?, parrafo_despues = ?, descripcion = ?
                     WHERE id = ? AND reportaje_id = ?'
                );
                foreach ($datos['fotos_existentes'] ?? [] as $fotoId => $ubicacion) {
                    $orden = max(1, (int) ($ubicacion['orden'] ?? 1));
                    $parrafo = max(0, (int) ($ubicacion['parrafo_despues'] ?? 0));
                    $descripcion = trim((string) ($ubicacion['descripcion'] ?? ''));
                    $fotoId = (int) $fotoId;
                    $fotoActualizacion->bind_param('iisii', $orden, $parrafo, $descripcion, $fotoId, $id);
                    $fotoActualizacion->execute();
                }
                $fotoActualizacion->close();

                if (!empty($datos['fotos_eliminar']) && is_array($datos['fotos_eliminar'])) {
                    $fotoEliminacion = $this->db->prepare(
                        'DELETE FROM reportajes_fotos WHERE id = ? AND reportaje_id = ?'
                    );
                    foreach ($datos['fotos_eliminar'] as $fotoId) {
                        $fotoId = (int) $fotoId;
                        $fotoEliminacion->bind_param('ii', $fotoId, $id);
                        $fotoEliminacion->execute();
                    }
                    $fotoEliminacion->close();
                }

                if (!empty($datos['fotos_reemplazos']) && is_array($datos['fotos_reemplazos'])) {
                    $fotoReemplazo = $this->db->prepare(
                        'UPDATE reportajes_fotos SET url_foto = ? WHERE id = ? AND reportaje_id = ?'
                    );
                    foreach ($datos['fotos_reemplazos'] as $fotoId => $url) {
                        $fotoId = (int) $fotoId;
                        $fotoReemplazo->bind_param('sii', $url, $fotoId, $id);
                        $fotoReemplazo->execute();
                    }
                    $fotoReemplazo->close();
                }

                if ($datos['fotos_nuevas'] !== []) {
                    $foto = $this->db->prepare(
                        'INSERT INTO reportajes_fotos
                            (reportaje_id, url_foto, orden, parrafo_despues, descripcion)
                         VALUES (?, ?, ?, ?, ?)'
                    );
                    foreach ($datos['fotos_nuevas'] as $nuevaFoto) {
                        $url = $nuevaFoto['url'];
                        $orden = max(1, (int) $nuevaFoto['orden']);
                        $parrafo = max(0, (int) $nuevaFoto['parrafo_despues']);
                        $descripcion = $nuevaFoto['descripcion'];
                        $foto->bind_param('isiis', $id, $url, $orden, $parrafo, $descripcion);
                        $foto->execute();
                    }
                    $foto->close();
                }

                $this->db->commit();
                return $id;
            } catch (mysqli_sql_exception | RuntimeException $exception) {
                $this->db->rollback();
                throw $exception;
            }
        }

    public function close(): void
    {
        $this->db->close();
    }
}