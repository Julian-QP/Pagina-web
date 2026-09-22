<?php
declare(strict_types=1);

function esEditor(): bool
{
    return strtolower(trim((string) ($_SESSION['rol'] ?? ''))) === 'editor';
}

function esRedactor(): bool
{
    return strtolower(trim((string) ($_SESSION['rol'] ?? ''))) === 'redactor';
}

function usuarioActualId(): int
{
    return (int) ($_SESSION['usuario_id'] ?? 0);
}

function estadoContenidoPermitido(string $estado): string
{
    return esRedactor() ? 'borrador' : $estado;
}

function exigirGestionUsuarios(): void
{
    if (esEditor() || esRedactor()) {
        http_response_code(403);
        exit('No tienes permisos para gestionar usuarios.');
    }
}
