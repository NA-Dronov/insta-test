<?php

namespace App\DataProviders;

use App\Core\Config;
use \PDO;
use \stdClass;
use \closure;
use App\Core\TSingleton;
use DateTime;

class DatabaseOperator
{
    use TSingleton;

    private $pdo = null;

    private function __construct()
    {

        /**
         * @var Config $gc
         */
        $gc = Config::getInstance();
        $this->pdo = new PDO("mysql:host={$gc->get("db.hostname")};", $gc->get("db.login"), $gc->get("db.password"));
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $dbname = "`" . str_replace("`", "``", $gc->get("db.dbname")) . "`";
        $this->pdo->query("CREATE DATABASE IF NOT EXISTS $dbname");
        $this->pdo->query("use $dbname");
    }

    private function getColumns(string $table_name)
    {
        $rs = $this->pdo->query("SELECT * FROM ${table_name} LIMIT 0");
        for ($i = 0; $i < $rs->columnCount(); $i++) {
            $col = $rs->getColumnMeta($i);
            $columns[] = $col['name'];
        }

        return $columns;
    }

    private function sanitizeInput(string $table_name, array $data, array $params = [])
    {
        $columns = $this->getColumns($table_name);
        $primary_key_column = !empty($params['primary_key']) ? $params['primary_key'] : "${table_name}_id";

        $key_index = array_search($primary_key_column, $columns);

        $data = array_filter($data, function ($k) use ($columns) {
            return in_array($k, $columns);
        }, ARRAY_FILTER_USE_KEY);

        if ($key_index === false) {
            return false;
        }

        return [$primary_key_column, $data];
    }

    private function sanitizeOutput(string $table_name, array $data, array $params = [])
    {
        $columns = $this->getColumns($table_name);
        $primary_key_column = !empty($params['primary_key']) ? $params['primary_key'] : "${table_name}_id";

        $key_index = array_search($primary_key_column, $columns);

        if (!(count($data) == 1 && $data[0] == '*')) {
            $data = array_filter($data, function ($v) use ($columns) {
                return in_array($v, $columns);
            });
        }

        if ($key_index === false) {
            return false;
        }

        return [$primary_key_column, $data];
    }

    public function updateStmt($data, $table_name, $primary_key_column, array $params = [])
    {
        if (empty($data[$primary_key_column])) {
            return false;
        }

        $prepare_update = [];
        foreach ($data as $k => $v) {
            if ($k == $primary_key_column) {
                continue;
            }

            $prepare_update[] = "{$k}=:{$k}";
        }

        $prepare_update = implode(", ", $prepare_update);
        $prepare_where = "{$primary_key_column}=:{$primary_key_column}";

        $sql = "UPDATE {$table_name} SET {$prepare_update} WHERE {$prepare_where}";
        return $sql;
    }

    public function insertStmt(&$data, $table_name, $primary_key_column, array $params = [])
    {
        $prepare_insert = [];

        foreach ($data as $k => $v) {
            if ($k == $primary_key_column) {
                continue;
            }

            // Sepcial case: decimal field value. VERY BAD Practice
            if ($k == 'value') {
                $data[$k] = [
                    'v' => str_replace(',', '.', $v),
                    't' => PDO::PARAM_STR,
                ];
            }

            $prepare_insert[$k] = ":{$k}";
        }

        $columns = implode(', ', array_keys($prepare_insert));
        $values = implode(', ', $prepare_insert);

        $sql = "INSERT INTO {$table_name} ({$columns}) VALUES ({$values})";
        return $sql;
    }

    public function getStmt($data, $table_name, $primary_key_column, array $params = [])
    {
        $fields = empty($data) ? '*' : implode(', ', $data);
        $sql_data = [];
        $condition = $limit = $order = $offset = "";

        if (!empty($params['from'])) {
            $date_from = DateTime::createFromFormat('d/m/Y', $params['from']);
            if ($date_from !== false) {
                $condition .= " AND date >= :date_from";
                $date_from->setTime(0, 0);
                $sql_data['date_from'] = ['v' => $date_from->getTimestamp(), 't' => PDO::PARAM_INT];
            }
        }

        if (!empty($params['to'])) {
            $date_to = DateTime::createFromFormat('d/m/Y', $params['to']);
            if ($date_from !== false) {
                $condition .= " AND date <= :date_to";
                $date_to->setTime(0, 0);
                $sql_data['date_to'] = ['v' => $date_to->getTimestamp(), 't' => PDO::PARAM_INT];
            }
        }

        if (!empty($params['valueID'])) {
            $condition .= " AND valuteID = :valueID";
            $sql_data['valueID'] = ['v' => $params['valueID'], 't' => PDO::PARAM_STR];
        }

        if (!empty($params['page'])) {
            $params['per_page'] = $params['per_page'] ?? 25;
            $limit = "LIMIT :item_from, :item_to";
            $sql_data['item_from'] = ['v' => $params['per_page'] * ($params['page'] - 1), 't' => PDO::PARAM_INT];
            $sql_data['item_to'] = ['v' => $params['per_page'], 't' => PDO::PARAM_INT];
        }

        $sql = "SELECT {$fields} FROM {$table_name} WHERE 1 {$condition} {$limit}";
        return [$sql, $sql_data];
    }

    public function setOperation(closure $operation, string $table_name, array $data, array $params = [])
    {
        $result = $this->sanitizeInput($table_name, $data, $params);

        if ($result === false) {
            return $result;
        }

        list($primary_key_column, $data) = $result;

        $sql = $operation($data, $table_name, $primary_key_column, $params);
        unset($data[$primary_key_column]);

        if ($sql === false) {
            return $sql;
        }

        $stmt = $this->pdo->prepare($sql);

        foreach ($data as $field_name => $field_value) {
            if (is_array($field_value)) {
                $stmt->bindParam(":{$field_name}", $field_value['v'], $field_value['t']);
            } else {
                $stmt->bindParam(":{$field_name}", $field_value);
            }
        }

        $stmt->execute();
    }

    public function getOperation(closure $operation, string $table_name, array $data, array $params = [], $class)
    {
        $result = $this->sanitizeOutput($table_name, $data, $params);

        if ($result === false) {
            return $result;
        }

        list($primary_key_column, $data) = $result;

        list($count_sql, $count_sql_data) = $operation(['COUNT(*)'], $table_name, $primary_key_column, array_filter($params, function ($k) {
            return !in_array($k, ['page', 'per_page']);
        }, ARRAY_FILTER_USE_KEY));

        $per_page = $params['per_page'] ?? 0;
        $stmt = $this->pdo->prepare($count_sql);
        foreach ($count_sql_data as $count_sql_name => $count_sql_data_param) {
            $stmt->bindParam(":{$count_sql_name}", $count_sql_data_param['v'], $count_sql_data_param['t']);
        }
        $stmt->execute();
        $count_result = $stmt->fetchColumn();
        $pages_count = $per_page == 0 ? 1 : round($count_result / $per_page, 0);
        $params['total'] = $pages_count;
        if (!empty($params['page']) || !empty($params['per_page'])) {
            $params['page'] = $params['page'] ?? 0;
            $params['page'] = empty($params['page']) ? 1 : ($params['page'] > $pages_count ? $pages_count : ($params['page'] < 1 ? 1 : ($params['page'])));
        }

        list($sql, $sql_data) = $operation($data, $table_name, $primary_key_column, $params);
        unset($data[$primary_key_column]);

        if ($sql === false) {
            return $sql;
        }

        $stmt = $this->pdo->prepare($sql);
        foreach ($sql_data as $sql_param_name => $sql_param_data) {
            $stmt->bindParam(":{$sql_param_name}", $sql_param_data['v'], $sql_param_data['t']);
        }
        $stmt->execute();
        if (empty($class)) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $result = $stmt->fetchAll(PDO::FETCH_CLASS, $class);
        }

        return [$result, $params];
    }

    public function rawOperation(string $query)
    {
        $this->pdo->query($query);
    }

    public static function __callStatic($name, $arguments)
    {
        $db = self::getInstance();
        if (in_array($name, ['update', 'insert'])) {
            $closure = Closure::fromCallable([$db, "{$name}Stmt"]);
            call_user_func_array([$db, "setOperation"], array_merge([$closure], $arguments));
        } elseif ($name == 'get') {
            $closure = Closure::fromCallable([$db, 'getStmt']);
            $result = call_user_func_array([$db, "getOperation"], array_merge([$closure], $arguments));
            return $result;
        }
    }
}
