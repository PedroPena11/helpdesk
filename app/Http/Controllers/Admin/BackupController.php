<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Auditoria;
use Illuminate\Support\Facades\DB;
use Laravel\Reverb\Loggers\Log;

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
                            
                            $valuesMapped = array_map(function($value) {
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
        if (!Storage::disk('backups')->exists($filename)) {
            return response()->json(['message' => 'El archivo no existe.'], 404);
        }

        return Storage::disk('backups')->download($filename);
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
}
