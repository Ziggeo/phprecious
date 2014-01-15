<?php

require_once(dirname(__FILE__) . "/../DatabaseTable.php");

class MemoryDatabaseTable extends DatabaseTable {
    
    private $rows = array();
    private $lastid = 1;
    
    public function primaryKey() {
        return "_id";
    }

    public function insert(&$row, $options = array()) {
        $id = $this->lastid++;
        $row[$this->primaryKey()] = $id; 
        $this->rows[$id] = $row;
        return TRUE;
    }
    
    public function removeOne($query, $options = array()) {
        $obj = $this->findOne($query);
        if (isset($obj))
            unset($this->rows[$obj[$this->primaryKey()]]);
        return TRUE;
    }       
    
    public function remove($query, $options = array()) {
        foreach ($this->find($query) as $obj)
            unset($this->rows[$obj[$this->primaryKey()]]);
        return TRUE;
    }
    
    private function updateById($id, $update) {
        foreach ($update as $key=>$value)
            $this->rows[$id][$key] = $value;
    }
    
    public function updateOne($query, $update, $options = array()) {
        $obj = $this->findOne($query);
        if (isset($obj)) {
            $this->updateById($obj[$this->primaryKey()], $update);
            return TRUE;
        }
        return FALSE;
    }
    
    public function update($query, $update, $options = array()) {
        foreach ($this->find($query) as $obj)
            $this->updateById($obj[$this->primaryKey()], $update);
        return TRUE;
    }
    
    private function matchValue($data, $arg) {
        if (is_array($arg) && count($arg) == 1) {
            $keys = array_keys($arg);
            $keys = $keys[0];
            if (StringUtils::startsWith($keys, '$')) {
                $value = $arg[$keys];
                if ($keys == '$lt')
                    return $data < $value;
                if ($keys == '$gt')
                    return $data > $value;
            }
        }
        return $data == $arg;
    }
    
    public function find($values, $options = NULL) {
        if (isset($values[$this->primaryKey()])) {
            if (isset($this->rows[$values[$this->primaryKey()]])) {
                $row = $this->rows[$values[$this->primaryKey()]];
                foreach ($values as $key=>$value)
                    if ($row[$key] != $value)
                        return array();
                return array($row);
            }
            return array();
        }
        $result = array();
        foreach ($this->rows as $id=>$row) {
            $filter = TRUE;
            foreach ($values as $key=>$value)
                $filter = $filter && $this->matchValue($row[$key], $value);
            if ($filter)
                $result[] = $row;
        }
        if (@$options) {
            if (@$options["sort"])
                uasort($result, function ($x, $y) use ($options) {
                    foreach ($options["sort"] as $key => $orientation) {
                        if ($x[$key] > $y[$key])
                            return $orientation;
                        if ($x[$key] < $y[$key])
                            return -$orientation;
                    }
                    return 0;
                });
            if (isset($options["skip"])) 
                $result = array_slice($result, $options["skip"]);
            if (isset($options["limit"]))
                $result = array_slice($result, 0, $options["limit"]);
        }
        $result = new ArrayObject($result);
        $result = $result->getIterator();       
        return $result;
    }
        
}
