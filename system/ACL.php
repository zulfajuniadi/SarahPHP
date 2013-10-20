<?php

class ACLHandler
{
    public function handler()
    {

    }
}

class ACL
{
    static $routeFilters;

    static $dataFilters;

    static function RouteFilter() {
        return new ACLHandler();
    }

    static function ModelFilter() {
        return new ACLHandler();
    }

}