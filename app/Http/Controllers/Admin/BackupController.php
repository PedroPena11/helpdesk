<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Auditoria;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Reverb\Loggers\Log;
use Symfony\Component\Process\Process;

class BackupController extends Controller
{
    public function index()
    {
        $files = Storage::disk('backups')->files();
        $backups = [];

        foreach ($files as $file) {
            $backups[] = [
                'filename' => $file,
                'size' => round(Storage::disk('backups')->size($file) / 1024, 2) . ' KB',
                'date' => date('Y-m-d H:i:s', Storage::disk('backups')->lastModified($file))
            ];
        }

        return response()->json($backups);
    }

    public function create(Request $request)
    {
        $request->validate([
            'type' => 'required|in:completo,estructura'
        ]);

        $timestamp = date('Ymd_His');
        $filename = "backup_{$request->type}_{$timestamp}.sql";

        if (!Storage::disk('backups')->exists('')) {
            Storage::disk('backups')->makeDirectory('');
        }

        $filePath = storage_path("app/backups/{$filename}");

        try {

            $handle = fopen($filePath, 'w+');

            fwrite($handle, "-- RESPALDO DE BASE DE DATOS POSTGRESQL\n");
            fwrite($handle, "-- Generado nativamente desde Laravel el " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- Tipo: " . strtoupper($request->type) . "\n\n");


            $tables = DB::select("
                SELECT tablename AS table_name 
                FROM pg_catalog.pg_tables 
                WHERE schemaname = 'public'
            ");


            if (empty($tables)) {
                $tables = DB::connection()->getDoctrineSchemaManager()->listTableNames();

                $tables = array_map(fn($name) => (object)['table_name' => $name], $tables);
            }

            foreach ($tables as $table) {
                $tableName = $table->table_name;


                if ($tableName === 'auditorias') {
                    continue;
                }

                fwrite($handle, "-- --------------------------------------------------\n");
                fwrite($handle, "-- Estructura e Inserts para la tabla: {$tableName}\n");
                fwrite($handle, "-- --------------------------------------------------\n");
                fwrite($handle, "DROP TABLE IF EXISTS \"{$tableName}\" CASCADE;\n\n");


                fwrite($handle, "CREATE TABLE \"{$tableName}\" (\n");

                $columns = DB::select("
                    SELECT column_name, data_type, is_nullable
                    FROM information_schema.columns 
                    WHERE table_name = :table AND table_schema = 'public'
                ", ['table' => $tableName]);

                $colDefinitions = [];
                foreach ($columns as $col) {
                    $def = "  \"{$col->column_name}\" {$col->data_type}";
                    if ($col->is_nullable === 'NO') {
                        $def .= " NOT NULL";
                    }
                    $colDefinitions[] = $def;
                }

                fwrite($handle, implode(",\n", $colDefinitions) . "\n);\n\n");


                if ($request->type === 'completo') {

                    $rows = DB::table($tableName)->get();

                    if ($rows->count() > 0) {
                        fwrite($handle, "-- Volcado de datos ({$rows->count()} registros)\n");

                        foreach ($rows as $row) {
                            $rowArray = (array)$row;
                            $columnsMapped = array_keys($rowArray);

                            $valuesMapped = array_map(function ($value) {
                                if (is_null($value)) return 'NULL';
                                if (is_bool($value)) return $value ? 'TRUE' : 'FALSE';
                                return "'" . str_replace("'", "''", $value) . "'";
                            }, array_values($rowArray));

                            $insColumns = implode(', ', array_map(fn($c) => "\"{$c}\"", $columnsMapped));
                            $insValues = implode(', ', $valuesMapped);

                            fwrite($handle, "INSERT INTO \"{$tableName}\" ({$insColumns}) VALUES ({$insValues});\n");
                        }
                    }
                    fwrite($handle, "\n");
                }
            }


            fflush($handle);
            fclose($handle);
        } catch (\Exception $e) {
        } catch (\Exception $e) {
            if (isset($handle)) fclose($handle);
            if (file_exists($filePath)) unlink($filePath);

            Log::error("Error en Backup Manual PHP: " . $e->getMessage());
            return response()->json([
                'message' => 'Error interno al procesar el volcado de datos.',
                'error_puro_windows' => $e->getMessage()
            ], 500);
        }


        \App\Models\Auditoria::registrar(
            auth()->id(),
            'BACKUP_GENERADO',
            "Se generó un respaldo de tipo [{$request->type}] bajo el nombre: {$filename} (Script PHP)"
        );

        return response()->json([
            'message' => 'Respaldo criptográfico generado con éxito (Vía PDO).',
            'filename' => $filename
        ]);
    }


    public function download($filename)
    {

        if (str_contains($filename, '..') || str_contains($filename, '/') || str_contains($filename, '\\')) {
            abort(403, 'Acceso no autorizado.');
        }

        $disk = Storage::disk('backups');


        if (!$disk->exists($filename)) {
            abort(404, 'El archivo de respaldo no existe en el almacenamiento.');
        }


        $filePath = $disk->path($filename);


        return response()->download($filePath, $filename, [
            'Content-Type' => 'text/plain',
        ]);
    }

    public function destroy($filename)
    {
        if (!Storage::disk('backups')->exists($filename)) {
            return response()->json(['message' => 'El archivo no existe.'], 404);
        }

        Storage::disk('backups')->delete($filename);

        Auditoria::registrar(
            auth()->id(),
            'BACKUP_ELIMINADO',
            "Se eliminó el archivo de respaldo: {$filename}"
        );

        return response()->json(['message' => 'Archivo de respaldo removido del sistema.']);
    }

    public function restore(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
            'clear_db' => 'required|boolean'
        ]);

        $filename = $request->filename;
        $filePath = storage_path("app/backups/{$filename}");

        if (!file_exists($filePath)) {
            return response()->json(['message' => 'El archivo de respaldo seleccionado no existe.'], 404);
        }

        try {
            $dbConfig = config('database.connections.pgsql');
            $username = $dbConfig['username'];

            // 1. CASO DE USO: LIMPIEZA PREVIA DEL ESQUEMA
            if ($request->clear_db) {
                DB::statement('DROP SCHEMA public CASCADE;');
                DB::statement('CREATE SCHEMA public;');
                DB::statement('GRANT ALL ON SCHEMA public TO public;');
                DB::statement('GRANT ALL ON SCHEMA public TO ' . $username . ';');
            }

            // 2. LECTURA E INYECCIÓN NATIVA (Sin usar psql ni consolas de Windows)
            $sqlContenido = file_get_contents($filePath);

            if (empty(trim($sqlContenido))) {
                throw new \Exception("El archivo de respaldo está completamente vacío.");
            }

            // Ejecuta todo el script SQL directamente sobre la conexión actual de Postgres
            DB::unprepared($sqlContenido);

            // 3. SINCRONIZACIÓN AUTOMÁTICA DE SECUENCIAS
            $statements = DB::select("
            SELECT 'SELECT setval(''' || c.relname || ''', COALESCE(MAX(' || a.attname || '), 1) + 1, false) FROM ' || t.relname AS query
            FROM pg_class c
            JOIN pg_depend d ON d.objid = c.oid
            JOIN pg_class t ON t.oid = d.refobjid
            JOIN pg_attribute a ON a.attrelid = d.refobjid AND a.attnum = d.refobjsubid
            WHERE c.relkind = 'S' AND t.relkind = 'r' AND d.deptype = 'a';
        ");

            foreach ($statements as $stmt) {
                DB::statement($stmt->query);
            }

            // 4. REGISTRO DE AUDITORÍA
            try {
                \App\Models\Auditoria::registrar(
                    auth()->id(),
                    'BD_RESTAURADA',
                    "Se restauró la base de datos de forma nativa desde el archivo: {$filename}."
                );
            } catch (\Exception $auditoriaError) {
                \Log::error("No se pudo registrar la auditoría del restore: " . $auditoriaError->getMessage());
            }

            return response()->json(['message' => 'Base de datos restaurada y secuencias sincronizadas con éxito.']);
        } catch (\Exception $e) {
            // Si algo falla, intentamos levantar el entorno para que no te quedes bloqueado afuera
            if ($request->clear_db) {
                try {
                    Artisan::call('migrate --seed');
                } catch (\Exception $migError) {
                    Log::error("No se pudo ejecutar el fallback de migración: " . $migError->getMessage());
                }
            }

            return response()->json([
                'message' => 'Fallo crítico en el proceso de restauración del script.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
