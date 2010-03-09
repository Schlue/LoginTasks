<?php
/**
 * The Horde_LoginTasks_Tasklist:: class is used to store the list of
 * login tasks that need to be run during this login.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_LoginTasks
 */
class Horde_LoginTasks_Tasklist
{
    /**
     * The URL of the web page to load after login tasks are complete.
     *
     * @var string
     */
    public $target;

    /**
     * Has this tasklist been processed yet?
     *
     * @var boolean
     */
    public $processed = false;

    /**
     * The list of tasks to run during this login.
     *
     * KEY: Task name
     * VALUE: array => (
     *   'display' => boolean,
     *   'task' => integer
     * )
     *
     * @var array
     */
    protected $_tasks = array();

    /**
     * Current task location pointer.
     *
     * @var integer
     */
    protected $_ptr = 0;

    /**
     * Adds a task to the tasklist.
     *
     * @param Horde_LoginTasks_Task $task  The task to execute.
     */
    public function addTask($task)
    {
        $tmp = array(
            'display' => false,
            'task' => $task
        );

        if ($task->display != Horde_LoginTasks::DISPLAY_NONE) {
            $tmp['display'] = true;
        }

        switch ($task->priority) {
        case Horde_LoginTasks::PRIORITY_HIGH:
            array_unshift($this->_tasks, $tmp);
            break;

        case Horde_LoginTasks::PRIORITY_NORMAL:
            $this->_tasks[] = $tmp;
            break;
        }
    }

    /**
     * Returns the list of tasks to perform.
     *
     * @param boolean $advance  If true, mark ready tasks as completed.
     *
     * @return array  The list of tasks to perform.
     */
    public function ready($advance = false)
    {
        $tmp = array();

        reset($this->_tasks);
        while (list($k, $v) = each($this->_tasks)) {
            if ($v['display'] && ($k >= $this->_ptr)) {
                break;
            }
            $tmp[] = $v['task'];
        }

        if ($advance) {
            $this->_tasks = array_slice($this->_tasks, count($tmp));
            $this->_ptr = 0;
        }

        return $tmp;
    }

    /**
     * Returns the next batch of tasks that need display.
     *
     * @param boolean $advance  If true, advance the internal pointer.
     *
     * @return array  The list of tasks to display.
     */
    public function needDisplay($advance = false)
    {
        $tmp = array();
        $previous = null;

        reset($this->_tasks);
        while (list($k, $v) = each($this->_tasks)) {
            if (!$v['display'] ||
                (!is_null($previous) && !$v['task']->joinDisplayWith($previous))) {
                break;
            }
            $tmp[] = $v['task'];
            $previous = $v['task'];
        }

        if ($advance) {
            $this->_ptr = count($tmp);
        }

        return $tmp;
    }

    /**
     * Are all tasks complete?
     *
     * @return boolean  True if all tasks are complete.
     */
    public function isDone()
    {
        return ($this->_ptr == count($this->_tasks));
    }

}
