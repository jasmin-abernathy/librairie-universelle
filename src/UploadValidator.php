<?php

declare(strict_types=1);

final class UploadValidator
{
    public static function validateAndStore(array $file, string $kind, array $config): array
    {
        $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if (in_array($uploadError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            throw new RuntimeException('Fichier trop volumineux pour les limites de l’hébergement. Réduisez sa taille avant un nouvel envoi.');
        }
        if ($uploadError === UPLOAD_ERR_PARTIAL) {
            throw new RuntimeException('Envoi interrompu avant la réception complète du fichier. Réessayez.');
        }
        if ($uploadError !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Fichier absent ou transfert invalide.');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        $name = basename((string) ($file['name'] ?? 'fichier'));
        $size = (int) ($file['size'] ?? 0);
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('Le fichier reçu n’est pas un upload HTTP valide.');
        }

        $limits = [
            'ebook' => (int) $config['max_epub_bytes'],
            'print_pdf' => (int) $config['max_pdf_bytes'],
            'cover' => (int) $config['max_cover_bytes'],
        ];
        if (!isset($limits[$kind]) || $size < 1 || $size > $limits[$kind]) {
            throw new RuntimeException('Taille de fichier invalide.');
        }

        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowedExtensions = [
            'ebook' => ['epub'],
            'print_pdf' => ['pdf'],
            'cover' => ['jpg', 'jpeg', 'png', 'webp'],
        ];
        if (!in_array($extension, $allowedExtensions[$kind], true)) {
            throw new RuntimeException('Extension de fichier non autorisée.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($tmp);
        self::validateContent($tmp, $kind, $mime);

        $storageRoot = rtrim((string) $config['storage_path'], '/');
        $directory = $storageRoot . '/submissions';
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Impossible de créer le stockage privé.');
        }

        $storedName = bin2hex(random_bytes(24)) . '.' . $extension;
        $destination = $directory . '/' . $storedName;
        if (!move_uploaded_file($tmp, $destination)) {
            throw new RuntimeException('Impossible de stocker le fichier.');
        }
        @chmod($destination, 0660);

        return [
            'original_name' => $name,
            'stored_name' => $storedName,
            'mime_type' => $mime,
            'size_bytes' => filesize($destination) ?: $size,
            'sha256' => hash_file('sha256', $destination),
            'path' => $destination,
        ];
    }

    private static function validateContent(string $path, string $kind, string $mime): void
    {
        if ($kind === 'print_pdf') {
            $header = file_get_contents($path, false, null, 0, 5);
            if ($header !== '%PDF-') {
                throw new RuntimeException('Le fichier PDF n’a pas une signature PDF valide.');
            }
            return;
        }

        if ($kind === 'cover') {
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                throw new RuntimeException('La couverture doit être une image JPEG, PNG ou WebP.');
            }
            if (@getimagesize($path) === false) {
                throw new RuntimeException('La couverture n’est pas une image valide.');
            }
            return;
        }

        if (!in_array($mime, ['application/epub+zip', 'application/zip', 'application/octet-stream'], true)) {
            throw new RuntimeException('Le fichier EPUB n’a pas un type reconnu.');
        }

        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($path) !== true) {
                throw new RuntimeException('Le fichier EPUB n’est pas une archive ZIP lisible.');
            }
            $mimetype = $zip->getFromName('mimetype');
            $container = $zip->locateName('META-INF/container.xml', ZipArchive::FL_NOCASE);
            $zip->close();
            if (trim((string) $mimetype) !== 'application/epub+zip' || $container === false) {
                throw new RuntimeException('Structure EPUB invalide ou incomplète.');
            }
        }
    }
}
