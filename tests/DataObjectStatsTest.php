<?php

require_once(dirname(__FILE__) . "/../database/memory/MemoryDatabase.php");
require_once(dirname(__FILE__) . "/../modelling/models/DatabaseModel.php");
require_once(dirname(__FILE__) . "/../statistics/DataObjectStats.php");

$database = NULL;

Class DataObjectStatsTestModelBase extends DataObjectStats {
    
    protected static function getDatabase() {
        global $database;
        return $database;
    }
    
    protected static function periodStatsClassName() {
        return "DataObjectPeriodStatsTestModel";
    }
    
    protected static function initializeScheme() {
        $attrs = parent::initializeScheme();
        $attrs["parent_id"] = array(
            "type" => "id"
        );
        return $attrs;
    }

    protected function initializeAssocs() {
        parent::initializeAssocs();
        $this->assocs["parent"] = new ModelHasOneAssociation($this, "parent_id", static::periodStatsClassName(), array(
            "cached" => TRUE,
            "polymorphic" => TRUE,
            "delete_cascade" => TRUE
        ));
    }   

    protected static function initializeStatsScheme() {
        return array(
            "a" => array(
                "default" => 1,
            ),
            "b" => array(
                "default" => 2,
                "type" => "period"
            ),
            "c" => array(
                "default" => 3,
                "hierarchical" => TRUE
            ),
            "d" => array(
                "default" => 4,
                "type" => "period",
                "hierarchical" => TRUE
            ),
        );
    }

}

Class DataObjectStatsTestModelChild extends DataObjectStatsTestModelBase {
    
    protected static function initializeScheme() {
        $attrs = parent::initializeScheme();
        $attrs["parent_id"] = array(
            "type" => "id"
        );
        return $attrs;
    }

    protected function initializeAssocs() {
        parent::initializeAssocs();
        $this->assocs["parent"] = new ModelHasOneAssociation($this, "parent_id", "DataObjectStatsTestModelParent", array("cached" => TRUE));
    }   

    protected function parentStats() {
        return $this->parent();
    }

}

Class DataObjectStatsTestModelParent extends DataObjectStatsTestModelBase {
    
    protected static function initializeStatsScheme() {
        return array_merge(parent::initializeStatsScheme(), array(
            "e" => array(
                "default" => 5               
            ),
            "f" => array(
                "default" => 6,
                "type" => "period"                
            ),
        ));
    }
    
}

Class DataObjectPeriodStatsTestModel extends DataObjectPeriodStats {
    protected static function getDatabase() {
        global $database;
        return $database;
    }
}



class DataObjectStatsTest extends PHPUnit_Framework_TestCase {
    
    public function test() {
        global $database;
        $base_time = mktime(1, 1, 1, 1, 1, 2020);
        $next_time = mktime(1, 1, 1, 2, 1, 2020);
        $database = new MemoryDatabase();
        $stats_parent = new DataObjectStatsTestModelParent();
        $stats_parent->save();
        $stats_child = new DataObjectStatsTestModelChild();
        $stats_child->parent_id = $stats_parent->id();
        $stats_child->save();
        $this->assertEquals(DataObjectStatsTestModelParent::count(), 1);
        $this->assertEquals(DataObjectStatsTestModelChild::count(), 1);
        $this->assertEquals(DataObjectPeriodStatsTestModel::count(), 0);
        $stats_parent->writeStats(array(), array("date" => $base_time));
        $this->assertEquals(DataObjectPeriodStatsTestModel::count(), 2);
        $stats_child->writeStats(array(), array("date" => $base_time));
        $this->assertEquals(DataObjectPeriodStatsTestModel::count(), 4);
        $child_data = $stats_child->readStats(array("date" => $base_time, "period" => "month"));
        $this->assertEquals($child_data["a"], 1);
        $this->assertEquals($child_data["b"], 2);
        $this->assertEquals($child_data["c"], 3);
        $this->assertEquals($child_data["d"], 4);
        $parent_data = $stats_parent->readStats(array("date" => $base_time, "period" => "month"));
        $this->assertEquals($parent_data["a"], 1);
        $this->assertEquals($parent_data["b"], 2);
        $this->assertEquals($parent_data["c"], 3);
        $this->assertEquals($parent_data["d"], 4);
        $this->assertEquals($parent_data["e"], 5);
        $this->assertEquals($parent_data["f"], 6);
        $stats_child->writeStats(array(
            "a" => array('inc' => 1),
            "b" => array('inc' => 1),
            "c" => array('inc' => 1),
            "d" => array('inc' => 1),
        ), array("date" => $base_time));
        $stats_child->reload();
        $stats_parent->reload();
        $child_data = $stats_child->readStats(array("date" => $base_time, "period" => "month"));
        $this->assertEquals($child_data["a"], 2);
        $this->assertEquals($child_data["b"], 3);
        $this->assertEquals($child_data["c"], 4);
        $this->assertEquals($child_data["d"], 5);
        $parent_data = $stats_parent->readStats(array("date" => $base_time, "period" => "month"));
        $this->assertEquals($parent_data["a"], 1);
        $this->assertEquals($parent_data["b"], 2);
        $this->assertEquals($parent_data["c"], 4);
        $this->assertEquals($parent_data["d"], 5);
        $this->assertEquals($parent_data["e"], 5);
        $this->assertEquals($parent_data["f"], 6);
        $stats_parent->writeStats(array(
            "a" => array('inc' => 1),
            "b" => array('inc' => 1),
            "c" => array('inc' => 1),
            "d" => array('inc' => 1),
            "e" => array('inc' => 1),
            "f" => array('inc' => 1),
        ), array("date" => $base_time));
        $stats_child->reload();
        $stats_parent->reload();
        $child_data = $stats_child->readStats(array("date" => $base_time, "period" => "month"));
        $this->assertEquals($child_data["a"], 2);
        $this->assertEquals($child_data["b"], 3);
        $this->assertEquals($child_data["c"], 4);
        $this->assertEquals($child_data["d"], 5);
        $parent_data = $stats_parent->readStats(array("date" => $base_time, "period" => "month"));
        $this->assertEquals($parent_data["a"], 2);
        $this->assertEquals($parent_data["b"], 3);
        $this->assertEquals($parent_data["c"], 5);
        $this->assertEquals($parent_data["d"], 6);
        $this->assertEquals($parent_data["e"], 6);
        $this->assertEquals($parent_data["f"], 7);
        $child_data = $stats_child->readStats(array("date" => $next_time, "period" => "month"));
        $this->assertEquals($child_data["a"], 2);
        $this->assertEquals($child_data["b"], 2);
        $this->assertEquals($child_data["c"], 4);
        $this->assertEquals($child_data["d"], 4);
        $parent_data = $stats_parent->readStats(array("date" => $next_time, "period" => "month"));
        $this->assertEquals($parent_data["a"], 2);
        $this->assertEquals($parent_data["b"], 2);
        $this->assertEquals($parent_data["c"], 5);
        $this->assertEquals($parent_data["d"], 4);
        $this->assertEquals($parent_data["e"], 6);
        $this->assertEquals($parent_data["f"], 6);
        $child_data = $stats_child->readStats(array("date" => $base_time, "period" => "year"));
        $this->assertEquals($child_data["a"], 2);
        $this->assertEquals($child_data["b"], 3);
        $this->assertEquals($child_data["c"], 4);
        $this->assertEquals($child_data["d"], 5);
        $parent_data = $stats_parent->readStats(array("date" => $base_time, "period" => "year"));
        $this->assertEquals($parent_data["a"], 2);
        $this->assertEquals($parent_data["b"], 3);
        $this->assertEquals($parent_data["c"], 5);
        $this->assertEquals($parent_data["d"], 6);
        $this->assertEquals($parent_data["e"], 6);
        $this->assertEquals($parent_data["f"], 7);
        $stats_child->writeStats(array(
            "b" => array('inc' => 10),
            "d" => array('inc' => 10),
        ), array("date" => $next_time));
        $this->assertEquals(DataObjectPeriodStatsTestModel::count(), 6);
        $child_data = $stats_child->readStats(array("date" => $base_time, "period" => "year"));
        $this->assertEquals($child_data["b"], 13);
        $this->assertEquals($child_data["d"], 15);
        $parent_data = $stats_parent->readStats(array("date" => $base_time, "period" => "year"));
        $this->assertEquals($parent_data["b"], 3);
        $this->assertEquals($parent_data["d"], 16);
        $child_data = $stats_child->readStats(array("date" => $base_time, "period" => "month"));
        $this->assertEquals($child_data["b"], 3);
        $this->assertEquals($child_data["d"], 5);
        $parent_data = $stats_parent->readStats(array("date" => $base_time, "period" => "month"));
        $this->assertEquals($parent_data["b"], 3);
        $this->assertEquals($parent_data["d"], 6);
        $child_data = $stats_child->readStats(array("date" => $next_time, "period" => "month"));
        $this->assertEquals($child_data["b"], 12);
        $this->assertEquals($child_data["d"], 14);
        $parent_data = $stats_parent->readStats(array("date" => $next_time, "period" => "month"));
        $this->assertEquals($parent_data["b"], 2);
        $this->assertEquals($parent_data["d"], 14);
    }

}
