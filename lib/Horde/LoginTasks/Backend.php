<?php
/**
 * The Horde_LoginTasks_Backend:: class provides the specific backend providing
 * the dependencies of the LoginTasks system (e.g. preferences, session storage,
 * redirection facilites, shutdown management etc.)
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @author  Gunnar Wrobel <wrobel@pardus.de>
 * @package Horde_LoginTasks
 */
abstract class Horde_LoginTasks_Backend
{
    /**
     * Is the current session authenticated?
     *
     * @return boolean True if the user is authenticated, false otherwise.
     */
    abstract public function isAuthenticated();

    /**
     * Retrieve a cached tasklist if it exists.
     *
     * @return Horde_LoginTasks_Tasklist|boolean The cached task list or false
     * if no task list was cached.
     */
    abstract public function getTasklistFromCache();

    /**
     * Store a login tasklist in the cache.
     *
     * @param Horde_LoginTasks_Tasklist|boolean The tasklist to be stored.
     *
     * @return NULL
     */
    abstract public function storeTasklistInCache($tasklist);

    /**
     * Register the shutdown handler.
     *
     * @param array The shutdown function
     *
     * @return NULL
     */
    abstract public function registerShutdown($shutdown);

    /**
     * Get the class names of the task classes that need to be performed.
     *
     * @return array An array of class names.
     */
    abstract public function getTasks();

    /**
     * Get the information about the last time the tasks were run. Array keys
     * are app names, values are last run timestamps. Special key '_once'
     * contains list of ONCE tasks previously run.
     *
     * @return array The information about the last time the tasks were run.
     */
    abstract public function getLastRun();

    /**
     * Store the information about the last time the tasks were run.
     *
     * @param array $last The information about the last time the tasks were run.
     *
     * @return NULL
     */
    abstract public function setLastRun(array $last);

    /**
     * Mark the current time as time the login tasks were run for the last time.
     *
     * @return NULL
     */
    abstract public function markLastRun();

    /**
     * Return the URL of the login tasks view.
     *
     * @return string The URL of the login tasks view
     */
    abstract public function getLoginTasksUrl();
}