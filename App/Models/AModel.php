<?php

namespace App\Models;

use ReflectionClass;
use ReflectionProperty;
use App\DataProviders\DatabaseOperator;

abstract class AModel
{
    protected $primary_key = "";
    protected $table_name = "";
    protected $aliases = [];

    public static function getModel(bool $short = true)
    {
        $class = new \ReflectionClass(get_called_class());
        return $short ? $class->getShortName() : $class->getName();
    }

    public function __construct(array $data = [])
    {
        if (empty($this->table_name)) {
            $this->table_name = strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', static::getModel()));
        }

        if (empty($this->primary_key)) {
            $this->primary_key = "{$this->table_name}_id";
        }

        if (!empty($data) && !empty($this->aliases)) {
            $fillables = $this->getFillables();

            $this->aliases = array_filter($this->aliases, function ($k) use ($fillables) {
                return in_array($k, $fillables);
            }, ARRAY_FILTER_USE_KEY);

            if (!empty($this->aliases)) {
                foreach ($data as $key => $value) {
                    $property = array_search($key, $this->aliases);
                    if ($property !== false) {
                        $this->$property = $value;
                    }
                }
            }
        }
    }

    protected function getFillables()
    {
        $reflect = new ReflectionClass($this);
        $props   = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

        $model_class = static::getModel();

        $fields = [];
        foreach ($props as $prop) {
            if ($prop->getDeclaringClass()->getShortName() == $model_class) {
                $fields[] = $prop->getName();
            }
        }

        return $fields;
    }

    public function update($data = [])
    {
        if (empty($data)) {
            $data = $this->getFillables();

            $query_data = array_reduce($data, function ($res, $val) {
                $res[$val] = $this->$val;
                return $res;
            }, []);
        }

        DatabaseOperator::update($this->table_name, $query_data, ['primary_key' => $this->primary_key]);
    }

    public function insert($data = [])
    {
        if (empty($data)) {
            $data = $this->getFillables();

            $query_data = array_reduce($data, function ($res, $val) {
                $res[$val] = $this->$val;
                return $res;
            }, []);
        }

        DatabaseOperator::insert($this->table_name, $query_data, ['primary_key' => $this->primary_key]);
    }

    public function get($data = [], $params = [])
    {
        if (empty($data)) {
            $data = $this->getFillables();
        } elseif ($data == '*') {
            $data = [$data];
        }

        $model = !empty($params["assoc"]) ? null : $this->getModel(false);

        return DatabaseOperator::get($this->table_name, $data, array_merge(['primary_key' => $this->primary_key], $params), $model);
    }
}
