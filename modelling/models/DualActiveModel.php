<?php

require_once(dirname(__FILE__) . "/../../support/strings/ParseType.php");
require_once(dirname(__FILE__) . "/../../support/strings/StringUtils.php");
require_once(dirname(__FILE__) . "/../../support/data/Iterators.php");
require_once(dirname(__FILE__) . "/../../support/time/TimeSupport.php");
require_once(dirname(__FILE__) . "/Model.php");

abstract class DualActiveModel extends Model {
    
    private static $table = array();


    public static function encodeData($attrs) {
        return static::model($attrs)::encodeData($attrs);
    }

	public abstract static function model($attrs);

    public static function decodeData($attrs) {
        return static::model($attrs)::decodeData($attrs);
    }

    public static function keyForQueryModel($model) {
    	return NULL;
	}

    protected static function materializeClass($attrs) {
		return static::model($attrs)::materializeClass($attrs);
    }

	public static function idKey() {
		$model = static::model(array())[0];
		return $model->primaryKey();
	}

    protected static function findRowById($id) {
		$model = static::model(array("id" => $id))[0];
        return $model::findRowById($id);
    }

    protected static function findRowBy($query) {
		$model = static::model($query)[0];
        return $model::findRowBy($query);
    }

    protected static function allRows($options = NULL) {
		$models = static::model(array());
		$results = array();
		foreach ($models as $model) {
			$results = array_merge($results, $model::allRows($options));
		}
		return $results;
    }

    public static function allRowsBy($query, $options = NULL) {
		$models = static::model($query);
		$results = array();
		foreach ($models as $model) {
			if (@static::keyForQueryModel($model) && isset($query[static::keyForQueryModel($model)]))
				$parsed_query = $query[static::keyForQueryModel($model)];
			else
				$parsed_query = $query;
			$results = array_merge($results, $model::allRowsBy($parsed_query, $options));
		}
		return $results;
    }

	public static function count($query = array(), $ignore_remove_field = FALSE, $extra_options = array()) {
    	$models = static::model($query);
    	$results = array();
    	foreach ($models as $model) {
    		if (@static::keyForQueryModel($model) && isset($query[static::keyForQueryModel($model)]))
    			$parsed_query = $query[static::keyForQueryModel($model)];
			else
				$parsed_query = $query;
    		$results[] = $model::count($parsed_query, $ignore_remove_field, $extra_options);
		}
		return array_sum($results);
	}


	public static function allBy($query, $sort = NULL, $limit = NULL, $skip = NULL, $iterator = FALSE, $ignore_remove_field = FALSE, $extra_options = array()) {
    	//TODO merge skip and limit and sort for 
		$options = array();
		if (@$sort)
			$options["sort"] = $sort;
		if (@$limit)
			$options["limit"] = $limit;
		if (@$skip)
			$options["skip"] = $skip;
		$options = array_merge($options, $extra_options);
		$rf = static::classOptionsOf("remove_field");
		if ($rf != NULL && !isset($query[$rf]) && !$ignore_remove_field)
			$query[$rf] = FALSE;
		$models = static::model($query);
		$result = array();
		foreach ($models as $model) {
			if ($options["limit"] === 0)
				break;
			if (@static::keyForQueryModel($model) && isset($query[static::keyForQueryModel($model)]) && !@$query[static::keyForQueryModel($model)]["skip_query"])
				$parsed_query = $query[static::keyForQueryModel($model)];
			else
				$parsed_query = $query;
			$query_result = iterator_to_array($model::allRowsBy($parsed_query, $options));
			$options["limit"] -= count($query_result);
			$result = array_merge($result, $query_result);
		}
		if (is_array($result)) {
			$result = new ArrayObject($result);
			$result = $result->getIterator();
		}
		$cls = get_called_class();
		$iter = new MappedIterator($result, function($row) use ($cls) {
			$model = $cls::model($row)[0];
			return $model::materializeObject($row);
		});
		return $iterator ? $iter : iterator_to_array($iter, FALSE);
	}

}