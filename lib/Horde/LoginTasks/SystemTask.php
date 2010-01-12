<?php
/**
 * Abstract class to allow for modularization of specific system login tasks
 * that are always run on login.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_LoginTasks
 */
abstract class Horde_LoginTasks_SystemTask
{
    /**
     * Should the task be run?
     *
     * @var boolean
     */
    public $active = true;

    /**
     * The interval at which to run the task.
     *
     * @var integer
     */
    public $interval = Horde_LoginTasks::EVERY;

    /**
     * Do login task (if it has been confirmed).
     *
     * @return boolean  Whether the login task was successful.
     */
    abstract public function execute();

}
