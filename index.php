<?php

date_default_timezone_set('America/New_York');

/* Get an instance of the index object */
$index = Index::instance();

/* Set your routes. 
 *
 * The is just a mapping of request URLs to PHP/HTML files,
 * usually refferred to as 'views' in developer-speak.
 *
 * The first argument is a regular expression
 * (or just a plain string) that can be matched against the current
 * request. The second is the path to the file that we'll load,
 * omitting the .php extention. It can also be a callable function,
 * in case you need to do something fancy like AJAX requests (which have limited
 * use if you're building to HTML).
 */
$index->route('/?', 'index');

foreach (glob("assets/data/*.json") as $filename) {
    $filename = str_replace('.json', '', basename($filename));
    $index->route("/$filename", 'index');
}

/* Config */
$index->config('site_name', '');
$index->config('site_url', '');
$index->config('default_artist_slug', 'grateful-dead');

/**
 * index.php, maybe the most portable micro MVC framework. It
 *  handles smart segment-based routing (pretty URLs), and
 *  automatically determines where it lives in relation
 *  to the filesystem and the webserver.
 * 
 * @author Kenny Katzgrau <kenny@katzgrau.com>
 * @link   https://github.com/katzgrau/index.php
 * 
 * This is the core of the single-file framework.
 */
class Index
{
    /**
     * The internal Index instance
     * @var Index
     */
    protected static $_instance = NULL;
    
    /**
     * Loaded bottlenecks
     * @var array
     */
    protected $_bottlenecks = array();
    
    /**
     * The array of routes
     * @var array
     */
    protected $_routes = array();
    
    /**
     * An array of config values
     * @var array
     */
    protected $_config = array();
    
    /**
     * The currently executing URL
     * @var string
     */
    protected $_request = NULL;
    
    /**
     * The type of request (get, put, post, cli)
     * @var string
     */
    protected $_requestType = NULL;
    
    /**
     * The time that the script was kicked off
     * @var long
     */
    protected $_startTime = NULL;
    
    /**
     * Get an instance of the framework
     * @return Index 
     */
    public static function instance()
    {
        if(self::$_instance === NULL)
            self::$_instance = new self();
        
        return self::$_instance;
    }
    
    public function __construct() 
    {
        global $argc, $argv;
        
        $this->_startTime = time();
        
        if(php_sapi_name() == 'cli')
            $this->_requestType = 'cli';
        else
            $this->_requestType = strtolower($_SERVER['REQUEST_METHOD']);
        
        if($this->_requestType == 'cli')
        {
            if($argc > 1)
                $this->_request = $argv[1];
                $this->_config['is_build'] = ($this->_request == 'build');
        }
        else
        {
            $dirname = dirname($_SERVER['SCRIPT_NAME']);

            if($dirname != '/') {
                /* Find the base URL if the .htaccess is setup */
                $this->_request = preg_replace("#" . addslashes(dirname($_SERVER['SCRIPT_NAME'])) . "#", 
                                    '', 
                                    $_SERVER['REQUEST_URI']);
            } else {
                /* In this case, we're probably using the build in PHP server (-S) */
                $this->_request = $_SERVER['REQUEST_URI'];
            }

            /* Find the base URL if .htaccess isn't set up */
            $this->_request = preg_replace("#{$_SERVER['SCRIPT_NAME']}#", 
                                '', 
                                $this->_request);
            
            $this->_request = '/' . ltrim($this->_request, '/');

            /* Fix the PATH_INFO variable */
            if(isset($_SERVER['PATH_INFO']))
            {
                $extra_path = $_SERVER['PATH_INFO'];
            }
            else
            {
                $extra_path = '/';
            }
        
            /* Set any last config items */
            $this->_config['base_url'] = '/' . ltrim(preg_replace("#{$extra_path}#", '', $_SERVER['REQUEST_URI']) . '/', '/');
            $this->_config['base_url_full'] = (@$_SERVER['HTTPS'] ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'] . $this->_config['base_url'];
            $this->_config['request'] = $this->_request;
        }
        
        /* Find the base directory that we're running out of */
        $this->_config['base_dir'] = $_SERVER['DOCUMENT_ROOT']
                                    . dirname($_SERVER['SCRIPT_NAME']);
    }
    
    /**
     * Load a bottleneck! Load more!
     * @param string $name
     * @return Bottleneck
     */
    public function bottleneck($name)
    {
        if(isset($this->_bottlenecks[$name]))
            return $this->_bottlenecks[$name];
        
        $class = ucfirst($name);
        
        require dirname(__FILE__) . "/bottlenecks/$name/$name.php";
        
        if(!class_exists($name))
            exit("The bottleneck '$name' doesn't exist. Sorry!");
        
        $this->_bottlenecks[$name] = new $class($this);
        
        /* Load the routes from the bottlebeck, if they exist */
        $routes = $this->_bottlenecks[$name]->routes();
        foreach($routes as $route)
        {
            $this->_routes[$route[0]] = $route[1];
        }
        
        return $this->_bottlenecks[$name];
    }
    
    /**
     * Build a static version of the site in HTML
     */
    public function build()
    {
        if(!file_exists('build'))
        {
            if(!@mkdir('build'))
            {
                exit("Could not create 'build' directory\n");
            }
        }
        
        $outpath = "build/" . $this->_startTime;
        
        if(!@mkdir($outpath))
        {
            exit("Could not create 'build' directory: $outpath\n");
        }
        
        if(system("cp -r assets $outpath"))
            echo "Could not copy assets directory";
        
        foreach($this->_routes as $route => $view)
        {
            $route = str_replace('/?', '/', $route);
            $route = preg_replace('#/$#', '', $route);
            
            if(!@mkdir("$outpath/$route", 0777, true))
            {
                echo "Warning: Could not create folder $outpath/$route. It may already exist.\n";
            }
            
            $levels_deep = count(explode('/', $route)) - 1;
            $this->_config['base_url']      = str_repeat('../', $levels_deep);
            $this->_config['base_url_full'] = $this->config('site_url');
            $this->_config['request']       = $route;
            
            ob_start();
            if(is_callable($view))
                call_user_func($view, $route);
            else
                $this->load("views/$view.php");
            $html = ob_get_contents();
            ob_end_clean();
            
            if(!@file_put_contents("{$outpath}$route/index.html", $html))
                echo "Could not create {$outpath}$route/index.html\n";
            else
                echo "Creating {$outpath}$route/index.html\n";
        }

        echo "$outpath\n";
    }
    
    /**
     * Execute Index.php on the current request with the already-loaded
     *  routes and configuration
     * @return void
     */
    public function execute()
    {
        if($this->_requestType == 'cli')
        {
            if($this->_request == 'build')
            {
                $this->build();
            }
            
            return;
        }
        
        $this->auth();
        
        foreach($this->_routes as $pattern => $view)
        {
            if(preg_match("#^$pattern/?$#", $this->_request))
            {
                if(is_callable($view))
                    call_user_func($view, $this->_request);
                else
                    $this->load("views/$view.php");
                
                return;
            }
        }
        
        exit('404. Not Found');
    }
    
    /**
     * Get or set a config item
     * @param string $name
     * @param string $value [FALSE] If omitted, this function return 
     * @return type 
     */
    public function config($name, $value = 'INDEX_NO_VAL')
    {
        if($value === 'INDEX_NO_VAL')
        {
            return isset($this->_config[$name]) ? $this->_config[$name] : null;
        }
        else
        {
            $this->_config[$name] = $value;
        }
    }
    
    /**
     * Load (include) a file. Will attempt to load it and throw an exception
     *  if it isnt found
     * @param string $path The [base] directory name where the file would 
     *  be found
     * @param type $file The filename to load
     * @param type $data Associate array of variables to pass to the view
     * @param type $ext The extention of the file, 'php' by default
     * @throws Exception If the file isn't found
     */
    public function load($path, $data = array(), $return = false)
    {
        $filepath = $this->_config['base_dir']
                    . DIRECTORY_SEPARATOR
                    . trim($path, '/');
        
        if(!file_exists($filepath))
        {
            throw new Exception("Index.php: Requested load of file $filepath does not exist");
        }
        
        # Template data
        extract($data);
        
        if($return) ob_start();
        
        include $filepath;
        
        if($return) {
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        }
    }
    
    /**
     * Prompt the user for their credentials. Issues a basic HTTP Auth
     *  challenge.
     */
    public function auth()
    {
        $user = @$_SERVER['PHP_AUTH_USER'];
        $pass = @$_SERVER['PHP_AUTH_PW'];
        
        $username = @$this->_config['username'];
        $password = @$this->_config['password'];

        /* If the developer didn't set a username, bail */
        if(!$username) return;
        
        if($user !== $user || $password !== $pass)
        {
            header('HTTP/1.0 401 Unauthorized');
            header("WWW-Authenticate: Basic realm=\"{$_SERVER['SERVER_NAME']}\"");
            die("<h1>Unauthorized</h1>\n");
        }
    }

    /**
     * Set a route
     * @param type $pattern The pattern to match
     * @param type $path The view to load
     */
    public function route($pattern, $path = FALSE)
    {
        if(is_array($pattern))
        {
            foreach($pattern as $key => $val)
                $this->route($key, $val);
        }
        else
        {
            $this->_routes[$pattern] = $path;
        }
    }
    
    /**
     * Get the URL to a file or page with the base URL attached
     * @param string $path 
     * @return The URL
     */
    public function url($path, $use_full = false)
    {
        if($use_full) $type = 'base_url_full'; else $type = 'base_url';
        
        $is_asset = preg_match('/(png|jpg|jpeg|css|js)$/i', $path);

        return rtrim($this->_config[$type], '/') 
                . (strlen($this->_config[$type]) ? '/' : '')
                . trim($path, '/')
                . ($this->config('cache_buster') && $is_asset ? '?' . $this->_startTime : '');
    }
    
    /**
     * Check the URL for a given slug/url segment
     * @param string $slug The slug to check for
     * @param int $i The index of the slug to check. If not supplied, the entire
     *  url will be checked for the slug
     */
    public function slug($i = false, $slug = false)
    {
        $segs = explode('/', trim($this->_config['request'], '/'));
        
        if($i === false && $slug === false)
            return $segs;
        
        if($i === false && $slug !== false)
        {
            $segs = array_flip($segs);
            return isset($segs[$slug]);
        }
        
        if($i !== false && $slug === false)
        {
            return $segs[$i];
        }
        
        return (@$segs[$i] == $slug);
    }
    
    
}

abstract class Bottleneck
{
    /**
     * The Index object
     * @var Index
     */
    protected $_index = null;
    public function __construct($index) { $this->_index = $index; }
    public function routes() { return array(); }
}

/**
 * Helper function for getting config items
 * @param string $name
 * @param string $value 
 */
function index_config($name, $value = 'INDEX_NO_VAL') 
{
    return Index::instance()->config($name, $value);
}

/**
 * Helper function for getting the path to a file
 * @param type $path
 * @return type 
 */
function index_url($path, $use_full = false)
{
    return Index::instance()->url($path, $use_full);
}

/**
 * Helper function for loading a file
 * @param type $path
 * @param type $file
 * @param type $data
 * @param type $ext 
 */
function index_load($path, $data = array())
{
    return Index::instance()->load($path, $data);
}

/**
 * @param type $i
 * @param type $slug 
 */
function index_slug($i = false, $slug = false)
{
    return Index::instance()->slug($i, $slug);
}

/* Kick off index.php */
Index::instance()->execute();
