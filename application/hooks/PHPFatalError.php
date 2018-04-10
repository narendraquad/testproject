<?php
    class PHPFatalError {
        public static $php_errors = array(
        E_ERROR              => 'Fatal Error',
        E_USER_ERROR         => 'User Error',
        E_PARSE              => 'Parse Error',
        E_WARNING            => 'Warning',
        E_USER_WARNING       => 'User Warning',
        E_STRICT             => 'Strict',
        E_NOTICE             => 'Notice',
        E_RECOVERABLE_ERROR  => 'Recoverable Error',
    );

    public function setHandler()
    {
        register_shutdown_function(array($this,'handleShutdown'));
        set_exception_handler(array($this,'exception_handler'));
    }
   
    
    
    /**
     * [handleShutdown description]
     * Handle PHP Errors
     * @return [type] [description]
     */
    public function handleShutdown() 
    {
        if(ENVIRONMENT == 'production'){
            if (($error = error_get_last())){
                $error['type'] = 'PHP';
                $data = base64_encode(json_encode($error));
                    if(isset($_SERVER['HTTPS'])){
                        $protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";
                    }
                    else{
                        $protocol = 'http';
                    }
                    $base_url = $protocol . "://" . $_SERVER['SERVER_NAME'] .str_replace('index.php', '',$_SERVER['SCRIPT_NAME']);
                    header('Location: '.$base_url.'notfound/exceptionerrors/'.$data);
            }
        }
    }

    /**
     * [exception_handler description]
     * Handle Database Errors
     * @param  Exception $e [description]
     * @return [type]       [description]
     */
    public static function exception_handler(Exception $e)
    {   try
        {
            if($e->getMessage()){
                $trace = $e->getTrace();
                $custom_error = explode(":",$e->getMessage());
                $trace[1]['args'][1]['type'] = "DB";

                $output = array('type'=>'DB','line'=>$trace[1]['args'][1][4],'file'=>$trace[1]['args'][1][3],'description'=>$trace[1]['args'][1][2],'message'=>$trace[1]['args'][1][1],'error_number'=>$trace[1]['args'][1][0]);
                $data = base64_encode(json_encode($output));
                if(isset($_SERVER['HTTPS'])){
                        $protocol = ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] != "off") ? "https" : "http";
                    }
                    else{
                        $protocol = 'http';
                    }
                    $base_url = $protocol . "://" . $_SERVER['SERVER_NAME'] .str_replace('index.php', '',$_SERVER['SCRIPT_NAME']);
            header('Location: '.$base_url.'notfound/exceptionerrors/'.$data);
            }
            // Get the exception information
            $type    = get_class($e);
            $code   = $e->getCode();
            $message = $e->getMessage();
            $file    = $e->getFile();
            $line    = $e->getLine();

            // Create a text version of the exception
            $error = self::exception_text($e);

            // Log the error message
            log_message('error', $error, TRUE);
            // Get the exception backtrace
            $trace = $e->getTrace();

            if ($e instanceof ErrorException)
            {
                if (isset(self::$php_errors[$code]))
                {
                    // Use the human-readable error name
                    $code = self::$php_errors[$code];
                }

                if (version_compare(PHP_VERSION, '5.3', '<'))
                {
                    // Workaround for a bug in ErrorException::getTrace() that exists in
                    // all PHP 5.2 versions. @see http://bugs.php.net/bug.php?id=45895
                    for ($i = count($trace) - 1; $i > 0; --$i)
                    {
                        if (isset($trace[$i - 1]['args']))
                        {
                            // Re-position the args
                            $trace[$i]['args'] = $trace[$i - 1]['args'];

                            // Remove the args
                            unset($trace[$i - 1]['args']);
                        }
                    }
                }
            }
            // Start an output buffer
            ob_start();

            // This will include the custom error file.
            //require APPPATH . 'errors/error_php_custom.php';

            // Display the contents of the output buffer
            echo ob_get_clean();

            return TRUE;
        }
        catch (Exception $e)
        {
            // Clean the output buffer if one exists
            ob_get_level() and ob_clean();

            // Display the exception text
            echo self::exception_text($e), "\n";

            // Exit with an error status
            exit(1);
        }
    }

    /**
     * [shutdown_handler description]
     * @return [type] [description]
     */
    public static function shutdown_handler()
    {
        $error = error_get_last();
        if ($error = error_get_last() AND in_array($error['type'], self::$shutdown_errors))
        {
            // Clean the output buffer
            ob_get_level() and ob_clean();

            // Fake an exception for nice debugging
            self::exception_handler(new ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']));

            // Shutdown now to avoid a "death loop"
            exit(1);
        }
    }

    public static function exception_text(Exception $e)
    {
        return sprintf('%s [ %s ]: %s ~ %s [ %d ]',
            get_class($e), $e->getCode(), strip_tags($e->getMessage()), $e->getFile(), $e->getLine());
    }
 
    

    /**
     * General Error Page
     *
     * This function takes an error message as input
     * (either as a string or an array) and displays
     * it using the specified template.
     *
     * @access  private
     * @param   string  the heading
     * @param   string  the message
     * @param   string  the template name
     * @return  string
     */
    function show_error($heading, $message, $template = 'error_general', $status_code = 500)
    {
        // If we are in production, then lets dump out now.
        if (IN_PRODUCTION)
        {
            return parent::show_error($heading, $message, $template, $status_code);
        }
        
        if( ! headers_sent())
        {
            set_status_header($status_code);
        }
        $trace = debug_backtrace();
        $file = NULL;
        $line = NULL;
        
        $is_from_app = FALSE;
        if(isset($trace[1]['file']) AND strpos($trace[1]['file'], APPPATH) === 0)
        {
            $is_from_app = !self::is_extension($trace[1]['file']);
        }

        // If the application called show_error, don't output a backtrace, just the error
        if($is_from_app)
        {
            $message = '<p>'.implode('</p><p>', ( ! is_array($message)) ? array($message) : $message).'</p>';

            if (ob_get_level() > $this->ob_level + 1)
            {
                ob_end_flush(); 
            }
            ob_start();
            include(APPPATH.'errors/'.$template.EXT);
            $buffer = ob_get_contents();
            ob_end_clean();
            return $buffer;
        }

        $message = implode(' / ', ( ! is_array($message)) ? array($message) : $message);

        // If the system called show_error, so lets find the actual file and line in application/ that caused it.
        foreach($trace as $call)
        {
            if(isset($call['file']) AND strpos($call['file'], APPPATH) === 0 AND !self::is_extension($call['file']))
            {
                $file = $call['file'];
                $line = $call['line'];
                break;
            }
        }
        unset($trace);

        self::exception_handler(new ErrorException($message, E_ERROR, 0, $file, $line));
        return;
    }

    /**
     * [debug_source description]
     * @param  [type]  $file        [description]
     * @param  [type]  $line_number [description]
     * @param  integer $padding     [description]
     * @return [type]               [description]
     */
    public static function debug_source($file, $line_number, $padding = 5)
    {
        if ( ! $file OR ! is_readable($file))
        {
            // Continuing will cause errors
            return FALSE;
        }

        // Open the file and set the line position
        $file = fopen($file, 'r');
        $line = 0;

        // Set the reading range
        $range = array('start' => $line_number - $padding, 'end' => $line_number + $padding);

        // Set the zero-padding amount for line numbers
        $format = '% '.strlen($range['end']).'d';

        $source = '';
        while (($row = fgets($file)) !== FALSE)
        {
            // Increment the line number
            if (++$line > $range['end'])
                break;

            if ($line >= $range['start'])
            {
                // Make the row safe for output
                $row = htmlspecialchars($row, ENT_NOQUOTES);

                // Trim whitespace and sanitize the row
                $row = '<span class="number">'.sprintf($format, $line).'</span> '.$row;

                if ($line === $line_number)
                {
                    // Apply highlighting to this row
                    $row = '<span class="line highlight">'.$row.'</span>';
                }
                else
                {
                    $row = '<span class="line">'.$row.'</span>';
                }

                // Add to the captured source
                $source .= $row;
            }
        }

        // Close the file
        fclose($file);

        return '<pre class="source"><code>'.$source.'</code></pre>';
    }
    
    

}
