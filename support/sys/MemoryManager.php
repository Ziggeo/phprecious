<?php

class MemoryManager {

    /*
     * Conditionally GC when program exceeds a threshold starting off a baseline.
     *
     * Returns the new baseline after garbage collection.
     *
     */
    public static function gc_collect_baseline_threshold($threshold, $baseline = -1) {
        if ($baseline < 0)
            return memory_get_usage();
        if (memory_get_usage() >= $baseline + $threshold) {
            gc_collect_cycles();
            $baseline = memory_get_usage();
        }
        return $baseline;
    }

}
