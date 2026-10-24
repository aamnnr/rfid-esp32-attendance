<?php
/**
 * Akses database berbasis mysqli + prepared statement.
 * Semua query yang menerima input pengguna WAJIB lewat fungsi di sini.
 */

function db()
{
    static $conn = null;

    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn = new mysqli(
            config('db.host', '127.0.0.1'),
            config('db.user', 'root'),
            config('db.pass', ''),
            config('db.name'),
            (int) config('db.port', 3306)
        );
        $conn->set_charset('utf8mb4');
        // Samakan zona waktu MySQL dengan PHP agar CURDATE()/NOW() konsisten.
        $conn->query("SET time_zone = '" . date('P') . "'");
    }

    return $conn;
}

/**
 * Jalankan query dengan parameter terikat dan kembalikan statement-nya.
 * Tipe parameter ditentukan otomatis (int => i, float => d, lainnya => s).
 */
function db_run($sql, array $params = [])
{
    $stmt = db()->prepare($sql);

    if ($params) {
        $types = '';
        foreach ($params as $param) {
            $types .= is_int($param) ? 'i' : (is_float($param) ? 'd' : 's');
        }
        $values = array_values($params);
        $stmt->bind_param($types, ...$values);
    }

    $stmt->execute();
    return $stmt;
}

function db_all($sql, array $params = [])
{
    $stmt = db_run($sql, $params);
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

function db_one($sql, array $params = [])
{
    $rows = db_all($sql, $params);
    return $rows ? $rows[0] : null;
}

function db_value($sql, array $params = [])
{
    $row = db_one($sql, $params);
    return $row ? reset($row) : null;
}

/**
 * Jalankan INSERT/UPDATE/DELETE, kembalikan jumlah baris yang terpengaruh.
 */
function db_exec($sql, array $params = [])
{
    $stmt = db_run($sql, $params);
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $affected;
}

function db_insert_id()
{
    return (int) db()->insert_id;
}

/**
 * Jalankan $callback di dalam transaksi; rollback otomatis jika gagal.
 */
function db_transaction(callable $callback)
{
    $conn = db();
    $conn->begin_transaction();
    try {
        $result = $callback();
        $conn->commit();
        return $result;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

function is_duplicate_key_error(Throwable $e)
{
    return $e instanceof mysqli_sql_exception && $e->getCode() === 1062;
}
