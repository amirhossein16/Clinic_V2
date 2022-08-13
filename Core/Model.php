<?php

namespace Core;

use Core\DB\MySqlDatabase;
use Medoo\Medoo;

abstract class Model
{
    private MySqlDatabase $conn;
    private DB\DatabaseInterface $table;

    function __construct()
    {
        $this->conn = MySqlDatabase::do ();
        $this->table = $this->conn->table($this->getTable());
    }

    abstract public function getTable();

    /**
     * Optional Select
     *
     * @param array $cols =['*']
     * @param array|null $where =null
     * @param array|null $order =null
     * @param array|null $join =null $table, [$value[1], $value[2]]
     * @param array|null $between =null
     * @param string|null $group =null
     *
     * @return array
     */
    public function select(array $cols = ['*'], array |null $where = null, array |null $order = null, array |null $join = null, array |null $between = null, string|null $group = null): array
    {
        $query = $this->conn->select($cols);


        if (!is_null($join)) {
            foreach ($join as $key => $value) {

                $query->join($value[0], [$value[1], $value[2]], $value[3] ?? null);
            }
        }

        if (!is_null($where)) {
            foreach ($where as $key => $value) {
                $query->where($value[0], $value[1], $value[2] ?? null, $value[3] ?? ($key != 0 ? "AND" : "OR"));
            }
        }
        if (!is_null($order)) {
            foreach ($order as $key => $value) {
                $query->sort($value[0], $value[1]);
            }
        }
        if (!is_null($between)) {
            $query->between($between['col'], $between['from'], $between['to']);
        }
        if (!is_null($group)) {
            $query->group($group);
        }

        return $query->fetchAll();
    }

    public function exists(string $col, string $value): bool
    {
        $result = $this->conn->select(['COUNT(*) as count'])->where($col, $value, '=')->fetch();
        $count = $result->count;
        return $count != 0 ? true : false;
    }

    /**
     * for fetch 1 row from db
     *
     * @param string $col
     * @param string $value
     * @return object|bool $record
     */
    public function get(string $col, string $value): object|bool
    {
        $row = $this->conn->select()->where($col, $value)->fetch();
        return $this->convertor($row);
    }

    public function insert(array $data): bool
    {
        return $this->conn->insert($data)->exec();
    }

    public function convertor(object $record): object
    {
        foreach ($record as $col => $value) {
            if (in_array($col, array_keys($this->convert))) {
                $value = json_decode($value);
            }
            $res[$col] = $value;
        }

        return (object)$res;
    }

    public function betweenDate(string $col, string $date1, string $date2, string $condition1, string $condition2)
    {
        return $this->conn->select()->between($col, $date1, $date2)->where($condition1, $condition2)->fetchAll();
    }

    public function existsAppointment(string $doctorID, string $patientID, string $time, string $date)
    {
        return $this->conn->select()->where('doctor_id', $doctorID)->where('patient_id', $patientID)->where('time', $time)->where('date', $date)->fetchAll();
    }

    public function findAppointments(array $cols, array |null $join, array $id, array |null $where = null, array |null $order = null, array |null $between = null)
    {
        $query = $this->conn->select($cols);


        if (!is_null($join)) {
            foreach ($join as $key => $value) {

                $query->join($value[0], [$value[1], $value[2]]);
            }
        }

        $query = $query->where($id[0], $id[1]);

        if (!is_null($where)) {
            foreach ($where as $key => $value) {
                $query->where($value[0], $value[1], $value[2], $key != 0 ? "OR" : "AND");
            }
        }
        if (!is_null($order)) {
            foreach ($order as $key => $value) {
                $query->sort($value[0], $value[1]);
            }
        }
        if (!is_null($between)) {
            $query->between($between['col'], $between['from'], $between['to']);
        }

        return $query->fetchAll();
    }

    public function updateRow(string $id, string $table, array $data): bool
    {
        return $this->conn->update($data)->where($table . '_id', $id)->exec();
    }

    public function delete(array $where): bool
    {
        $query = $this->conn->delete();

        foreach ($where as $key => $value) {
            $query->where($value[0], $value[1], $value[2] ?? null, $value[3] ?? ($key != 0 ? "AND" : "OR"));
        }

        return $query->exec();
    }

}
