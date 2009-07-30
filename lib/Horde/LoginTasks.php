<?php
/**
 * The Horde_LoginTasks:: class provides a set of methods for dealing with
 * login tasks to run upon login to Horde applications.
 *
 * Copyright 2001-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_LoginTasks
 */
class Horde_LoginTasks
{
    /* Interval settings. */
    // Do task yearly (First login after/on January 1).
    const YEARLY = 1;
    // Do task monthly (First login after/on first of month).
    const MONTHLY = 2;
    // Do task weekly (First login after/on a Sunday).
    const WEEKLY = 3;
    // Do task daily (First login of the day).
    const DAILY = 4;
    // Do task every login.
    const EVERY = 5;
    // Do task on first login only.
    const FIRST_LOGIN = 6;
    // Do task once only.
    const ONCE = 7;

    /* Display styles. */
    const DISPLAY_CONFIRM_NO = 1;
    const DISPLAY_CONFIRM_YES = 2;
    const DISPLAY_AGREE = 3;
    const DISPLAY_NOTICE = 4;
    const DISPLAY_NONE = 5;

    /* Priority settings */
    const PRIORITY_HIGH = 1;
    const PRIORITY_NORMAL = 2;

    /**
     * Singleton instance.
     *
     * @var array
     */
    static protected $_instances = array();

    /**
     * The Horde_LoginTasks_Tasklist object for this login.
     *
     * @var Horde_LoginTasks_Tasklist
     */
    protected $_tasklist;

    /**
     * Was the tasklist init'd in this access?
     *
     * @var boolean
     */
    protected $_init = false;

    /**
     * Attempts to return a reference to a concrete Horde_LoginTasks
     * instance based on $app. It will only create a new instance
     * if no instance with the same parameters currently exists.
     *
     * This method must be invoked as:
     *   $var = Horde_LoginTasks::singleton($app[, $params]);
     *
     * @param string $app  See self::__construct().
     * @param string $url  The URL to redirect to when finished.
     *
     * @return Horde_LoginTasks  The singleton instance.
     */
    static public function singleton($app, $url = null)
    {
        if (empty(self::$_instances[$app])) {
            self::$_instances[$app] = new Horde_LoginTasks($app, $url);
        }

        return self::$_instances[$app];
    }

    /**
     * Constructor.
     *
     * @param string $app  The name of the Horde application.
     * @param string $url  The URL to redirect to when finished.
     */
    protected function __construct($app, $url)
    {
        $this->_app = $app;

        if (!Horde_Auth::getAuth()) {
            return;
        }

        /* Retrieves a cached tasklist or make sure one is created. */
        if (isset($_SESSION['horde_logintasks'][$app])) {
            $this->_tasklist = @unserialize($_SESSION['horde_logintasks'][$app]);
        }

        if (empty($this->_tasklist)) {
            $this->_createTaskList($url);
            $this->_init = true;
        }
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        if (isset($this->_tasklist)) {
            $_SESSION['horde_logintasks'][$this->_app] = serialize($this->_tasklist);
        }
    }

    /**
     * Creates the list of login tasks that are available for this session
     * (stored in a Horde_LoginTasks_Tasklist object).
     *
     * @param string $url  The URL to redirect to when finished.
     */
    protected function _createTaskList($url)
    {
        /* Create a new Horde_LoginTasks_Tasklist object. */
        $this->_tasklist = new Horde_LoginTasks_Tasklist($url);

        /* Get last task run date(s). Array keys are app names, values are
         * last run timestamps. Special key '_once' contains list of
         * ONCE tasks previously run. */
        $lasttask_pref = @unserialize($GLOBALS['prefs']->getValue('last_logintasks'));
        if (!is_array($lasttask_pref)) {
            $lasttask_pref = array();
        }

        /* Add Horde tasks here if not yet run. */
        $app_list = array($this->_app);
        if (($this->_app != 'horde') &&
            !isset($_SESSION['horde_logintasks']['horde'])) {
            array_unshift($app_list, 'horde');
        }

        $tasks = array();

        foreach ($app_list as $app) {
            $fileroot = $GLOBALS['registry']->get('fileroot', $app);
            if (!is_null($fileroot)) {
                foreach (array('SystemTask', 'Task') as $val) {
                    if (is_dir($fileroot . '/lib/LoginTasks/' . $val)) {
                        foreach (scandir($fileroot . '/lib/LoginTasks/' . $val) as $file) {
                            $classname = $app . '_LoginTasks_' . $val . '_' . basename($file, '.php');
                            if (class_exists($classname)) {
                                $tasks[$classname] = $app;
                            }
                        }
                    }
                }
            }
        }

        if (empty($tasks)) {
            return;
        }

        /* Create time objects for today's date and last task run date. */
        $cur_date = getdate();

        foreach ($tasks as $classname => $app) {
            $ob = new $classname();

            /* If marked inactive, skip the task. */
            if (!$ob->active) {
                continue;
            }

            $addtask = false;

            if ($ob->interval == self::FIRST_LOGIN) {
                $addtask = empty($lasttask_pref[$app]);
            } else {
                $lastrun = getdate(empty($lasttask_pref[$app]) ? time() : $lasttask_pref[$app]);

                switch ($ob->interval) {
                case self::YEARLY:
                    $addtask = ($cur_date['year'] > $lastrun['year']);
                    break;

                case self::MONTHLY:
                    $addtask = (($cur_date['year'] > $lastrun['year']) || ($cur_date['mon'] > $lastrun['mon']));
                    break;

                case self::WEEKLY:
                    $addtask = (($cur_date['wday'] < $lastrun['wday']) || ((time() - 604800) > $lastrun['wday']));
                    break;

                case self::DAILY:
                    $addtask = (($cur_date['year'] > $lastrun['year']) || ($cur_date['yday'] > $lastrun['yday']));
                    break;

                case self::EVERY:
                    $addtask = true;
                    break;

                case self::ONCE:
                    if (empty($lasttask_pref['_once']) ||
                        !in_array($classname, $lasttask_pref['_once'])) {
                        $addtask = true;
                        $lasttask_pref['_once'][] = $classname;
                        $GLOBALS['prefs']->setValue('last_logintasks', serialize($lasttask_pref));
                    }
                    break;
                }
            }

            if ($addtask) {
                $this->_tasklist->addTask($ob);
            }
        }
    }

    /**
     * Do operations needed for this login.
     *
     * This function will generate the list of tasks to perform during this
     * login and will redirect to the login tasks page if necessary.  This is
     * the function that should be called from the application upon login.
     *
     * @param array $options  Options:
     * <pre>
     * 'confirmed' - (boolean) If true, indicates that any pending actions
     *                         have been confirmed by the user.
     * 'runtasks' - (boolean) If true, run all tasks. If false, only run
     *                        system tasks.
     * </pre>
     */
    public function runTasks($options = array())
    {
        if (!isset($this->_tasklist) ||
            ($this->_tasklist === true)) {
            return;
        }

        $options['advance'] = $this->_init || !empty($options['confirmed']);

        /* Perform ready tasks now. */
        foreach ($this->_tasklist->ready($options) as $key => $val) {
            if ($val instanceof Horde_LoginTasks_SystemTask ||
                in_array($val->display, array(self::DISPLAY_AGREE, self::DISPLAY_NOTICE, self::DISPLAY_NONE)) ||
                Horde_Util::getFormData('logintasks_confirm_' . $key)) {
                $val->execute();
            }
        }

        $need_display = $this->_tasklist->needDisplay();
        $tasklist_target = $this->_tasklist->target;

        /* If we've successfully completed every task in the list (or skipped
         * it), record now as the last time login tasks was run. */
        if ($this->_tasklist->isDone()) {
            $lasttasks = unserialize($GLOBALS['prefs']->getValue('last_logintasks'));
            $lasttasks[$this->_app] = time();
            if (($this->_app != 'horde') &&
                !isset($_SESSION['horde_logintasks']['horde'])) {
                $lasttasks['horde'] = time();
                $_SESSION['horde_logintasks']['horde'] = true;
            }
            $GLOBALS['prefs']->setValue('last_logintasks', serialize($lasttasks));

            /* This will prevent us from having to store the entire tasklist
             * object in the session, while still indicating we have
             * completed the login tasks for this application. */
            $this->_tasklist = true;
        }

        if ($this->_init && $need_display) {
            header('Location: ' . $this->getLoginTasksUrl());
            exit;
        } elseif (!$this->_init && !$need_display) {
            header('Location: ' . $tasklist_target);
            exit;
        }
    }

    /**
     * Generate the list of tasks that need to be displayed.
     *
     * This is the function called from the login tasks page every time it
     * is loaded.
     *
     * @return array  The list of tasks that need to be displayed.
     */
    public function displayTasks()
    {
        return $this->_tasklist->needDisplay(true);
    }

    /**
     * Generated the login tasks URL.
     *
     * @return string  The login tasks URL.
     */
    public function getLoginTasksUrl()
    {
        return Horde::url(Horde_Util::addParameter($GLOBALS['registry']->get('webroot', 'horde') . '/services/logintasks.php', array('app' => $this->_app)), true);
    }

}
