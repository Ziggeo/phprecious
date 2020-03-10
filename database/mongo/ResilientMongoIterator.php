<?php


Class ResilientMongoIterator implements Iterator {

    private $collection;
    private $query;
    private $originalSkip;
    private $originalLimit;
    private $originalSort;
    private $elementsObtained;
    private $iterator;
    private $first;

    function __construct($collection, $query, $skip, $limit, $sort) {
        $this->collection = $collection;
        $this->query = $query;
        $this->originalSkip = $skip;
        $this->originalLimit = $limit;
        $this->originalSort = $sort;
        $this->reset();
    }

    private function reset() {
        $this->first = TRUE;
        $this->elementsObtained = 0;
        $options = array();
        if ($this->originalSkip !== NULL)
            $options["skip"] = $this->originalSkip;
        if ($this->originalLimit !== NULL)
            $options["limit"] = $this->originalLimit;
        if ($this->originalSort !== NULL)
            $options["sort"] = $this->originalSort;
        $this->iterator = new IteratorIterator($this->collection->find($this->query, $options));
    }

    public function valid() {
        return $this->iterator->valid();
    }

    public function rewind() {
        if (!$this->first || $this->iterator === NULL)
            // Let's start over from scratch
            $this->reset();
        $this->iterator->rewind();
    }

    public function key() {
        // You should only call this if you already have an element in the iterator, so we just patch it through.
        return $this->iterator->key();
    }

    public function current() {
        // You should only call this if you already have an element in the iterator, so we just patch it through.
        return $this->iterator->current();
    }

    private function queryAfterElement($baseQuery, $lastElement, $sort) {
        // We assume that sort is NOT empty and contains a uniqueness index.
        $ors = [];
        $equals = [];
        $keys = array_keys($sort);
        while (count($keys) > 0) {
            $key = array_shift($keys);
            $direction = $sort[$key];
            $directionalConstraint = array();
            $directionalConstraint[$key] = array();
            $directionalConstraint[$key][$direction > 0 ? '$gt' : '$lt'] = $lastElement[$key];
            $ors[] = array_merge($equals, $directionalConstraint);
            $equals[$key] = $lastElement[$key];
        }
        if ($baseQuery === NULL)
            $baseQuery = array();
        $query = $baseQuery;
        if (isset($query['$or'])) {
            if (!isset($query['$and']))
                $query['$and'] = array();
            $query['$and'][] = array('$or' => $query['$or']);
            unset($query['$or']);
            $query['$and'][] = array('$or' => $ors);
        } else
            $query['$or'] = $ors;
        //var_dump($query);
        return $query;
    }

    public function next() {
        // This should only be called if valid, so we can assume there is a current element.
        $this->elementsObtained++;
        $lastElement = $this->current();
        try {
            // DEBUG: Comment In
            if ($this->elementsObtained > 5 && $this->first)
                throw new MongoDB\Driver\Exception\RuntimeException("cursor id exception simulation");
            // DEBUG: Comment In
            // We just try to get the next element
            $this->iterator->next();
        } catch (MongoDB\Driver\Exception\RuntimeException $e) {
            if (strpos($e->getMessage(), "cursor id") !== FALSE) {
                // cursor id not found exception, let's mitigate
                $options = array();
                // no skip; we already skipped everything in the first iterator
                // limit if we have a limit; we adjust it with the elements obtained
                if ($this->originalLimit !== NULL)
                    $options["limit"] = $this->originalLimit - $this->elementsObtained;
                // sort we keep
                if ($this->originalSort !== NULL)
                    $options["sort"] = $this->originalSort;
                // Adjust the query so we start after the last obtained element
                $query = $this->queryAfterElement($this->query, $lastElement, $this->originalSort);
                // DEBUG: Comment In
                //var_dump($query);
                // DEBUG: Comment In
                $this->iterator = new IteratorIterator($this->collection->find($query, $options));
                $this->iterator->rewind();
                // We don't call next on the new iterator because it already "has" the first element
                // not first anymore
                $this->first = FALSE;
            } else {
                // something else; let's throw this right back at the caller
                throw $e;
            }
        }
    }

}
