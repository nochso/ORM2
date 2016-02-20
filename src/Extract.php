<?php

namespace nochso\ORM;

class Extract
{
    /**
     * Calls get_object_vars for you, ensuring only public properties are returned.
     *
     * @param $object
     * @return array
     */
    public static function getObjectVars($object)
    {
        return get_object_vars($object);
    }
}
