<?php

trait SerializeType {
    /**
     * Creates a PHP value from a stored representation
     *
     * @param mixed $value to unserialize
     * @return mixed|null|string|void
     */
    public function toPHP($value) {
        if ($value === null) {
            return;
        }
        return unserialize($value);
    }

    /**
     * Generates a storable representation of a value
     *
     * @param mixed $value to serialize
     * @return null|string
     */
    public function toDatabase($value) {
        return serialize($value);
    }
}