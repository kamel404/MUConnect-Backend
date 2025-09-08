<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class DatabaseHelper
{
    /**
     * Get the appropriate LIKE operator for case-insensitive searches
     * based on the database driver.
     * 
     * @return string
     */
    public static function getCaseInsensitiveLikeOperator(): string
    {
        $driver = DB::connection()->getDriverName();
        
        return match ($driver) {
            'pgsql' => 'ilike',
            'mysql', 'sqlite', 'sqlsrv' => 'like',
            default => 'like'
        };
    }
    
    /**
     * Perform a case-insensitive LIKE search on a query builder
     * 
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query
     * @param string $column
     * @param string $value
     * @param string $boolean
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder
     */
    public static function whereLikeInsensitive($query, string $column, string $value, string $boolean = 'and')
    {
        $operator = self::getCaseInsensitiveLikeOperator();
        $method = $boolean === 'and' ? 'where' : 'orWhere';
        
        return $query->{$method}($column, $operator, $value);
    }
    
    /**
     * Check if the current database supports unsigned integers
     * 
     * @return bool
     */
    public static function supportsUnsigned(): bool
    {
        $driver = DB::connection()->getDriverName();
        
        return $driver === 'mysql';
    }
    
    /**
     * Get the appropriate text column type for the current database
     * 
     * @param string $size 'small', 'medium', 'long'
     * @return string
     */
    public static function getTextColumnType(string $size = 'normal'): string
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'pgsql') {
            // PostgreSQL uses TEXT for all sizes
            return 'text';
        }
        
        return match ($size) {
            'small' => 'text',
            'medium' => 'mediumText',
            'long' => 'longText',
            default => 'text'
        };
    }
}
