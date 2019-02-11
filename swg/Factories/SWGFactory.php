<?php

/**
 * Factories allow you to get items from the database by setting criteria before searching.
 * The advantage over using a single method is that you can set any number of criteria as you want,
 * and it's clearer what each option does compared to lots of arguments on a single method
 *
 * Set the public variables on the factory to define your options (see the factory concrete classes)
 * Finally, call get() to build the required objects
 *
 * You can call reset() to reset all options to defaults, but factories should be designed
 * so you don't have to do this after building a new factory.
 */
abstract class SWGFactory
{
    /**
     * Reset the factory to default settings
     *
     * @return void
     */
    public abstract function reset();
    
    /**
     * Get the required objects based on current factory settings
     *
     * @return SWGBaseModel[]
     */
    public abstract function get();
}
