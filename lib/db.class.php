<?php

/**
 * MySQL Database Class extends wpdb
 *
 * @since 1.0.0
 */
class db_mysql extends wpdb {
    function db_mysql($dbuser, $dbpassword, $dbname, $dbhost) {
        return $this->__construct($dbuser, $dbpassword, $dbname, $dbhost);
    }

    function  __construct( $dbuser, $dbpassword, $dbname, $dbhost ) {
        register_shutdown_function(array(&$this, "__destruct"));

        if ( WP_DEBUG )
            $this->show_errors();

        if ( defined('DB_CHARSET') )
            $this->charset = DB_CHARSET;

        if ( defined('DB_COLLATE') )
            $this->collate = DB_COLLATE;

        $this->dbuser = $dbuser;

        $this->dbh = @mysql_connect($dbhost, $dbuser, $dbpassword, true);
        if (!$this->dbh) {
            return;
        }

        $this->ready = true;

        if ( $this->has_cap( 'collation' ) && !empty($this->charset) ) {
            if ( function_exists('mysql_set_charset') ) {
                mysql_set_charset($this->charset, $this->dbh);
                $this->real_escape = true;
            } else {
                $collation_query = "SET NAMES '{$this->charset}'";
                if ( !empty($this->collate) )
                    $collation_query .= " COLLATE '{$this->collate}'";
                $this->query($collation_query);
            }
        }

        $this->select($dbname);
    }

}


/**
 * Access database
 *
 * @since 1.0.0
 * @version 1.1.0
 */
class db_access {

    /**
     * Amount of queries made
     *
     * @since 1.1.0
     * @access private
     * @var int
     */
    var $num_queries = 0;

    /**
     * The last error during query.
     *
     * @since 1.1.0
     * @var string
     */
    var $last_error = false;

    /**
     * Saved result of the last query made
     *
     * @since 1.1.0
     *
     * @access private
     * @var array
     */
    var $last_query;

    /**
     * Saved info on the table column
     *
     * @since 1.1.0
     *
     * @access private
     * @var array
     */
    var $col_info;

    /**
     * Whether the database queries are ready to start executing.
     *
     * @since 1.1.0
     *
     * @access private
     * @var bool
     */
    var $ready = false;

    /**
     *
     * @since 1.0.0
     *
     * @param strng $dbhost 数据库路径
     * @param string $dbuser 数据库用户名
     * @param mixed $dbpass 数据库用户密码
     * @return Access数据库连接
     */
    function db_access($dbhost, $dbuser = '', $dbpass = '') {
        return $this->__construct($dbhost, $dbuser, $dbpass);
    }

    /**
     *
     * @since 1.0.0
     * @version 1.1.0
     *
     * @param strng $dbhost 数据库路径
     * @param string $dbuser 数据库用户名
     * @param mixed $dbpass 数据库用户密码
     * @return WP_Error|Access数据库连接
     */
    function __construct($dbhost, $dbuser = '', $dbpass = '') {
        $this->dbuser = $dbuser;

        $this->dbh = new com('adodb.connection', null, 65001);

        $sql = "DRIVER={Microsoft Access Driver (*.mdb)};dbq=$dbhost;uid=$dbuser;pwd=$dbpass";
        $this->dbh->open($sql);

        if(!$this->dbh->state) {
            $this->dbh->open("Provider=Microsoft.Jet.OLEDB.4.0; Data Source=$dbhost");
            if($dbc->state == 0) {
                $this->ready = false;
                return new WP_Error('connection_access_failed', 'Unable to connect access database.');
            }
        }

        $this->ready = true;

        return $this->dbh;
    }

    /**
     * Kill cached query results.
     *
     * @since 1.0.0
     */
    function flush() {
        $this->last_result = array();
        $this->col_info = null;
        $this->last_query = null;
    }

    /**
     * Perform a MySQL database query, using current database connection.
     *
     * More information can be found on the codex page.
     *
     * @since 1.0.0
     * @version 1.1.0
     *
     * @param string $query
     * @return int|false Number of rows affected/selected or false on error
     */
    function query($query) {
        if ( ! $this->ready )
            return false;

        // filter the query, if filters are available
        // NOTE: some queries are made before the plugins have been loaded, and thus cannot be filtered with this method
        if ( function_exists('apply_filters') )
            $query = apply_filters('query', $query);

        // initialise return
        $return_val = 0;
        $this->flush();

        // Log how the function was called
        $this->func_call = "\$db->query(\"$query\")";

        // Keep track of the last query for debug..
        $this->last_query = $query;

        // Perform the query via std mysql_query function..
        if ( defined('SAVEQUERIES') && SAVEQUERIES )
            $this->timer_start();

        $this->result = $this->dbh->execute($query);
        ++$this->num_queries;

        if( !$this->result )

            if ( defined('SAVEQUERIES') && SAVEQUERIES )
                $this->queries[] = array( $query, $this->timer_stop(), $this->get_caller() );

        $num_fields = $this->get_num_fields($this->result);
        for( $i = 0; $i < $num_fields; $i++ ) {
            $this->col_info[$i] = $this->result->Fields[$i];
        }
        $num_rows = 0;
        while ( $row = $this->get_fetch_object($this->result) ) {
            $this->last_result[$num_rows] = $row;
            $num_rows++;
        }

        // Log number of rows the query returned
        $this->num_rows = $num_rows;

        // Return number of rows selected
        $return_val = $this->num_rows;

        return $return_val;

    }

    /**
     * Starts the timer, for debugging purposes.
     *
     * @since 1.1.0
     * @see wpdb wp-includes/wp-db.php
     *
     * @return true
     */
    function timer_start() {
        $mtime = microtime();
        $mtime = explode(' ', $mtime);
        $this->time_start = $mtime[1] + $mtime[0];
        return true;
    }

    /**
     * Stops the debugging timer.
     *
     * @since 1.1.0
     * @see wpdb wp-includes/wp-db.php
     *
     * @return int Total time spent on the query, in milliseconds
     */
    function timer_stop() {
        $mtime = microtime();
        $mtime = explode(' ', $mtime);
        $time_end = $mtime[1] + $mtime[0];
        $time_total = $time_end - $this->time_start;
        return $time_total;
    }

    /**
     * Retrieve the name of the function that called wpdb.
     *
     * Requires PHP 4.3 and searches up the list of functions until it reaches
     * the one that would most logically had called this method.
     *
     * @since 1.1.0
     * @see wpdb wp-includes/wp-db.php
     *
     * @return string The name of the calling function
     */
    function get_caller() {
        // requires PHP 4.3+
        if ( !is_callable('debug_backtrace') )
            return '';

        $bt = debug_backtrace();
        $caller = array();

        $bt = array_reverse( $bt );
        foreach ( (array) $bt as $call ) {
            if ( @$call['class'] == __CLASS__ )
                continue;
            $function = $call['function'];
            if ( isset( $call['class'] ) )
                $function = $call['class'] . "->$function";
            $caller[] = $function;
        }
        $caller = join( ', ', $caller );

        return $caller;
    }

    /**
     * Get number of fields in result
     *
     * @since 1.1.0
     *
     * @param <type> $result
     * @return int
     */
    function get_num_fields($result) {
        $num_fields = $result->Fields->Count;
        return $num_fields;
    }

    /**
     * Fetch a result row as an object
     *
     * @since 1.1.0
     *
     * @param <type> $result
     * @return object
     */
    function get_fetch_object($result) {

        $object = null;
        $num_fields = count($this->col_info);

        while( !$result->EOF ) {
            for( $i = 0 ; $i < $num_fields; $i++ ) {
                $name = $result[$i]->name;
                $value = $result[$i]->value;
                $object->$name = $value;
                //echo $name . ' = ' . $value.'<br>';
            }
            $this->result->movenext();
            return $object;
        }
    }

    /**
     * Retrieve one variable from the database.
     *
     * Executes a SQL query and returns the value from the SQL result.
     * If the SQL result contains more than one column and/or more than one row, this function returns the value in the column and row specified.
     * If $query is null, this function returns the value in the specified column and row from the previous SQL result.
     *
     * @since 1.1.0
     * @see get_var(), wp-includes/wp-db.php
     *
     * @param string|null $query SQL query.  If null, use the result from the previous query.
     * @param int $x (optional) Column of value to return.  Indexed from 0.
     * @param int $y (optional) Row of value to return.  Indexed from 0.
     * @return string Database query result
     */
    function get_var($query=null, $x = 0, $y = 0) {
        $this->func_call = "\$db->get_var(\"$query\",$x,$y)";
        if ( $query )
            $this->query($query);

        // Extract var out of cached results based x,y vals
        if ( !empty( $this->last_result[$y] ) ) {
            $values = array_values(get_object_vars($this->last_result[$y]));
        }

        // If there is a value return it else return null
        return (isset($values[$x]) && $values[$x]!=='') ? $values[$x] : null;
    }

    /**
     * Retrieve one row from the database.
     *
     * Executes a SQL query and returns the row from the SQL result.
     *
     * @since 1.1.0
     * @see get_row(), wp-includes/wp-db.php
     *
     * @param string|null $query SQL query.
     * @param string $output (optional) one of ARRAY_A | ARRAY_N | OBJECT constants.  Return an associative array (column => value, ...), a numerically indexed array (0 => value, ...) or an object ( ->column = value ), respectively.
     * @param int $y (optional) Row to return.  Indexed from 0.
     * @return mixed Database query result in format specifed by $output
     */
    function get_row($query = null, $output = OBJECT, $y = 0) {
        $this->func_call = "\$db->get_row(\"$query\",$output,$y)";
        if ( $query )
            $this->query($query);
        else
            return null;

        if ( !isset($this->last_result[$y]) )
            return null;

        if ( $output == OBJECT ) {
            return $this->last_result[$y] ? $this->last_result[$y] : null;
        } elseif ( $output == ARRAY_A ) {
            return $this->last_result[$y] ? get_object_vars($this->last_result[$y]) : null;
        } elseif ( $output == ARRAY_N ) {
            return $this->last_result[$y] ? array_values(get_object_vars($this->last_result[$y])) : null;
        } else {
            $this->print_error(/*WP_I18N_DB_GETROW_ERROR*/' $db->get_row(string query, output type, int offset) -- 输出类型必须是以下类型中的一个：OBJECT, ARRAY_A, ARRAY_N'/*/WP_I18N_DB_GETROW_ERROR*/);
        }
    }

    /**
     * Retrieve one column from the database.
     *
     * Executes a SQL query and returns the column from the SQL result.
     * If the SQL result contains more than one column, this function returns the column specified.
     * If $query is null, this function returns the specified column from the previous SQL result.
     *
     * @since 1.1.0
     * @see get_col(), wp-includes/wp-db.php
     *
     * @param string|null $query SQL query.  If null, use the result from the previous query.
     * @param int $x Column to return.  Indexed from 0.
     * @return array Database query result.  Array indexed from 0 by SQL result row number.
     */
    function get_col($query = null , $x = 0) {
        if ( $query )
            $this->query($query);

        $new_array = array();
        // Extract the column values
        for ( $i=0; $i < count($this->last_result); $i++ ) {
            $new_array[$i] = $this->get_var(null, $x, $i);
        }
        return $new_array;
    }

    /**
     *
     * @since 1.0.0
     * @version 1.1.0
     *
     * @param string $query 待查询语句
     * @param mixed $output 输出类型：OBJECT、OBJECT_K、ARRAY_A、ARRAY_N
     * @return mixed 见 $output
     */
    function get_results($query = null, $output = OBJECT) {
        $this->func_call = "\$db->get_results(\"$query\", $output)";

        if ( $query ) {
            $this->query($query);
        } else {
            return null;
        }

        if ( $output == OBJECT ) {
            // Return an integer-keyed array of row objects
            return $this->last_result;
        } elseif ( $output == OBJECT_K ) {
            // Return an array of row objects with keys from column 1
            // (Duplicates are discarded)
            foreach ( $this->last_result as $row ) {
                $key = array_shift( get_object_vars( $row ) );
                if ( !isset( $new_array[ $key ] ) )
                    $new_array[ $key ] = $row;
            }
            return $new_array;
        } elseif ( $output == ARRAY_A || $output == ARRAY_N ) {
            // Return an integer-keyed array of...
            if ( $this->last_result ) {
                $i = 0;
                foreach( (array) $this->last_result as $row ) {
                    if ( $output == ARRAY_N ) {
                        // ...integer-keyed row arrays
                        $new_array[$i] = array_values( get_object_vars( $row ) );
                        echo $new_array[$i];
                    } else {
                        // ...column name-keyed row arrays
                        $new_array[$i] = get_object_vars( $row );
                    }
                    ++$i;
                }
                return $new_array;
            }
        }

    }

}

?>