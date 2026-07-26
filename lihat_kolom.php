<?php
$database_file = 'melody_db';
$nama_tabel = 'orders';

try {
    $pdo = new PDO("sqlite:" . $database_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $query = $pdo->query("PRAGMA table_info(\"$nama_tabel\")");
    $kolom = $query->fetchAll(PDO::FETCH_ASSOC);

    if (empty($kolom)) {
        echo "Tabel '$nama_tabel' tidak ditemukan.\n";
    } else {
        echo "\n=== DAFTAR KOLOM TABEL: " . strtoupper($nama_tabel) . " ===\n\n";
        
        // Header Tabel di CMD
        printf("%-3s | %-25s | %-12s | %-8s | %-11s\n", "NO", "NAMA KOLOM", "TIPE DATA", "PRIMARY?", "NOT NULL?");
        echo str_repeat("-", 70) . "\n";
        
        // Isi Baris Kolom
        foreach ($kolom as $k) {
            printf(
                "%-3d | %-25s | %-12s | %-8s | %-11s\n",
                $k['cid'],
                $k['name'],
                $k['type'],
                $k['pk'] ? 'YA' : 'TIDAK',
                $k['notnull'] ? 'YA' : 'TIDAK'
            );
        }
        echo "\nTotal: " . count($kolom) . " kolom ditemukan.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
