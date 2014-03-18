<?php


require_once(dirname(__FILE__) . "/../modelling/associations/ModelHasManyAssociation.php");
require_once(dirname(__FILE__) . "/../modelling/associations/ModelHasOneAssociation.php");


Abstract Class DataObjectStats extends DatabaseModel {
	
	protected function time() {
		return time();
	}
    
    protected static function periodStatsClassName() { return "DataObjectPeriodStats"; }
    
    /*
     * Options:
     *   - type: default | period
     *   - default (can be a function)
     *   - value (can be a function)
     *   - dependencies
     *   - hierarchical: boolean
     *   - period_dependencies
     *
     */
    protected static function initializeStatsScheme() { return array(); }
    
    protected function parentStats() {
        return NULL;
    }
    
    protected static function periods() {
        return array("year", "month");
    }
    

    /* 
     * - hierarchy: Boolean = FALSE (returns a list)
     * - date: Date = time()
     * - period: NULL|"period" = NULL
     * 
     */
    public function readStats($options = array()) {
        $hierarchy = isset($options["hierarchy"]) ? $options["hierarchy"] : FALSE;
        $date = isset($options["date"]) ? $options["date"] : $this->time();
        $period = isset($options["period"]) ? $options["period"] : NULL;
        $options["date"] = $date;
        $result = $this->data;
        if ($period != NULL) {
            $period_stat = $this->obtainPeriodStats($date, $period);
            $result = array_merge($result, $period_stat->data);
        }
        if (!$hierarchy)
            return $result;
        $result = array($result);
        $parent = $this->parentStats();
        if ($parent != NULL)
            return array_merge($result, $parent->readStats($options));
        return $result;
    }
    
    /*
     * - date: Date = time()
     * update:
     *  key => array(op => value), op: inc, dec, set
     */ 
    public function writeStats($update, $options = array()) {
        $date = isset($options["date"]) ? $options["date"] : $this->time();
        $options["date"] = $date;
        foreach (static::periods() as $period) {
            $period_stat = $this->obtainPeriodStats($date, $period);
            $period_stat->save();
        }
        $local_update = array();
        $period_update = array();
        $hierarchical_update = array();
        $statsScheme = static::getStatsScheme();
        foreach ($update as $key=>$action) {
            $entry = $statsScheme[$key];
            if (isset($entry["hierarchical"]) && $entry["hierarchical"])
                $hierarchical_update[$key] = $action;
            if (isset($entry["type"]) && $entry["type"] == "period")
                $period_update[$key] = $action;
            else
                $local_update[$key] = $action;
        }
        $local_deps = array();
        $period_deps = array();
        $old_data = array_slice($this->data, 0);
        if (count($local_update) > 0) {
            $data = $this->data;
            $this->data = NULL;
            foreach ($local_update as $key=>$action) {
                self::performAction($statsScheme, $data, $key, $action);
                if (isset($statsScheme[$key]["dependencies"]))
                    foreach ($statsScheme[$key]["dependencies"] as $dep)
                        $local_deps[$dep] = TRUE;
                if (isset($statsScheme[$key]["period_dependencies"]))
                    foreach ($statsScheme[$key]["period_dependencies"] as $dep)
                        $period_deps[$dep] = TRUE;
            }
            while (count($local_deps) > 0) {
                $dep = array_keys(array_splice($local_deps, 0, 1));
                $key = $dep[0];
                $data[$key] = $statsScheme[$key]["value"]($data, $old_data);
                if (isset($statsScheme[$key]["dependencies"]))
                    foreach ($statsScheme[$key]["dependencies"] as $dep)
                        $local_deps[$dep] = TRUE;
                if (isset($statsScheme[$key]["period_dependencies"]))
                    foreach ($statsScheme[$key]["period_dependencies"] as $dep)
                        $period_deps[$dep] = TRUE;
            }
            $this->data = $data;
            $this->save();
        }
        if (count($period_update) > 0 || count($period_deps) > 0) {
            foreach (static::periods() as $period) {
                $period_stat = $this->obtainPeriodStats($date, $period);
                $old_period_data = array_slice($period_stat->data, 0);
                $period_data = $period_stat->data;
                $period_stat->data = array();
                foreach ($period_update as $key=>$action) {
                    self::performAction($statsScheme, $period_data, $key, $action);
                    if (isset($statsScheme[$key]["period_dependencies"]))
                        foreach ($statsScheme[$key]["period_dependencies"] as $dep)
                            $period_deps[$dep] = TRUE;
                }
				$old_period_deps = array_slice($period_deps, 0);
                while (count($period_deps) > 0) {
                    $dep = array_keys(array_splice($period_deps, 0, 1));
                    $key = $dep[0];
                    $period_data[$key] = $statsScheme[$key]["value"]($period_data, $data, $old_period_data, $old_data);
                    if (isset($statsScheme[$key]["period_dependencies"]))
                        foreach ($statsScheme[$key]["period_dependencies"] as $dep)
                            $period_deps[$dep] = TRUE;
                }
				$period_deps = $old_period_deps;
                $period_stat->data = $period_data;
                $period_stat->save();
            }
        }
        if (count($hierarchical_update) > 0) {
            $parent = $this->parentStats();
            if ($parent != NULL)
                $parent->writeStats($hierarchical_update, $options);
        }        
    }

    private static function performAction($scheme, &$data, $key, $action) {
        $entry = $scheme[$key];
        $act = NULL;
        $val = NULL;
        foreach ($action as $a=>$v) {
            $act = $a;
            $val = $v;
        }
        if ($act == "inc")
            $data[$key] = $data[$key] + $val;
        elseif ($act == "dec")
            $data[$key] = $data[$key] - $val;
        elseif ($act == "set")
            $data[$key] = $val;
    }
    
    private function obtainPeriodStats($date, $period) {
        // Try to lookup specific period. If it exists, return it.
        $base_query = array(
            "stats_id" => $this->id(),
            "stats_id_type" => get_called_class(),
            "period" => $period
        );
        $query = array_merge($base_query, self::datePeriodRange($period, $date));
        $cls = static::periodStatsClassName();
        $row = $cls::findBy($query);
        if ($row != NULL)
            return $row;
        // If it doesn't, check whether there is a later entry.
        $later_query = array_merge($base_query, array("start_date" => array('$gt' => $query["start_date"])));
        $rows = $cls::allBy($later_query, array("start_date" => -1), 1, 0);
        $stat = new $cls($query);
        $scheme = static::getStatsScheme("period");
        if (count($rows) == 1) {
            // If so, use initial data.
            $row = $rows[0];
            $stat->initial = $row->initial;
        } else {
            // If it doesn't, create a new entry with new initial data.
            $stat = new $cls($query);
            $data = array();
            foreach ($scheme as $key=>$entry)
                if (!is_callable($entry["default"]))
                    $data[$key] = $entry["default"];
            foreach ($scheme as $key=>$entry)
                if (is_callable($entry["default"]))
                    $data[$key] = $entry["default"]($data, $this->data);
            $stat->initial = $data;
        }
        $stat->data = array_slice($stat->initial, 0);
        return $stat;
    }

    private static $statsScheme = array();
    
    public static function getStatsScheme($type = NULL) {
        if (!isset(self::$statsScheme[get_called_class()]))
            self::$statsScheme[get_called_class()] = static::initializeStatsScheme();
        $result = self::$statsScheme[get_called_class()];
        if ($type != NULL)
            $result = array_filter($result, function ($entry) use ($type) {
                return (!isset($entry["type"]) && $type == "default") || (isset($entry["type"]) && $type == $entry["type"]);
            });
        return $result;
    }

    protected static function initializeScheme() {
        $attrs = parent::initializeScheme();
        $attrs["data"] = array(
            "type" => "object"
        );
        return $attrs;
    }
    
    protected function beforeCreate() {
        parent::beforeCreate();
        $scheme = static::getStatsScheme("default");
        $data = array();
        foreach ($scheme as $key=>$entry)
            if (!is_callable($entry["default"]))
                $data[$key] = $entry["default"];
        foreach ($scheme as $key=>$entry)
            if (is_callable($entry["default"]))
                $data[$key] = $entry["default"]($data);
        $this->data = $data;
    }
    
    protected function initializeAssocs() {
        parent::initializeAssocs();
        $this->assocs["period_stats"] = new ModelHasManyAssociation($this, "stats_id", static::periodStatsClassName(), array(
            "cached" => TRUE,
            "polymorphic" => TRUE,
            "delete_cascade" => TRUE
        ));
    }   
    
    private static $periods = array(
        "year" => array(
            "select" => array("year"),
            "increment" => array("year" => 1)
        ),
        "month" => array(
            "select" => array("year", "mon"),
            "increment" => array("mon" => 1)
        ),
        "day" => array(
            "select" => array("year", "mon", "mday"),
            "increment" => array("mday" => 1)           
        )
    );
    
    public static function datePeriodRange($period, $date) {
        $period = self::$periods[$period];
        $date = getdate($date);
        $new_date = array();
        $new_date_inc = array();
        foreach ($period["select"] as $sel) {
            $new_date[$sel] = $date[$sel];
            $new_date_inc[$sel] = $date[$sel];
            if (isset($period["increment"][$sel]))
                $new_date_inc[$sel] += $period["increment"][$sel];
        }
        return array(
            "start_date" => mktime(0,0,0,
                isset($new_date["mon"]) ? $new_date["mon"] : 1,
                isset($new_date["mday"]) ? $new_date["mday"] : 1,
                isset($new_date["year"]) ? $new_date["year"] : 0
            ),
            "end_date" => mktime(0,0,0,
                isset($new_date_inc["mon"]) ? $new_date_inc["mon"] : 1,
                isset($new_date_inc["mday"]) ? $new_date_inc["mday"] : 1,
                isset($new_date_inc["year"]) ? $new_date_inc["year"] : 0
            ),
        );
    }
    
    public function statsHierarchy() {
        $result = array();
        $current = $this;
        while ($current != NULL) {
            $result[] = $current;
            $current = $current->parentStats();
        }
        return $result;
    }
    

}


Abstract Class DataObjectPeriodStats extends DatabaseModel {
       
    protected static function initializeScheme() {
        $attrs = parent::initializeScheme();
        $attrs["stats_id"] = array(
            "type" => "id",
            "index" => TRUE
        );
        $attrs["stats_id_type"] = array(
            "type" => "string",
            "index" => TRUE
        );
        $attrs["start_date"] = array("type" => "date");
        $attrs["end_date"] = array("type" => "date");
        $attrs["period"] = array("type" => "string");
        $attrs["data"] = array(
            "type" => "object"
        );
        $attrs["initial"] = array(
            "type" => "object"
        );
        return $attrs;
    }
    
}
