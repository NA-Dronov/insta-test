<?php

namespace App\Core;

class Config
{
    use TSingleton;

    private $db_params = null;

    public function __construct()
    {
        $xml_config = simplexml_load_file(DATABASE_CONFIG);
        $this->db_params = new \stdClass();

        foreach ($xml_config->params as $param) {
            foreach ($param as $p) {
                foreach ($p->attributes() as $k => $v) {
                    if ($k != 'name') {
                        continue;
                    }

                    $this->db_params->$v = (string) $p;
                }
            }
        }

        foreach ($xml_config->queries as $query) {
            foreach ($query as $q) {
                $this->db_params->init_quieries[] = (string) $q;
            }
        }
    }

    public function get(string $key)
    {
        $path = explode('.', $key, 2);
        if (count($path) == 1 && !in_array($path[0], ['db_params'])) {
            return $this->$key;
        } elseif (count($path) > 1 && $path[0] == 'db') {
            $prop = $path[1];
            return $this->db_params->$prop ?? null;
        } else {
            return null;
        }
    }
}
